<?php

namespace App\Http\Controllers;

use App\Actions\AddDateNightPlanToCalendarAction;
use App\Actions\LikeDateNightPlanAction;
use App\Http\Presenters\DateNightPlanPresenter;
use App\Http\Requests\DateNightPlan\AddToCalendarRequest;
use App\Models\DateNightPlan;
use App\Models\Response;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DateNightPlanController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly DateNightPlanPresenter $presenter,
    ) {}

    public function show(Request $request, DateNightPlan $dateNightPlan): InertiaResponse|RedirectResponse
    {
        $user = $request->user();
        $this->authorize('view', $dateNightPlan);

        $dateNightPlan->loadMissing([
            'partnerOneResponse.user',
            'partnerTwoResponse.user',
            'questionnaire',
        ]);

        $partner = $user->partner;

        if ($partner && $partner->can('view', $dateNightPlan)) {
            $this->notificationService->notifyPartnerViewedPlan($partner, $dateNightPlan);
        }

        return Inertia::render('date-night/Show', [
            'plan' => $this->presenter->detail($dateNightPlan, $user),
        ]);
    }

    public function history(Request $request): InertiaResponse
    {
        $user = $request->user();

        $responseIds = Response::where('user_id', $user->id)->pluck('id');

        $plans = DateNightPlan::with(['questionnaire'])
            ->where(function ($q) use ($responseIds, $user) {
                $q->whereIn('partner_one_response_id', $responseIds)
                    ->orWhereIn('partner_two_response_id', $responseIds)
                    ->orWhere('partner_user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get();

        $search = $request->string('search')->toString();
        $themeFilter = $request->string('theme')->toString();
        $compatibilityFilter = $request->string('compatibility')->toString();

        $plans = $this->applyHistoryFilters($plans, $search, $themeFilter, $compatibilityFilter);

        return Inertia::render('date-night/History', [
            'plans' => $plans->values()->map(fn ($p) => $this->presenter->summary($p)),
            'filters' => [
                'search' => $search,
                'theme' => $themeFilter,
                'compatibility' => $compatibilityFilter,
            ],
        ]);
    }

    public function toggleFavourite(Request $request, DateNightPlan $dateNightPlan): RedirectResponse
    {
        $this->authorize('favourite', $dateNightPlan);

        $dateNightPlan->update(['is_favourite' => ! $dateNightPlan->is_favourite]);

        return back();
    }

    public function toggleLike(
        Request $request,
        DateNightPlan $dateNightPlan,
        LikeDateNightPlanAction $likePlan,
    ): RedirectResponse {
        $user = $request->user();
        $this->authorize('like', $dateNightPlan);

        $likePlan->execute($user, $dateNightPlan);

        return back();
    }

    public function addToCalendar(
        AddToCalendarRequest $request,
        DateNightPlan $dateNightPlan,
        AddDateNightPlanToCalendarAction $addToCalendar,
    ): RedirectResponse {
        $user = $request->user();
        $this->authorize('addToCalendar', $dateNightPlan);

        $addToCalendar->execute($user, $dateNightPlan, $request->validated());

        return back()->with('success', 'Added to your calendar!');
    }

    public function favourites(Request $request): InertiaResponse
    {
        $user = $request->user();
        $responseIds = Response::where('user_id', $user->id)->pluck('id');

        $plans = DateNightPlan::with(['questionnaire'])
            ->favourites()
            ->where(function ($q) use ($responseIds, $user) {
                $q->whereIn('partner_one_response_id', $responseIds)
                    ->orWhereIn('partner_two_response_id', $responseIds)
                    ->orWhere('partner_user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('date-night/Favourites', [
            'plans' => $plans->map(fn ($p) => $this->presenter->summary($p)),
        ]);
    }

    /**
     * @param  Collection<int, DateNightPlan>  $plans
     * @return Collection<int, DateNightPlan>
     */
    private function applyHistoryFilters(
        Collection $plans,
        string $search,
        string $theme,
        string $compatibility,
    ): Collection {
        if ($search !== '') {
            $needle = strtolower($search);
            $plans = $plans->filter(fn (DateNightPlan $p) => str_contains(strtolower((string) $p->theme), $needle)
                || str_contains(strtolower((string) $p->summary), $needle));
        }

        if ($theme !== '') {
            $plans = $plans->filter(fn (DateNightPlan $p) => strtolower((string) $p->theme) === strtolower($theme));
        }

        return match ($compatibility) {
            'high' => $plans->filter(fn (DateNightPlan $p) => $p->compatibility_score >= 75),
            'medium' => $plans->filter(fn (DateNightPlan $p) => $p->compatibility_score >= 50 && $p->compatibility_score < 75),
            'low' => $plans->filter(fn (DateNightPlan $p) => $p->compatibility_score < 50),
            default => $plans,
        };
    }
}
