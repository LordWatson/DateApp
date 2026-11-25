<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuestionnaireStatus;
use App\Models\Achievement;
use App\Models\CalendarEvent;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Moment;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Performs the site-wide search across the user's plans, moments,
 * love notes, questionnaires, saved profiles, calendar events and
 * achievements. Returns a shape ready for the search Inertia page.
 */
final class GlobalSearchService
{
    private const PER_TYPE_LIMIT = 5;

    private const MIN_QUERY_LENGTH = 2;

    /**
     * @return array<string, Collection<int, array{id: int, title: string, description: string, emoji: string, url: string}>|Collection<int, array{id: int, title: string, description: string|null, emoji: string, url: string}>|Collection<int, array{id: int, title: string, description: null, emoji: string, url: string}>>
     */
    public function search(User $user, string $query): array
    {
        $query = trim($query);

        if (strlen($query) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        return array_filter([
            'plans' => $this->plans($user, $query),
            'moments' => $this->moments($user, $query),
            'love_notes' => $this->loveNotes($user, $query),
            'questionnaires' => $this->questionnaires($query),
            'saved_profiles' => $this->savedProfiles($user, $query),
            'calendar_events' => $this->calendarEvents($user, $query),
            'achievements' => $this->achievements($query),
        ], fn ($collection) => $collection !== null);
    }

    /**
     * @return Collection<int, array{id: int, title: string, description: string, emoji: string, url: string}>|null
     */
    private function plans(User $user, string $query): ?Collection
    {
        $plans = DateNightPlan::where(function ($q) use ($user): void {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        })
            ->notHiddenFor($user)
            ->where(fn ($q) => $q->where('theme', 'like', "%{$query}%")->orWhere('summary', 'like', "%{$query}%"))
            ->limit(self::PER_TYPE_LIMIT)
            ->get();

        if ($plans->isEmpty()) {
            return null;
        }

        return $plans->map(fn (DateNightPlan $p) => [
            'id' => $p->id,
            'title' => $p->theme,
            'description' => $p->summary,
            'emoji' => $p->theme_emoji ?? '🌙',
            'url' => route('date-night.show', $p),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, description: string|null, emoji: string, url: string}>|null
     */
    private function moments(User $user, string $query): ?Collection
    {
        $moments = Moment::whereIn('user_id', $this->coupleIds($user))
            ->where(fn ($q) => $q->where('title', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(self::PER_TYPE_LIMIT)
            ->get();

        if ($moments->isEmpty()) {
            return null;
        }

        return $moments->map(fn ($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'description' => $m->description,
            'emoji' => '📖',
            'url' => route('moments.show', $m),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, description: string, emoji: string, url: string}>|null
     */
    private function loveNotes(User $user, string $query): ?Collection
    {
        $notes = LoveNote::visibleTo($user)
            ->where('message', 'like', "%{$query}%")
            ->limit(self::PER_TYPE_LIMIT)
            ->get();

        if ($notes->isEmpty()) {
            return null;
        }

        return $notes->map(fn (LoveNote $n) => [
            'id' => $n->id,
            'title' => 'Love Note',
            'description' => $n->message,
            'emoji' => '💌',
            'url' => route('love-notes.show', $n),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, description: string|null, emoji: string, url: string}>|null
     */
    private function questionnaires(string $query): ?Collection
    {
        $questionnaires = Questionnaire::where('status', QuestionnaireStatus::Active)
            ->where(fn ($q) => $q->where('title', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(self::PER_TYPE_LIMIT)
            ->get();

        if ($questionnaires->isEmpty()) {
            return null;
        }

        return $questionnaires->map(fn (Questionnaire $q) => [
            'id' => $q->id,
            'title' => $q->title,
            'description' => $q->description,
            'emoji' => $q->emoji ?? '📋',
            'url' => route('questionnaires.show', $q),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, description: null, emoji: string, url: string}>|null
     */
    private function savedProfiles(User $user, string $query): ?Collection
    {
        $profiles = $user->savedProfiles()
            ->where('name', 'like', "%{$query}%")
            ->limit(self::PER_TYPE_LIMIT)
            ->get();

        if ($profiles->isEmpty()) {
            return null;
        }

        return $profiles->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->name,
            'description' => null,
            'emoji' => $p->emoji ?? '💾',
            'url' => route('saved-profiles.index'),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, description: string|null, emoji: string, url: string}>|null
     */
    private function calendarEvents(User $user, string $query): ?Collection
    {
        $events = CalendarEvent::whereIn('user_id', $this->coupleIds($user))
            ->where(fn ($q) => $q->where('title', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(self::PER_TYPE_LIMIT)
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        return $events->map(fn ($e) => [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'emoji' => $e->emoji ?? '📅',
            'url' => route('calendar.show', $e),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, description: string, emoji: string, url: string}>|null
     */
    private function achievements(string $query): ?Collection
    {
        $achievements = Achievement::where('hidden', false)
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(self::PER_TYPE_LIMIT)
            ->get();

        if ($achievements->isEmpty()) {
            return null;
        }

        return $achievements->map(fn (Achievement $a) => [
            'id' => $a->id,
            'title' => $a->name,
            'description' => $a->description,
            'emoji' => $a->emoji,
            'url' => route('achievements.index'),
        ]);
    }

    /**
     * The user and their partner: moments and calendar events are shared between the two.
     *
     * @return list<int>
     */
    private function coupleIds(User $user): array
    {
        return array_values(array_filter([$user->id, $user->partner_id]));
    }
}
