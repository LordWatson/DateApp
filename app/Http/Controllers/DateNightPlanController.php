<?php

namespace App\Http\Controllers;

use App\Actions\LikeDateNightPlanAction;
use App\Models\DateNightPlan;
use App\Models\Response;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DateNightPlanController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function show(Request $request, DateNightPlan $dateNightPlan): InertiaResponse|RedirectResponse
    {
        $user = $request->user();

        $userResponseIds = Response::where('user_id', $user->id)
            ->pluck('id');

        $isParticipant = $userResponseIds->contains($dateNightPlan->partner_one_response_id)
            || $userResponseIds->contains($dateNightPlan->partner_two_response_id)
            || $dateNightPlan->partner_user_id === $user->id;

        if (! $isParticipant) {
            abort(403);
        }

        $dateNightPlan->loadMissing([
            'partnerOneResponse.user',
            'partnerTwoResponse.user',
            'questionnaire',
        ]);

        $partner = $user->partner;

        if ($partner) {
            $partnerResponseIds = Response::where('user_id', $partner->id)->pluck('id');
            $partnerIsViewer = $partnerResponseIds->contains($dateNightPlan->partner_one_response_id)
                || $partnerResponseIds->contains($dateNightPlan->partner_two_response_id);

            if ($partnerIsViewer) {
                $this->notificationService->notifyPartnerViewedPlan($partner, $dateNightPlan);
            }
        }

        return Inertia::render('date-night/Show', [
            'plan' => $this->formatPlan($dateNightPlan, $user),
        ]);
    }

    public function history(Request $request): InertiaResponse
    {
        $user = $request->user();

        $responseIds = Response::where('user_id', $user->id)->pluck('id');

        $userId = $user->id;
        $plans = DateNightPlan::with(['questionnaire'])
            ->where(function ($q) use ($responseIds, $userId) {
                $q->whereIn('partner_one_response_id', $responseIds)
                    ->orWhereIn('partner_two_response_id', $responseIds)
                    ->orWhere('partner_user_id', $userId);
            })
            ->orderByDesc('created_at')
            ->get();

        $search = $request->string('search')->toString();
        $themeFilter = $request->string('theme')->toString();
        $compatibilityFilter = $request->string('compatibility')->toString();

        if ($search) {
            $plans = $plans->filter(fn ($p) => str_contains(strtolower($p->theme), strtolower($search))
                || str_contains(strtolower($p->summary), strtolower($search)));
        }

        if ($themeFilter) {
            $plans = $plans->filter(fn ($p) => strtolower($p->theme) === strtolower($themeFilter));
        }

        if ($compatibilityFilter === 'high') {
            $plans = $plans->filter(fn ($p) => $p->compatibility_score >= 75);
        } elseif ($compatibilityFilter === 'medium') {
            $plans = $plans->filter(fn ($p) => $p->compatibility_score >= 50 && $p->compatibility_score < 75);
        } elseif ($compatibilityFilter === 'low') {
            $plans = $plans->filter(fn ($p) => $p->compatibility_score < 50);
        }

        return Inertia::render('date-night/History', [
            'plans' => $plans->values()->map(fn ($p) => $this->formatPlanSummary($p)),
            'filters' => [
                'search' => $search,
                'theme' => $themeFilter,
                'compatibility' => $compatibilityFilter,
            ],
        ]);
    }

    public function toggleFavourite(Request $request, DateNightPlan $dateNightPlan): RedirectResponse
    {
        $user = $request->user();

        $responseIds = Response::where('user_id', $user->id)->pluck('id');

        $isParticipant = $responseIds->contains($dateNightPlan->partner_one_response_id)
            || $responseIds->contains($dateNightPlan->partner_two_response_id)
            || $dateNightPlan->partner_user_id === $user->id;

        if (! $isParticipant) {
            abort(403);
        }

        $dateNightPlan->update(['is_favourite' => ! $dateNightPlan->is_favourite]);

        return back();
    }

    public function toggleLike(
        Request $request,
        DateNightPlan $dateNightPlan,
        LikeDateNightPlanAction $likePlan,
    ): RedirectResponse {
        $user = $request->user();

        $responseIds = Response::where('user_id', $user->id)->pluck('id');

        $isParticipant = $responseIds->contains($dateNightPlan->partner_one_response_id)
            || $responseIds->contains($dateNightPlan->partner_two_response_id)
            || $dateNightPlan->partner_user_id === $user->id;

        if (! $isParticipant) {
            abort(403);
        }

        $likePlan->execute($user, $dateNightPlan);

        return back();
    }

    public function favourites(Request $request): InertiaResponse
    {
        $user = $request->user();

        $responseIds = Response::where('user_id', $user->id)->pluck('id');

        $userId = $user->id;
        $plans = DateNightPlan::with(['questionnaire'])
            ->favourites()
            ->where(function ($q) use ($responseIds, $userId) {
                $q->whereIn('partner_one_response_id', $responseIds)
                    ->orWhereIn('partner_two_response_id', $responseIds)
                    ->orWhere('partner_user_id', $userId);
            })
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('date-night/Favourites', [
            'plans' => $plans->map(fn ($p) => $this->formatPlanSummary($p)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPlan(DateNightPlan $plan, User $viewer): array
    {
        $partnerOne = $plan->partnerOneResponse?->user;
        $partnerTwo = $plan->partnerTwoResponse?->user;

        $plan->loadCount('likedBy');
        $isLiked = $plan->isLikedBy($viewer);

        return [
            'id' => $plan->id,
            'theme' => $plan->theme,
            'theme_emoji' => $plan->theme_emoji,
            'compatibility_score' => $plan->compatibility_score,
            'summary' => $plan->summary,
            'meal_suggestion' => $plan->meal_suggestion,
            'drink_suggestion' => $plan->drink_suggestion,
            'music_vibe' => $plan->music_vibe,
            'atmosphere' => $plan->atmosphere,
            'activity' => $plan->activity,
            'conversation_prompt' => $plan->conversation_prompt,
            'romantic_challenge' => $plan->romantic_challenge,
            'is_solo' => $plan->is_solo,
            'location_label' => $plan->location_label,
            'local_suggestions' => $plan->local_suggestions ?? [],
            'is_favourite' => $plan->is_favourite,
            'is_liked' => $isLiked,
            'likes_count' => (int) ($plan->liked_by_count ?? 0),
            'created_at' => $plan->created_at?->toISOString(),
            'questionnaire' => [
                'id' => $plan->questionnaire?->id,
                'title' => $plan->questionnaire?->title,
                'slug' => $plan->questionnaire?->slug,
            ],
            'partner_one' => $partnerOne ? [
                'id' => $partnerOne->id,
                'name' => $partnerOne->display_name ?? $partnerOne->name,
                'avatar' => $partnerOne->avatar,
            ] : null,
            'partner_two' => $partnerTwo ? [
                'id' => $partnerTwo->id,
                'name' => $partnerTwo->display_name ?? $partnerTwo->name,
                'avatar' => $partnerTwo->avatar,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPlanSummary(DateNightPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'theme' => $plan->theme,
            'theme_emoji' => $plan->theme_emoji,
            'compatibility_score' => $plan->compatibility_score,
            'is_favourite' => $plan->is_favourite,
            'created_at' => $plan->created_at?->toISOString(),
            'questionnaire_title' => $plan->questionnaire?->title,
        ];
    }
}
