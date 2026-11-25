<?php

namespace App\Http\Controllers;

use App\Enums\QuestionnaireStatus;
use App\Models\CalendarEvent;
use App\Models\DateNightPlan;
use App\Models\Moment;
use App\Models\Questionnaire;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RelationshipHubController extends Controller
{
    public function __construct(
        private readonly AchievementService $achievementService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('partner');
        $partner = $user->partner;

        $partnerIds = $partner ? [$user->id, $partner->id] : [$user->id];

        $today = $user->today();

        // Repeating events are always upcoming, whatever year they were entered in.
        $upcomingEvents = CalendarEvent::whereIn('user_id', $partnerIds)
            ->where(fn ($query) => $query
                ->where('date', '>=', $today->toDateString())
                ->orWhereNotNull('recurrence'))
            ->get()
            ->map(fn (CalendarEvent $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'emoji' => $e->emoji ?? '📅',
                'date' => $e->nextOccurrence($today)->toDateString(),
                'colour' => $e->colour,
            ])
            ->sortBy('date')
            ->take(3)
            ->values();

        $momentsCount = Moment::whereIn('user_id', $partnerIds)->count();

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

        $averageCompatibility = round((float) (
            DateNightPlan::where(function ($q) use ($user) {
                $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                    ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
            })->notHiddenFor($user)->avg('compatibility_score') ?? 0)
        );

        $seasonalQuestionnaires = Questionnaire::where('is_seasonal', true)
            ->where('status', QuestionnaireStatus::Active)
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
            'moments_count' => $momentsCount,
            'achievement_progress' => $achievementProgress,
            'seasonal_questionnaires' => $seasonalQuestionnaires,
        ]);
    }
}
