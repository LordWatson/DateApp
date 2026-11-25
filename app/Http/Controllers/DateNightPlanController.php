<?php

namespace App\Http\Controllers;

use App\Actions\AddDateNightPlanToCalendarAction;
use App\Actions\LikeDateNightPlanAction;
use App\Actions\RetryDateNightPlanWithAiAction;
use App\Http\Presenters\DateNightPlanPresenter;
use App\Http\Presenters\QuestionnairePresenter;
use App\Http\Requests\DateNightPlan\AddToCalendarRequest;
use App\Models\DateNightPlan;
use App\Models\Response;
use App\Services\CompatibilityService;
use App\Services\NotificationService;
use App\Services\QuestionnaireService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $dateNightPlan->loadExists(['favouritedBy as is_favourite' => fn ($query) => $query->whereKey($user->id)]);

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

        $search = $request->string('search')->toString();
        $themeFilter = $request->string('theme')->toString();
        $compatibilityFilter = $request->string('compatibility')->toString();

        $paginator = DateNightPlan::with(['questionnaire'])
            ->visibleTo($user)
            ->withFavouriteFor($user)
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('theme', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")))
            ->when($themeFilter !== '', fn ($query) => $query->where('theme', $themeFilter))
            // Solo plans have no score to compare, so a compatibility filter leaves them out.
            ->when(in_array($compatibilityFilter, ['high', 'medium', 'low'], true), fn ($query) => $query
                ->where('is_solo', false)
                ->whereBetween('compatibility_score', match ($compatibilityFilter) {
                    'high' => [75, 100],
                    'medium' => [50, 74],
                    default => [0, 49],
                }))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('date-night/History', [
            'plans' => [
                'data' => collect($paginator->items())->map(fn (DateNightPlan $plan) => $this->presenter->summary($plan)),
                'links' => $paginator->linkCollection()->toArray(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'last_page' => $paginator->lastPage(),
            ],
            'themes' => DateNightPlan::query()->visibleTo($user)->distinct()->orderBy('theme')->pluck('theme'),
            'filters' => [
                'search' => $search,
                'theme' => $themeFilter,
                'compatibility' => $compatibilityFilter,
            ],
        ]);
    }

    /**
     * Favourites are per person: this never changes what the partner has saved.
     */
    public function toggleFavourite(Request $request, DateNightPlan $dateNightPlan): RedirectResponse
    {
        $this->authorize('favourite', $dateNightPlan);

        $dateNightPlan->favouritedBy()->toggle($request->user()->id);

        return back();
    }

    /**
     * Side-by-side answers for the two responses behind a couple plan.
     */
    public function answers(
        Request $request,
        DateNightPlan $dateNightPlan,
        QuestionnaireService $questionnaireService,
        QuestionnairePresenter $questionnairePresenter,
    ): InertiaResponse {
        [$mine, $theirs] = $this->comparisonResponses($request, $dateNightPlan);
        $questionnaire = $dateNightPlan->questionnaire;

        return Inertia::render('questionnaires/PartnerAnswers', [
            'questionnaire' => $questionnaire->only(['id', 'title', 'slug']),
            'partner_name' => $theirs->user->display_name ?? $theirs->user->name,
            'grouped_answers' => $questionnairePresenter->comparisonGroupedAnswers(
                $questionnaire->questions()->with('options')->get(),
                $questionnaireService->getAnswersForResponse($mine),
                $questionnaireService->getAnswersForResponse($theirs),
            ),
            'plan_id' => $dateNightPlan->id,
        ]);
    }

    /**
     * Match score and matched/different lists for the two responses behind a couple plan.
     */
    public function compatibility(
        Request $request,
        DateNightPlan $dateNightPlan,
        CompatibilityService $compatibilityService,
    ): InertiaResponse {
        [$mine, $theirs] = $this->comparisonResponses($request, $dateNightPlan);
        $questionnaire = $dateNightPlan->questionnaire;

        return Inertia::render('questionnaires/Compatibility', [
            'questionnaire' => $questionnaire->only(['id', 'title', 'slug']),
            'partner_name' => $theirs->user->display_name ?? $theirs->user->name,
            'partner_completed' => true,
            'compatibility' => $compatibilityService->calculate($request->user(), $questionnaire, $mine, $theirs),
            'plan_id' => $dateNightPlan->id,
        ]);
    }

    public function retryAi(
        DateNightPlan $dateNightPlan,
        RetryDateNightPlanWithAiAction $retryWithAi,
    ): RedirectResponse {
        $this->authorize('update', $dateNightPlan);

        if (! $dateNightPlan->fallback_used) {
            return back()->with('error', 'This plan has already been written with AI.');
        }

        return $retryWithAi->execute($dateNightPlan)
            ? back()->with('success', 'Your plan has been rewritten with AI.')
            : back()->with('error', 'AI is still unavailable, so your plan is unchanged. Please try again later.');
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

        $addToCalendar->execute($user, $dateNightPlan, [
            'date' => $request->string('date')->toString(),
            'time' => $request->filled('time') ? $request->string('time')->toString() : null,
            'location' => $request->filled('location') ? $request->string('location')->toString() : null,
        ]);

        return back()->with('success', 'Added to your calendar!');
    }

    public function favourites(Request $request): InertiaResponse
    {
        $user = $request->user();

        $plans = DateNightPlan::with(['questionnaire'])
            ->visibleTo($user)
            ->favouritedBy($user)
            ->withFavouriteFor($user)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('date-night/Favourites', [
            'plans' => $plans->map(fn ($p) => $this->presenter->summary($p)),
        ]);
    }

    /**
     * The viewer's response first, the other person's second. Solo plans have nothing to compare.
     *
     * @return array{0: Response, 1: Response}
     */
    private function comparisonResponses(Request $request, DateNightPlan $plan): array
    {
        $this->authorize('view', $plan);
        abort_if($plan->is_solo, 404);

        $plan->loadMissing(['questionnaire', 'partnerOneResponse.user', 'partnerTwoResponse.user']);

        $one = $plan->partnerOneResponse;
        $two = $plan->partnerTwoResponse;

        return $two->user_id === $request->user()->id ? [$two, $one] : [$one, $two];
    }
}
