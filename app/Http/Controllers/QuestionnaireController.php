<?php

namespace App\Http\Controllers;

use App\Enums\CompletionStatus;
use App\Enums\QuestionnaireStatus;
use App\Http\Presenters\QuestionnairePresenter;
use App\Http\Requests\Questionnaire\FinishQuestionnaireRequest;
use App\Http\Requests\Questionnaire\SaveAnswerRequest;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response as QuestionnaireResponse;
use App\Models\User;
use App\Services\CompatibilityService;
use App\Services\QuestionnaireService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class QuestionnaireController extends Controller
{
    public function __construct(
        private readonly QuestionnaireService $questionnaireService,
        private readonly CompatibilityService $compatibilityService,
        private readonly QuestionnairePresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $filter = $this->normaliseIndexFilter((string) $request->query('filter', 'all'));

        $paginator = $this->buildIndexQuery($filter)->paginate(10)->withQueryString();

        $user = $request->user();
        $questionnaireIds = collect($paginator->items())->pluck('id');

        $latestResponses = $user->responses()
            ->whereIn('questionnaire_id', $questionnaireIds)
            ->orderByDesc('id')
            ->get()
            ->unique('questionnaire_id')
            ->keyBy('questionnaire_id');

        $pairedResponseIds = $this->pairedResponseIds($latestResponses);

        $paginator->getCollection()->transform(function (Questionnaire $q) use ($latestResponses, $pairedResponseIds) {
            $response = $latestResponses->get($q->id);
            $isPaired = $response !== null && $pairedResponseIds->contains($response->id);

            return [
                'id' => $q->id,
                'title' => $q->title,
                'slug' => $q->slug,
                'description' => $q->description,
                'emoji' => $q->emoji,
                'estimated_minutes' => $q->estimated_minutes,
                'question_count' => $q->questions_count,
                'is_solo' => $q->is_solo,
                'response_status' => $response?->status->value,
                'partner_paired' => $isPaired,
            ];
        });

        return Inertia::render('questionnaires/Index', [
            'questionnaires' => [
                'data' => $paginator->items(),
                'links' => $paginator->linkCollection()->toArray(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => ['filter' => $filter],
        ]);
    }

    public function show(Request $request, Questionnaire $questionnaire): Response|RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        if ($partner && $this->questionnaireService->shouldAutoStartNewCycle($user, $partner, $questionnaire)) {
            $this->questionnaireService->startNewResponse($user, $questionnaire);

            return redirect()->route('questionnaires.question', [$questionnaire->slug, 1]);
        }

        $response = $this->questionnaireService->getResponseWithAnswers($user, $questionnaire);
        $partnerCompleted = $partner
            ? $this->questionnaireService->partnerHasFreshCompletedResponse($user, $partner, $questionnaire)
            : false;

        return Inertia::render('questionnaires/Show', [
            'questionnaire' => $this->presenter->questionnaire($questionnaire),
            'response' => $this->presenter->responseSummary(
                $response,
                $response ? count($this->questionnaireService->getAnsweredQuestionIds($response)) : 0,
                $response ? $this->questionnaireService->getFirstUnansweredOrder($questionnaire, $response) : 1,
            ),
            'partner_completed' => $partnerCompleted,
            'current_streak' => $user->current_streak,
            'saved_profiles' => $this->presenter->savedProfiles($user),
            'past_attempts' => $this->buildPastAttempts($user, $questionnaire),
        ]);
    }

    public function restart(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $this->questionnaireService->startNewResponse($request->user(), $questionnaire);

        return redirect()->route('questionnaires.question', [$questionnaire->slug, 1]);
    }

    public function start(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $response = $this->questionnaireService->findOrCreateResponse($request->user(), $questionnaire);
        $order = $this->questionnaireService->getFirstUnansweredOrder($questionnaire, $response);

        return redirect()->route('questionnaires.question', [$questionnaire->slug, $order]);
    }

    public function question(Request $request, Questionnaire $questionnaire, int $order): Response|RedirectResponse
    {
        $user = $request->user();
        $questions = $questionnaire->questions()->with('options')->get();
        $question = $questions->firstWhere('display_order', $order);

        if (! $question) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        $response = $this->questionnaireService->findOrCreateResponse($user, $questionnaire);
        $answers = $this->questionnaireService->getAnswersForResponse($response);

        $questionAnswers = $answers->where('question_id', $question->id);
        $currentAnswer = $question->type->value === 'multiple_choice'
            ? $questionAnswers->pluck('value')->toArray()
            : $questionAnswers->first()?->value;

        $total = $questions->count();

        return Inertia::render('questionnaires/Question', [
            'questionnaire' => $this->presenter->questionnaireBrief($questionnaire),
            'question' => $this->presenter->question($question),
            'current_answer' => $currentAnswer,
            'progress' => $this->presenter->progress(
                $order,
                $total,
                $answers->pluck('question_id')->unique()->count(),
            ),
            'has_previous' => $order > 1,
            'has_next' => $order < $total,
            'is_last' => $order === $total,
        ]);
    }

    public function answer(SaveAnswerRequest $request, Questionnaire $questionnaire, int $order): JsonResponse
    {
        $user = $request->user();
        $question = $questionnaire->questions()->with('options')->where('display_order', $order)->firstOrFail();
        $response = $this->questionnaireService->findOrCreateResponse($user, $questionnaire);

        $this->questionnaireService->saveAnswer($response, $question, $request->input('value'));

        return response()->json(['saved' => true]);
    }

    public function complete(Request $request, Questionnaire $questionnaire): Response|RedirectResponse
    {
        $user = $request->user();
        $response = $this->questionnaireService->getResponseWithAnswers($user, $questionnaire);

        if (! $response) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        $partner = $user->partner;
        $partnerCompleted = $partner
            ? $this->questionnaireService->partnerHasFreshCompletedResponse($user, $partner, $questionnaire)
            : false;

        return Inertia::render('questionnaires/Complete', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
                'is_solo' => $questionnaire->is_solo,
            ],
            'completed_at' => $response->completed_at?->toISOString(),
            'current_streak' => $user->current_streak,
            'partner_completed' => $partnerCompleted,
            // Solo always produces a plan; otherwise wait until the partner has also completed.
            'awaiting_plan' => $questionnaire->is_solo || $partnerCompleted,
        ]);
    }

    public function planStatus(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        $response = $this->questionnaireService->getLatestCompletedResponse($request->user(), $questionnaire);

        if (! $response) {
            return response()->json(['ready' => false, 'plan_id' => null]);
        }

        $plan = DateNightPlan::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->where(function ($query) use ($response): void {
                $query->where('partner_one_response_id', $response->id)
                    ->orWhere('partner_two_response_id', $response->id);
            })
            ->latest('id')
            ->first();

        return response()->json([
            'ready' => $plan !== null,
            'plan_id' => $plan?->id,
        ]);
    }

    public function finish(FinishQuestionnaireRequest $request, Questionnaire $questionnaire): RedirectResponse
    {
        $response = $this->questionnaireService->getResponseWithAnswers($request->user(), $questionnaire);

        if ($response) {
            $this->questionnaireService->completeWithLocation($response, $request->validated());
        }

        return redirect()->route('questionnaires.complete', $questionnaire->slug);
    }

    public function saveLocation(FinishQuestionnaireRequest $request, Questionnaire $questionnaire): RedirectResponse
    {
        $response = $this->questionnaireService->getResponseWithAnswers($request->user(), $questionnaire);

        if ($response) {
            $this->questionnaireService->saveLocation($response, $request->validated());
        }

        return redirect()->route('questionnaires.summary', $questionnaire->slug);
    }

    public function location(Request $request, Questionnaire $questionnaire): Response|RedirectResponse
    {
        if (! $questionnaire->is_solo) {
            return redirect()->route('questionnaires.summary', $questionnaire->slug);
        }

        $response = $this->questionnaireService->getResponseWithAnswers($request->user(), $questionnaire);

        if (! $response) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        $questions = $questionnaire->questions()->get();
        $total = $questions->count();

        return Inertia::render('questionnaires/Location', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
                'is_solo' => $questionnaire->is_solo,
                'estimated_minutes' => $questionnaire->estimated_minutes,
            ],
            'progress' => $this->presenter->progress($total + 1, $total + 1, $total),
            'existing_location' => [
                'label' => $response->location_label,
                'city' => $response->location_city,
                'region' => $response->location_region,
                'country' => $response->location_country,
                'latitude' => $response->location_latitude !== null ? (float) $response->location_latitude : null,
                'longitude' => $response->location_longitude !== null ? (float) $response->location_longitude : null,
                'travel_radius_minutes' => $response->travel_radius_minutes,
            ],
        ]);
    }

    public function summary(Request $request, Questionnaire $questionnaire): Response|RedirectResponse
    {
        $user = $request->user();
        $response = $this->questionnaireService->getResponseWithAnswers($user, $questionnaire);

        if (! $response) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        $questions = $questionnaire->questions()->with('options')->get();
        $answers = $this->questionnaireService->getAnswersForResponse($response);

        return Inertia::render('questionnaires/Summary', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
                'is_solo' => $questionnaire->is_solo,
            ],
            'response_status' => $response->status->value,
            'grouped_answers' => $this->presenter->summaryGroupedAnswers($questions, $answers),
            'saved_profiles' => $this->presenter->savedProfiles($user),
        ]);
    }

    public function partnerAnswers(Request $request, Questionnaire $questionnaire): Response|RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        if (! $partner) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        $userResponse = $this->questionnaireService->getLatestCompletedResponse($user, $questionnaire);
        $partnerResponse = $this->questionnaireService->getLatestCompletedResponse($partner, $questionnaire);

        if (! $userResponse || ! $partnerResponse) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        if (! $this->questionnaireService->partnerHasFreshCompletedResponse($user, $partner, $questionnaire)) {
            return redirect()->route('questionnaires.complete', $questionnaire->slug);
        }

        return Inertia::render(
            'questionnaires/PartnerAnswers',
            $this->buildComparisonPayload($questionnaire, $userResponse, $partnerResponse, $partner)
        );
    }

    public function compatibility(Request $request, Questionnaire $questionnaire): Response|RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        $partnerCompleted = $partner
            ? $this->questionnaireService->partnerHasFreshCompletedResponse($user, $partner, $questionnaire)
            : false;

        return Inertia::render('questionnaires/Compatibility', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
            ],
            'partner_name' => $partner?->display_name ?? $partner?->name,
            'partner_completed' => $partnerCompleted,
            'compatibility' => $partnerCompleted ? $this->compatibilityService->calculate($user, $questionnaire) : null,
        ]);
    }

    public function pastAttempt(Request $request, Questionnaire $questionnaire, QuestionnaireResponse $response): Response|RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        if (! $partner
            || $response->user_id !== $user->id
            || $response->questionnaire_id !== $questionnaire->id
            || $response->status !== CompletionStatus::Completed
        ) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        $plan = DateNightPlan::where('questionnaire_id', $questionnaire->id)
            ->where(function ($query) use ($response): void {
                $query->where('partner_one_response_id', $response->id)
                    ->orWhere('partner_two_response_id', $response->id);
            })
            ->with(['partnerOneResponse.answers.questionOption', 'partnerTwoResponse.answers.questionOption'])
            ->first();

        if (! $plan) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        $partnerResponse = $plan->partnerOneResponse?->id === $response->id
            ? $plan->partnerTwoResponse
            : $plan->partnerOneResponse;

        if (! $partnerResponse || $partnerResponse->user_id !== $partner->id) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        return Inertia::render('questionnaires/PartnerAnswers', array_merge(
            $this->buildComparisonPayload($questionnaire, $response, $partnerResponse, $partner),
            [
                'completed_at' => $response->completed_at?->toISOString(),
                'is_past_attempt' => true,
            ],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildComparisonPayload(
        Questionnaire $questionnaire,
        QuestionnaireResponse $userResponse,
        QuestionnaireResponse $partnerResponse,
        User $partner,
    ): array {
        $userResponse->load('answers.question', 'answers.questionOption');
        $partnerResponse->load('answers.question', 'answers.questionOption');

        $questions = $questionnaire->questions()->with('options')->get();
        $userAnswers = $this->questionnaireService->getAnswersForResponse($userResponse);
        $partnerAnswers = $this->questionnaireService->getAnswersForResponse($partnerResponse);

        return [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
            ],
            'partner_name' => $partner->display_name ?? $partner->name,
            'grouped_answers' => $this->presenter->comparisonGroupedAnswers($questions, $userAnswers, $partnerAnswers),
        ];
    }

    private function normaliseIndexFilter(string $filter): string
    {
        return in_array($filter, ['all', 'couples', 'solo', 'intimacy', 'seasonal'], true) ? $filter : 'all';
    }

    private function buildIndexQuery(string $filter): Builder
    {
        $query = Questionnaire::query()
            ->where('status', QuestionnaireStatus::Active)
            ->withCount('questions')
            ->orderBy('display_order');

        match ($filter) {
            'solo' => $query->where('is_solo', true),
            'intimacy' => $query->where('is_intimacy', true),
            'seasonal' => $query->where('is_seasonal', true),
            'couples' => $query
                ->where('is_solo', false)
                ->where('is_intimacy', false)
                ->where('is_seasonal', false),
            default => null,
        };

        return $query;
    }

    /**
     * @param  Collection<int, QuestionnaireResponse>  $latestResponses
     * @return Collection<int, int>
     */
    private function pairedResponseIds(Collection $latestResponses): Collection
    {
        $completedResponseIds = $latestResponses
            ->filter(fn ($response) => $response->status === CompletionStatus::Completed)
            ->pluck('id');

        if ($completedResponseIds->isEmpty()) {
            return collect();
        }

        return DateNightPlan::query()
            ->where(function ($query) use ($completedResponseIds): void {
                $query->whereIn('partner_one_response_id', $completedResponseIds)
                    ->orWhereIn('partner_two_response_id', $completedResponseIds);
            })
            ->get(['partner_one_response_id', 'partner_two_response_id'])
            ->flatMap(fn ($plan) => [$plan->partner_one_response_id, $plan->partner_two_response_id])
            ->intersect($completedResponseIds)
            ->unique()
            ->values();
    }

    /**
     * @return array<int, array{id:int, completed_at:string|null}>
     */
    private function buildPastAttempts(User $user, Questionnaire $questionnaire): array
    {
        $partner = $user->partner;

        if (! $partner) {
            return [];
        }

        $userResponseIds = QuestionnaireResponse::where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->pluck('id');

        if ($userResponseIds->isEmpty()) {
            return [];
        }

        return DateNightPlan::where('questionnaire_id', $questionnaire->id)
            ->where(function ($query) use ($userResponseIds): void {
                $query->whereIn('partner_one_response_id', $userResponseIds)
                    ->orWhereIn('partner_two_response_id', $userResponseIds);
            })
            ->with(['partnerOneResponse', 'partnerTwoResponse'])
            ->get()
            ->map(function (DateNightPlan $plan) use ($user): ?array {
                $userResponse = $plan->partnerOneResponse?->user_id === $user->id
                    ? $plan->partnerOneResponse
                    : $plan->partnerTwoResponse;

                if (! $userResponse || $userResponse->user_id !== $user->id) {
                    return null;
                }

                return [
                    'id' => $userResponse->id,
                    'completed_at' => $userResponse->completed_at?->toISOString(),
                ];
            })
            ->filter()
            ->sortByDesc('completed_at')
            ->values()
            ->all();
    }
}
