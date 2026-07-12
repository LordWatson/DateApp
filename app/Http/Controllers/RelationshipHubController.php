<?php

namespace App\Http\Controllers;

use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Services\AchievementService;
use App\Services\InsightsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RelationshipHubController extends Controller
{
    public function __construct(
        private readonly AchievementService $achievementService,
        private readonly InsightsService $insightsService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('partner');
        $partner = $user->partner;

        $upcomingEvents = $user->calendarEvents()
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->limit(3)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'emoji' => $e->emoji ?? '📅',
                'date' => $e->date->toDateString(),
                'colour' => $e->colour,
            ]);

        $recentMoments = $user->moments()
            ->orderByDesc('date')
            ->limit(3)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'mood' => $m->mood,
                'date' => $m->date->toDateString(),
                'is_favourite' => $m->is_favourite,
            ]);

        $achievementProgress = $this->achievementService->getUserProgress($user);

        $averageCompatibility = round(
            DateNightPlan::where(function ($q) use ($user) {
                $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                    ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
            })->avg('compatibility_score') ?? 0
        );

        $seasonalQuestionnaires = Questionnaire::where('is_seasonal', true)
            ->where('status', 'published')
            ->orderBy('display_order')
            ->limit(3)
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'emoji' => $q->emoji,
                'slug' => $q->slug,
                'description' => $q->description,
            ]);

        return Inertia::render('relationship-hub/Index', [
            'partner' => $partner ? [
                'id' => $partner->id,
                'name' => $partner->name,
                'avatar' => $partner->avatar,
            ] : null,
            'stats' => [
                'current_streak' => $user->current_streak,
                'longest_streak' => $user->longest_streak,
                'average_compatibility' => $averageCompatibility,
            ],
            'upcoming_events' => $upcomingEvents,
            'recent_moments' => $recentMoments,
            'achievement_progress' => $achievementProgress,
            'seasonal_questionnaires' => $seasonalQuestionnaires,
        ]);
    }
}
