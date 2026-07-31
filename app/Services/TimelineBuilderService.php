<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompletionStatus;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Response as QuestionnaireResponse;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aggregates the different domain events (questionnaires, plans,
 * moments, love notes, calendar events, achievements) into a single
 * chronological timeline for a user.
 */
final class TimelineBuilderService
{
    private const PER_TYPE_LIMIT = 20;

    private const ALLOWED_FILTERS = [
        'all',
        'questionnaires',
        'plans',
        'moments',
        'love_notes',
        'calendar',
        'achievements',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function build(User $user, string $filter): Collection
    {
        if (! in_array($filter, self::ALLOWED_FILTERS, true)) {
            $filter = 'all';
        }

        return collect()
            ->concat($this->questionnaireEntries($user, $filter))
            ->concat($this->planEntries($user, $filter))
            ->concat($this->momentEntries($user, $filter))
            ->concat($this->loveNoteEntries($user, $filter))
            ->concat($this->calendarEntries($user, $filter))
            ->concat($this->achievementEntries($user, $filter))
            ->sortByDesc('occurred_at')
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function questionnaireEntries(User $user, string $filter): Collection
    {
        if (! $this->wants($filter, 'questionnaires')) {
            return collect();
        }

        return QuestionnaireResponse::where('user_id', $user->id)
            ->where('status', CompletionStatus::Completed)
            ->with('questionnaire')
            ->latest()
            ->limit(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn (QuestionnaireResponse $response) => [
                'id' => 'response-'.$response->id,
                'type' => 'questionnaire',
                'title' => 'Completed: '.($response->questionnaire?->title ?? 'Questionnaire'),
                'description' => $response->questionnaire?->description,
                'emoji' => $response->questionnaire?->emoji ?? '📋',
                'occurred_at' => $response->updated_at->toISOString(),
                'link' => null,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function planEntries(User $user, string $filter): Collection
    {
        if (! $this->wants($filter, 'plans')) {
            return collect();
        }

        return DateNightPlan::where(function ($q) use ($user): void {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        })
            ->latest()
            ->limit(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn (DateNightPlan $plan) => [
                'id' => 'plan-'.$plan->id,
                'type' => 'plan',
                'title' => 'Date Night Plan: '.$plan->theme,
                'description' => $plan->summary,
                'emoji' => $plan->theme_emoji ?? '🌙',
                'occurred_at' => $plan->created_at->toISOString(),
                'link' => route('date-night.show', $plan),
                'compatibility_score' => $plan->compatibility_score,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function momentEntries(User $user, string $filter): Collection
    {
        if (! $this->wants($filter, 'moments')) {
            return collect();
        }

        return $user->moments()
            ->orderByDesc('date')
            ->limit(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn ($moment) => [
                'id' => 'moment-'.$moment->id,
                'type' => 'moment',
                'title' => $moment->title,
                'description' => $moment->description,
                'emoji' => '📖',
                'occurred_at' => $moment->date->toDateString(),
                'link' => null,
                'mood' => $moment->mood,
                'is_favourite' => $moment->is_favourite,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function loveNoteEntries(User $user, string $filter): Collection
    {
        if (! $this->wants($filter, 'love_notes')) {
            return collect();
        }

        return LoveNote::where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->latest()
            ->limit(self::PER_TYPE_LIMIT)
            ->get()
            ->map(function (LoveNote $note) use ($user): array {
                $isSender = $note->sender_id === $user->id;

                return [
                    'id' => 'note-'.$note->id,
                    'type' => 'love_note',
                    'title' => $isSender ? 'Love Note Sent 💌' : 'Love Note Received 💌',
                    'description' => $note->message,
                    'emoji' => '💌',
                    'occurred_at' => $note->created_at->toISOString(),
                    'link' => null,
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function calendarEntries(User $user, string $filter): Collection
    {
        if (! $this->wants($filter, 'calendar')) {
            return collect();
        }

        return $user->calendarEvents()
            ->orderByDesc('date')
            ->limit(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn ($event) => [
                'id' => 'event-'.$event->id,
                'type' => 'calendar',
                'title' => $event->title,
                'description' => $event->description,
                'emoji' => $event->emoji ?? '📅',
                'occurred_at' => $event->date->toDateString(),
                'link' => null,
                'colour' => $event->colour,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function achievementEntries(User $user, string $filter): Collection
    {
        if (! $this->wants($filter, 'achievements')) {
            return collect();
        }

        return $user->achievements()
            ->withPivot('unlocked_at')
            ->get()
            ->map(fn ($achievement) => [
                'id' => 'achievement-'.$achievement->id,
                'type' => 'achievement',
                'title' => 'Achievement Unlocked: '.$achievement->name,
                'description' => $achievement->description,
                'emoji' => $achievement->emoji,
                'occurred_at' => $achievement->pivot->unlocked_at,
                'link' => null,
                'points' => $achievement->points,
            ]);
    }

    private function wants(string $filter, string $type): bool
    {
        return $filter === 'all' || $filter === $type;
    }
}
