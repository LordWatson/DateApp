<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AI\AIUseCase;
use App\Enums\CompletionStatus;
use App\Models\CalendarEvent;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Moment;
use App\Models\Response;
use App\Models\User;
use App\Models\WeeklyReflection;
use App\Services\AI\AIService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Generates and retrieves the "Weekly Reflection": insights drawn from what
 * the couple actually did over the past seven days. One reflection is shared
 * by both partners, so they always read the same thing.
 */
final readonly class WeeklyReflectionService
{
    /** Days of activity a reflection looks back over, today included. */
    private const PERIOD_DAYS = 7;

    private const MAX_ITEMS = 10;

    public function __construct(
        private AIService $ai,
        private LoggerInterface $logger,
    ) {}

    /**
     * Return this week's reflection for the couple, generating it when
     * neither partner has one yet.
     */
    public function currentOrGenerate(User $user, ?CarbonImmutable $now = null): WeeklyReflection
    {
        $now ??= CarbonImmutable::now();

        $find = fn (): ?WeeklyReflection => $this->sharedQuery($user)
            ->whereDate('week_start', $this->weekKey($now))
            ->orderBy('id')
            ->first();

        // Looked up again inside the lock: the other partner may have generated it while we waited.
        $existing = $find();
        if ($existing !== null) {
            return $existing;
        }

        return $this->locked($user, $now, fn (): WeeklyReflection => $find() ?? $this->generate($user, $now));
    }

    /**
     * Replace this week's reflection for both partners with a fresh one.
     */
    public function regenerateCurrent(User $user, ?CarbonImmutable $now = null): WeeklyReflection
    {
        $now ??= CarbonImmutable::now();

        return $this->locked($user, $now, function () use ($user, $now): WeeklyReflection {
            $this->sharedQuery($user)->whereDate('week_start', $this->weekKey($now))->delete();

            return $this->generate($user, $now);
        });
    }

    /**
     * The cache key of the lock that keeps a couple to one reflection per week.
     * It is the same whichever partner asks.
     */
    public function lockKey(User $user, CarbonImmutable $now): string
    {
        $ids = array_filter([$user->id, $user->partner_id]);
        sort($ids);

        return 'weekly-reflection:'.implode('-', $ids).':'.$this->weekKey($now);
    }

    /**
     * Run one generation at a time per couple and week, so both partners opening
     * the page at the same instant cannot each create a row.
     *
     * @param  Closure(): WeeklyReflection  $callback
     */
    private function locked(User $user, CarbonImmutable $now, Closure $callback): WeeklyReflection
    {
        $lock = Cache::lock($this->lockKey($user, $now), 120);

        try {
            $lock->block(30);
        } catch (LockTimeoutException) {
            // The other request is unusually slow: carry on rather than fail the page.
            return $callback();
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * @return Collection<int, WeeklyReflection>
     */
    public function history(User $user, int $limit = 8): Collection
    {
        return $this->sharedQuery($user)
            ->orderByDesc('week_start')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->unique(fn (WeeklyReflection $reflection): string => $reflection->week_start->toDateString())
            ->values();
    }

    /**
     * Reflections the user wrote, plus the ones their current partner generated while paired with them.
     *
     * @return Builder<WeeklyReflection>
     */
    private function sharedQuery(User $user): Builder
    {
        return WeeklyReflection::query()->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->id);

            if ($user->partner_id !== null) {
                $query->orWhere(function (Builder $query) use ($user): void {
                    $query->where('user_id', $user->partner_id)->where('partner_id', $user->id);
                });
            }
        });
    }

    /**
     * One reflection per calendar week; this is the key it is stored under.
     */
    private function weekKey(CarbonImmutable $now): string
    {
        return $now->startOfWeek()->toDateString();
    }

    private function generate(User $user, CarbonImmutable $now): WeeklyReflection
    {
        $end = $now->endOfDay();
        $start = $now->subDays(self::PERIOD_DAYS - 1)->startOfDay();

        $activity = $this->collectActivity($user, $start, $end);

        [$content, $fallbackUsed] = $this->generateContent($user, $activity);

        return WeeklyReflection::create([
            'user_id' => $user->id,
            'partner_id' => $user->partner_id,
            'week_start' => $this->weekKey($now),
            'week_end' => $end->toDateString(),
            'headline' => $content['headline'],
            'summary' => $content['summary'],
            'highlights' => $content['highlights'],
            'gentle_suggestion' => $content['gentle_suggestion'],
            'encouragement' => $content['encouragement'],
            // Counts only: the detail sent to the AI includes note text and is not kept.
            'metrics' => ['period_start' => $start->toDateString(), 'period_end' => $end->toDateString()] + $activity['totals'],
            'fallback_used' => $fallbackUsed,
            'generated_at' => Carbon::now(),
        ]);
    }

    /**
     * Everything the couple did in the period, written from the couple's point
     * of view so the result is the same whichever partner asks for it.
     *
     * @return array<string, mixed>
     */
    private function collectActivity(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        /** @var Collection<int, User> $people */
        $people = collect([$user, $user->partner])->filter()->sortBy('id')->values();
        $ids = $people->pluck('id')->all();
        $names = $people->mapWithKeys(fn (User $person): array => [
            $person->id => Str::before($person->display_name ?? $person->name, ' '),
        ]);
        $name = static fn (?int $id): string => $names->get($id, 'One of you');
        $days = [$start->toDateString(), $end->toDateString()];
        // whereDate, not whereBetween: a date stored with a midnight time must still match the last day.
        $inPeriod = static fn (Builder $query): Builder => $query
            ->whereDate('date', '>=', $days[0])
            ->whereDate('date', '<=', $days[1]);

        $dateNights = DateNightPlan::query()
            ->with(['partnerOneResponse:id,user_id'])
            ->whereBetween('created_at', [$start, $end])
            ->where(function (Builder $query) use ($ids): void {
                $query->whereHas('partnerOneResponse', fn (Builder $r) => $r->whereIn('user_id', $ids))
                    ->orWhereHas('partnerTwoResponse', fn (Builder $r) => $r->whereIn('user_id', $ids))
                    ->orWhereIn('partner_user_id', $ids);
            })
            ->orderBy('created_at')
            ->get();

        $done = DB::table('date_night_plan_completions')
            ->join('date_night_plans', 'date_night_plans.id', '=', 'date_night_plan_completions.date_night_plan_id')
            ->whereIn('date_night_plan_completions.user_id', $ids)
            ->whereDate('completed_on', '>=', $days[0])
            ->whereDate('completed_on', '<=', $days[1])
            ->orderBy('completed_on')
            ->get(['date_night_plans.theme', 'date_night_plan_completions.user_id', 'rating', 'completed_on']);

        $notes = LoveNote::query()
            ->whereIn('sender_id', $ids)
            ->whereIn('recipient_id', $ids)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get(['sender_id', 'message']);

        $moments = Moment::query()
            ->whereIn('user_id', $ids)
            ->tap($inPeriod)
            ->orderBy('date')
            ->get(['user_id', 'title', 'description', 'mood', 'date', 'is_favourite']);

        $events = CalendarEvent::query()
            ->whereIn('user_id', $ids)
            ->tap($inPeriod)
            ->orderBy('date')
            ->get(['title', 'type', 'date']);

        $comingUp = CalendarEvent::query()
            ->whereIn('user_id', $ids)
            ->whereDate('date', '>', $days[1])
            ->whereDate('date', '<=', $end->addDays(self::PERIOD_DAYS)->toDateString())
            ->orderBy('date')
            ->limit(5)
            ->get(['title', 'type', 'date']);

        $questionnaires = Response::query()
            ->with('questionnaire:id,title')
            ->whereIn('user_id', $ids)
            ->where('status', CompletionStatus::Completed->value)
            ->whereBetween('completed_at', [$start, $end])
            ->orderBy('completed_at')
            ->get(['id', 'user_id', 'questionnaire_id', 'completed_at']);

        $achievements = DB::table('user_achievements')
            ->join('achievements', 'achievements.id', '=', 'user_achievements.achievement_id')
            ->whereIn('user_achievements.user_id', $ids)
            ->whereBetween('user_achievements.unlocked_at', [$start, $end])
            ->orderBy('user_achievements.unlocked_at')
            ->get(['achievements.name', 'user_achievements.user_id']);

        $event = static fn (CalendarEvent $item): array => [
            'date' => $item->date->toDateString(),
            'title' => $item->title,
            'type' => $item->type,
        ];

        return [
            'period' => ['from' => $days[0], 'to' => $days[1]],
            'partners' => $names->values()->all(),
            'partner_connected' => $people->count() === 2,
            'date_nights' => $dateNights->take(self::MAX_ITEMS)->map(fn (DateNightPlan $plan): array => array_filter([
                'date' => $plan->created_at?->toDateString(),
                'theme' => $plan->theme,
                'planned' => $plan->is_solo ? 'by '.$name($plan->partnerOneResponse?->user_id) : 'together',
                'compatibility' => $plan->is_solo ? null : $plan->compatibility_score,
                'activity' => $plan->activity,
                'meal' => $plan->meal_suggestion,
                'where' => $plan->location_label,
            ], static fn (mixed $value): bool => $value !== null))->values()->all(),
            // Date nights the couple actually did this week, with each person's rating out of 5.
            'date_nights_done' => $done->take(self::MAX_ITEMS)->map(fn (object $row): array => [
                'date' => substr((string) $row->completed_on, 0, 10),
                'theme' => $row->theme,
                'rated_by' => $name((int) $row->user_id),
                'rating_out_of_5' => (int) $row->rating,
            ])->values()->all(),
            'love_notes' => [
                'total' => $notes->count(),
                'sent_by' => $notes->countBy(fn (LoveNote $note): string => $name($note->sender_id))->all(),
                'messages' => $notes->take(-8)->map(fn (LoveNote $note): array => [
                    'from' => $name($note->sender_id),
                    'message' => Str::limit($note->message, 140),
                ])->values()->all(),
            ],
            'moments' => $moments->take(self::MAX_ITEMS)->map(fn (Moment $moment): array => array_filter([
                'date' => $moment->date->toDateString(),
                'title' => $moment->title,
                'mood' => $moment->mood,
                'description' => $moment->description !== null ? Str::limit($moment->description, 200) : null,
                'favourite' => $moment->is_favourite ?: null,
                'added_by' => $name($moment->user_id),
            ], static fn (mixed $value): bool => $value !== null))->values()->all(),
            'calendar' => [
                'this_week' => $events->take(self::MAX_ITEMS)->map($event)->values()->all(),
                'coming_up' => $comingUp->map($event)->values()->all(),
            ],
            'questionnaires_completed' => $questionnaires->take(self::MAX_ITEMS)->map(fn (Response $response): array => [
                'date' => $response->completed_at?->toDateString(),
                'title' => $response->questionnaire?->title,
                'completed_by' => $name($response->user_id),
            ])->values()->all(),
            'achievements_unlocked' => $achievements->map(fn (object $row): array => [
                'name' => $row->name,
                'unlocked_by' => $name((int) $row->user_id),
            ])->all(),
            'streaks_in_days' => $people->mapWithKeys(fn (User $person): array => [
                $name($person->id) => (int) $person->current_streak,
            ])->all(),
            'totals' => [
                'date_nights' => $dateNights->count(),
                'love_notes' => $notes->count(),
                'moments' => $moments->count(),
                'calendar_events' => $events->count(),
                'questionnaires_completed' => $questionnaires->count(),
                'achievements_unlocked' => $achievements->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $activity
     * @return array{0: array{headline: string, summary: string, highlights: array<int, string>, gentle_suggestion: string, encouragement: string}, 1: bool}
     */
    private function generateContent(User $user, array $activity): array
    {
        $deterministic = $this->deterministicContent($activity);

        try {
            $response = $this->ai->generate(
                useCase: AIUseCase::WeeklyReflection,
                // The prompt template reads {{context}}; any other key never reaches the model.
                context: ['context' => $activity],
                // The recap holds both partners' notes and moments, so both must have agreed.
                people: array_filter([$user, $user->partner]),
                userId: $user->id,
            );
        } catch (Throwable $e) {
            $this->logger->warning('Weekly reflection AI generation threw; using deterministic fallback.', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);

            return [$deterministic, true];
        }

        if (! $response->successful) {
            return [$deterministic, true];
        }

        $merged = $this->mergeAiContent($deterministic, $response->data);

        return [$merged, false];
    }

    /**
     * Plain recap used when the AI is unavailable. It still names what happened.
     *
     * @param  array<string, mixed>  $activity
     * @return array{headline: string, summary: string, highlights: array<int, string>, gentle_suggestion: string, encouragement: string}
     */
    private function deterministicContent(array $activity): array
    {
        $totals = $activity['totals'];
        $listed = static fn (array $items, string $key): string => collect($items)
            ->pluck($key)
            ->filter()
            ->unique()
            ->take(3)
            ->map(fn (string $value): string => "\u{201C}{$value}\u{201D}")
            ->join(', ', ' and ');

        $highlights = [];

        if ($totals['date_nights'] > 0) {
            $highlights[] = 'You planned '.($totals['date_nights'] === 1 ? 'a date night' : "{$totals['date_nights']} date nights")
                .': '.$listed($activity['date_nights'], 'theme').'.';
        }

        if ($totals['moments'] > 0) {
            $highlights[] = 'You captured '.($totals['moments'] === 1 ? 'a moment' : "{$totals['moments']} moments")
                .': '.$listed($activity['moments'], 'title').'.';
        }

        if ($totals['love_notes'] > 0) {
            $highlights[] = $totals['love_notes'] === 1
                ? 'A love note was sent this week.'
                : "You exchanged {$totals['love_notes']} love notes.";
        }

        if ($totals['calendar_events'] > 0) {
            $highlights[] = 'On the calendar: '.$listed($activity['calendar']['this_week'], 'title').'.';
        }

        if ($totals['questionnaires_completed'] > 0) {
            $highlights[] = 'You answered '.$listed($activity['questionnaires_completed'], 'title').'.';
        }

        $comingUp = $activity['calendar']['coming_up'][0]['title'] ?? null;

        // Someone without a partner gets a recap about their own week, not about a couple.
        if (! $activity['partner_connected']) {
            return [
                'headline' => 'Your Week',
                'summary' => $highlights === []
                    ? 'A quiet week. Rest counts too.'
                    : 'This week you made time for the things that matter to you. Every plan and saved moment adds up.',
                'highlights' => $highlights === [] ? ['A gentle, quiet week.'] : array_slice($highlights, 0, 5),
                'gentle_suggestion' => $comingUp !== null
                    ? "You have \u{201C}{$comingUp}\u{201D} coming up — take a few minutes to look forward to it."
                    : 'Next week, maybe try one small new thing — a walk, a coffee somewhere new, or a fresh playlist.',
                'encouragement' => 'You are making time for yourself, and that matters.',
            ];
        }

        return [
            'headline' => 'This Week Together',
            'summary' => $highlights === []
                ? 'A quiet week together. Little moments still count — sometimes rest is the connection.'
                : 'This week you made space for each other in small, meaningful ways. '
                    .'Every note, plan and shared moment adds up to something warm.',
            'highlights' => $highlights === [] ? ['A gentle, quiet week together.'] : array_slice($highlights, 0, 5),
            'gentle_suggestion' => $comingUp !== null
                ? "You have \u{201C}{$comingUp}\u{201D} coming up — take a few minutes together to look forward to it."
                : 'Next week, maybe try one small new thing together — a walk, a coffee, or a shared playlist.',
            'encouragement' => 'You are showing up for each other, and that is what matters most.',
        ];
    }

    /**
     * @param  array{headline: string, summary: string, highlights: array<int, string>, gentle_suggestion: string, encouragement: string}  $deterministic
     * @param  array<string, mixed>  $aiData
     * @return array{headline: string, summary: string, highlights: array<int, string>, gentle_suggestion: string, encouragement: string}
     */
    private function mergeAiContent(array $deterministic, array $aiData): array
    {
        $result = $deterministic;

        foreach (['headline', 'summary', 'gentle_suggestion', 'encouragement'] as $key) {
            $value = $aiData[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $result[$key] = trim($value);
            }
        }

        $highlights = $aiData['highlights'] ?? null;
        if (is_array($highlights)) {
            $clean = [];
            foreach ($highlights as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $clean[] = trim($item);
                }
            }
            if ($clean !== []) {
                $result['highlights'] = array_slice($clean, 0, 5);
            }
        }

        return $result;
    }
}
