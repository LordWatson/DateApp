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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Generates and retrieves the "Weekly Reflection" — a warm, supportive
 * summary of the couple's past week across Date Nights, Love Notes,
 * Moments, Calendar and Questionnaire history.
 */
final readonly class WeeklyReflectionService
{
    public function __construct(
        private AIService $ai,
        private LoggerInterface $logger,
    ) {}

    /**
     * Return the latest reflection for the user, generating a new one for
     * the current week when none exists yet.
     */
    public function currentOrGenerate(User $user, ?CarbonImmutable $now = null): WeeklyReflection
    {
        $now ??= CarbonImmutable::now();
        $weekStart = $now->startOfWeek()->startOfDay();

        $existing = WeeklyReflection::query()
            ->where('user_id', $user->id)
            ->where('week_start', $weekStart->toDateString())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->generateForWeek($user, $weekStart);
    }

    /**
     * Force a fresh reflection for the current week, replacing an
     * existing one if present.
     */
    public function regenerateCurrent(User $user, ?CarbonImmutable $now = null): WeeklyReflection
    {
        $now ??= CarbonImmutable::now();
        $weekStart = $now->startOfWeek()->startOfDay();

        WeeklyReflection::query()
            ->where('user_id', $user->id)
            ->where('week_start', $weekStart->toDateString())
            ->delete();

        return $this->generateForWeek($user, $weekStart);
    }

    /**
     * @return Collection<int, WeeklyReflection>
     */
    public function history(User $user, int $limit = 8): Collection
    {
        return WeeklyReflection::query()
            ->where('user_id', $user->id)
            ->orderByDesc('week_start')
            ->limit($limit)
            ->get();
    }

    private function generateForWeek(User $user, CarbonImmutable $weekStart): WeeklyReflection
    {
        $weekEnd = $weekStart->endOfWeek()->endOfDay();

        $metrics = $this->collectMetrics($user, $weekStart, $weekEnd);

        [$content, $fallbackUsed] = $this->generateContent($user, $metrics);

        return WeeklyReflection::create([
            'user_id' => $user->id,
            'partner_id' => $user->partner_id,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'headline' => $content['headline'],
            'summary' => $content['summary'],
            'highlights' => $content['highlights'],
            'gentle_suggestion' => $content['gentle_suggestion'],
            'encouragement' => $content['encouragement'],
            'metrics' => $metrics,
            'fallback_used' => $fallbackUsed,
            'generated_at' => Carbon::now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function collectMetrics(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $partner = $user->partner;
        $partnerId = $partner?->id;
        $userIds = array_values(array_filter([$user->id, $partnerId]));

        $dateNights = DateNightPlan::query()
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) use ($user): void {
                $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                    ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
            })
            ->get(['theme', 'atmosphere', 'compatibility_score']);

        $loveNotesSent = LoveNote::query()
            ->where('sender_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $loveNotesReceived = $partnerId
            ? LoveNote::query()
                ->where('sender_id', $partnerId)
                ->where('recipient_id', $user->id)
                ->whereBetween('created_at', [$start, $end])
                ->count()
            : 0;

        $moments = Moment::query()
            ->whereIn('user_id', $userIds ?: [$user->id])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['title', 'mood']);

        $calendarEvents = CalendarEvent::query()
            ->whereIn('user_id', $userIds ?: [$user->id])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['title', 'type']);

        $questionnairesCompleted = Response::query()
            ->whereIn('user_id', $userIds ?: [$user->id])
            ->where('status', CompletionStatus::Completed->value)
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        return [
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'partner_connected' => $partner !== null,
            'date_nights' => [
                'count' => $dateNights->count(),
                'themes' => $dateNights->pluck('theme')->filter()->unique()->values()->all(),
                'atmospheres' => $dateNights->pluck('atmosphere')->filter()->unique()->values()->all(),
                'average_compatibility' => $dateNights->isEmpty()
                    ? null
                    : (int) round((float) $dateNights->avg('compatibility_score')),
            ],
            'love_notes' => [
                'sent' => $loveNotesSent,
                'received' => $loveNotesReceived,
            ],
            'moments' => [
                'count' => $moments->count(),
                'moods' => $moments->pluck('mood')->filter()->unique()->values()->all(),
            ],
            'calendar_events' => [
                'count' => $calendarEvents->count(),
                'types' => $calendarEvents->pluck('type')->filter()->unique()->values()->all(),
            ],
            'questionnaires_completed' => $questionnairesCompleted,
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{0: array{headline: string, summary: string, highlights: array<int, string>, gentle_suggestion: string, encouragement: string}, 1: bool}
     */
    private function generateContent(User $user, array $metrics): array
    {
        $deterministic = $this->deterministicContent($metrics);

        try {
            $response = $this->ai->generate(
                useCase: AIUseCase::WeeklyReflection,
                context: ['metrics' => $metrics],
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
     * @param  array<string, mixed>  $metrics
     * @return array{headline: string, summary: string, highlights: array<int, string>, gentle_suggestion: string, encouragement: string}
     */
    private function deterministicContent(array $metrics): array
    {
        $dateNights = (int) ($metrics['date_nights']['count'] ?? 0);
        $notesSent = (int) ($metrics['love_notes']['sent'] ?? 0);
        $notesReceived = (int) ($metrics['love_notes']['received'] ?? 0);
        $moments = (int) ($metrics['moments']['count'] ?? 0);
        $events = (int) ($metrics['calendar_events']['count'] ?? 0);
        $questionnaires = (int) ($metrics['questionnaires_completed'] ?? 0);

        $highlights = [];
        if ($dateNights > 0) {
            $highlights[] = $dateNights === 1
                ? 'You planned a date night together.'
                : "You planned {$dateNights} date nights together.";
        }
        if ($notesSent + $notesReceived > 0) {
            $highlights[] = 'You exchanged '.($notesSent + $notesReceived).' love notes.';
        }
        if ($moments > 0) {
            $highlights[] = "You captured {$moments} shared moment".($moments === 1 ? '' : 's').'.';
        }
        if ($events > 0) {
            $highlights[] = "You had {$events} calendar event".($events === 1 ? '' : 's').' on the horizon.';
        }
        if ($questionnaires > 0) {
            $highlights[] = 'You checked in through your questionnaires.';
        }

        $summary = $highlights === []
            ? 'A quiet week together. Little moments still count — sometimes rest is the connection.'
            : 'This week you made space for each other in small, meaningful ways. '
                .'Every note, plan and shared moment adds up to something warm.';

        return [
            'headline' => 'This Week Together',
            'summary' => $summary,
            'highlights' => $highlights === [] ? ['A gentle, quiet week together.'] : $highlights,
            'gentle_suggestion' => 'Next week, maybe try one small new thing together — a walk, a coffee, or a shared playlist.',
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
