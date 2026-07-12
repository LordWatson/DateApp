<?php

namespace App\Http\Controllers;

use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Response as QuestionnaireResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filter = $request->get('filter', 'all');

        $entries = $this->buildTimeline($user, $filter);

        return Inertia::render('timeline/Index', [
            'entries' => $entries,
            'filter' => $filter,
            'filters' => [
                ['value' => 'all', 'label' => 'All'],
                ['value' => 'questionnaires', 'label' => 'Questionnaires'],
                ['value' => 'plans', 'label' => 'Plans'],
                ['value' => 'moments', 'label' => 'Moments'],
                ['value' => 'love_notes', 'label' => 'Love Notes'],
                ['value' => 'calendar', 'label' => 'Calendar'],
                ['value' => 'achievements', 'label' => 'Achievements'],
            ],
        ]);
    }

    private function buildTimeline(User $user, string $filter): Collection
    {
        $entries = collect();

        if (in_array($filter, ['all', 'questionnaires'])) {
            $responses = QuestionnaireResponse::where('user_id', $user->id)
                ->where('status', 'completed')
                ->with('questionnaire')
                ->latest()
                ->limit(20)
                ->get();

            foreach ($responses as $response) {
                $entries->push([
                    'id' => 'response-'.$response->id,
                    'type' => 'questionnaire',
                    'title' => 'Completed: '.($response->questionnaire?->title ?? 'Questionnaire'),
                    'description' => $response->questionnaire?->description,
                    'emoji' => $response->questionnaire?->emoji ?? '📋',
                    'occurred_at' => $response->updated_at->toISOString(),
                    'link' => null,
                ]);
            }
        }

        if (in_array($filter, ['all', 'plans'])) {
            $plans = DateNightPlan::where(function ($q) use ($user) {
                $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                    ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
            })->latest()->limit(20)->get();

            foreach ($plans as $plan) {
                $entries->push([
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
        }

        if (in_array($filter, ['all', 'moments'])) {
            $moments = $user->moments()->orderByDesc('date')->limit(20)->get();

            foreach ($moments as $moment) {
                $entries->push([
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
        }

        if (in_array($filter, ['all', 'love_notes'])) {
            $notes = LoveNote::where('sender_id', $user->id)
                ->orWhere('recipient_id', $user->id)
                ->latest()
                ->limit(20)
                ->get();

            foreach ($notes as $note) {
                $isSender = $note->sender_id === $user->id;
                $entries->push([
                    'id' => 'note-'.$note->id,
                    'type' => 'love_note',
                    'title' => $isSender ? 'Love Note Sent 💌' : 'Love Note Received 💌',
                    'description' => $note->message,
                    'emoji' => '💌',
                    'occurred_at' => $note->created_at->toISOString(),
                    'link' => null,
                ]);
            }
        }

        if (in_array($filter, ['all', 'calendar'])) {
            $events = $user->calendarEvents()->orderByDesc('date')->limit(20)->get();

            foreach ($events as $event) {
                $entries->push([
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
        }

        if (in_array($filter, ['all', 'achievements'])) {
            $userAchievements = $user->achievements()->withPivot('unlocked_at')->get();

            foreach ($userAchievements as $achievement) {
                $entries->push([
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
        }

        return $entries->sortByDesc('occurred_at')->values();
    }
}
