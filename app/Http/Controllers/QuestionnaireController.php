<?php

namespace App\Http\Controllers;

use App\Enums\CompletionStatus;
use App\Enums\QuestionnaireStatus;
use App\Http\Requests\Questionnaire\FinishQuestionnaireRequest;
use App\Http\Requests\Questionnaire\SaveAnswerRequest;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response as QuestionnaireResponse;
use App\Models\User;
use App\Services\CompatibilityService;
use App\Services\QuestionnaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionnaireController extends Controller
{
    public function __construct(
        private readonly QuestionnaireService $questionnaireService,
        private readonly CompatibilityService $compatibilityService,
    ) {}

    public function index(Request $request): Response
    {
        $filter = (string) $request->query('filter', 'all');
        $allowedFilters = ['all', 'couples', 'solo', 'intimacy', 'seasonal'];

        if (! in_array($filter, $allowedFilters, true)) {
            $filter = 'all';
        }

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

        $paginator = $query
            ->paginate(10)
            ->withQueryString();

        $user = $request->user();
        $responses = $user->responses()
            ->whereIn('questionnaire_id', collect($paginator->items())->pluck('id'))
            ->get()
            ->keyBy('questionnaire_id');

        $paginator->getCollection()->transform(fn (Questionnaire $q) => [
            'id' => $q->id,
            'title' => $q->title,
            'slug' => $q->slug,
            'description' => $q->description,
            'emoji' => $q->emoji,
            'estimated_minutes' => $q->estimated_minutes,
            'question_count' => $q->questions_count,
            'response_status' => $responses->get($q->id)?->status->value,
        ]);

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
            'filters' => [
                'filter' => $filter,
            ],
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
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
                'description' => $questionnaire->description,
                'emoji' => $questionnaire->emoji,
                'estimated_minutes' => $questionnaire->estimated_minutes,
                'question_count' => $questionnaire->questions()->count(),
                'is_solo' => $questionnaire->is_solo,
            ],
            'response' => $response ? [
                'status' => $response->status->value,
                'answered_count' => count($this->questionnaireService->getAnsweredQuestionIds($response)),
                'resume_order' => $this->questionnaireService->getFirstUnansweredOrder($questionnaire, $response),
            ] : null,
            'partner_completed' => $partnerCompleted,
            'current_streak' => $user->current_streak,
            'saved_profiles' => $user->savedProfiles->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'emoji' => $p->emoji,
                'colour' => $p->colour,
            ]),
            'past_attempts' => $this->buildPastAttempts($user, $questionnaire),
        ]);
    }

    public function restart(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $user = $request->user();
        $this->questionnaireService->startNewResponse($user, $questionnaire);

        return redirect()->route('questionnaires.question', [$questionnaire->slug, 1]);
    }

    /**
     * @return array<int, array{id:int, completed_at:string|null, partner_completed:bool}>
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

        $plans = DateNightPlan::where('questionnaire_id', $questionnaire->id)
            ->where(function ($query) use ($userResponseIds): void {
                $query->whereIn('partner_one_response_id', $userResponseIds)
                    ->orWhereIn('partner_two_response_id', $userResponseIds);
            })
            ->with(['partnerOneResponse', 'partnerTwoResponse'])
            ->get();

        return $plans
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

    public function start(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $user = $request->user();
        $response = $this->questionnaireService->findOrCreateResponse($user, $questionnaire);

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

        return Inertia::render('questionnaires/Question', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
                'estimated_minutes' => $questionnaire->estimated_minutes,
            ],
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'description' => $question->description,
                'emoji' => $question->emoji,
                'type' => $question->type->value,
                'required' => $question->required,
                'minimum_value' => $question->minimum_value,
                'maximum_value' => $question->maximum_value,
                'display_order' => $question->display_order,
                'options' => $question->options->map(fn ($o) => [
                    'id' => $o->id,
                    'title' => $o->title,
                    'description' => $o->description,
                    'emoji' => $o->emoji,
                    'value' => $o->value,
                ]),
            ],
            'current_answer' => $currentAnswer,
            'progress' => [
                'current' => $order,
                'total' => $questions->count(),
                'percentage' => (int) round(($order / $questions->count()) * 100),
                'answered_count' => $answers->pluck('question_id')->unique()->count(),
            ],
            'has_previous' => $order > 1,
            'has_next' => $order < $questions->count(),
            'is_last' => $order === $questions->count(),
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

        // A date-night plan will be generated whenever we have all the responses
        // required by the pipeline: always for solo, otherwise only once the
        // partner has also completed the current cycle.
        $awaitingPlan = $questionnaire->is_solo || $partnerCompleted;

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
            'awaiting_plan' => $awaitingPlan,
        ]);
    }

    public function planStatus(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        $user = $request->user();
        $response = $this->questionnaireService->getLatestCompletedResponse($user, $questionnaire);

        if (! $response) {
            return response()->json([
                'ready' => false,
                'plan_id' => null,
            ]);
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
        $user = $request->user();
        $response = $this->questionnaireService->getResponseWithAnswers($user, $questionnaire);

        if ($response && $response->status !== CompletionStatus::Completed) {
            if ($questionnaire->is_solo) {
                $response->fill([
                    'location_label' => $request->input('location_label'),
                    'location_city' => $request->input('location_city'),
                    'location_region' => $request->input('location_region'),
                    'location_country' => $request->input('location_country'),
                ])->save();
            }

            $this->questionnaireService->completeResponse($response);
        }

        return redirect()->route('questionnaires.complete', $questionnaire->slug);
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

        $grouped = $questions->map(function ($question) use ($answers) {
            $questionAnswers = $answers->where('question_id', $question->id);

            return [
                'question' => [
                    'id' => $question->id,
                    'title' => $question->title,
                    'emoji' => $question->emoji,
                    'type' => $question->type->value,
                    'display_order' => $question->display_order,
                    'options' => $question->options->map(fn ($o) => [
                        'id' => $o->id,
                        'title' => $o->title,
                        'emoji' => $o->emoji,
                        'value' => $o->value,
                    ]),
                ],
                'answers' => $questionAnswers->map(fn ($a) => [
                    'value' => $a->value,
                    'option_title' => $a->questionOption?->title,
                    'option_emoji' => $a->questionOption?->emoji,
                ])->values(),
            ];
        });

        return Inertia::render('questionnaires/Summary', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
                'is_solo' => $questionnaire->is_solo,
            ],
            'response_status' => $response->status->value,
            'grouped_answers' => $grouped,
            'saved_profiles' => $user->savedProfiles->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'emoji' => $p->emoji,
                'colour' => $p->colour,
            ]),
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

        $userResponse->load('answers.question', 'answers.questionOption');
        $partnerResponse->load('answers.question', 'answers.questionOption');

        $questions = $questionnaire->questions()->with('options')->get();
        $userAnswers = $this->questionnaireService->getAnswersForResponse($userResponse);
        $partnerAnswers = $this->questionnaireService->getAnswersForResponse($partnerResponse);

        $grouped = $questions->map(function ($question) use ($userAnswers, $partnerAnswers) {
            $mapAnswer = fn ($answers) => $answers->where('question_id', $question->id)->map(fn ($a) => [
                'value' => $a->value,
                'option_title' => $a->questionOption?->title,
                'option_emoji' => $a->questionOption?->emoji,
            ])->values();

            return [
                'question' => [
                    'id' => $question->id,
                    'title' => $question->title,
                    'emoji' => $question->emoji,
                    'type' => $question->type->value,
                    'display_order' => $question->display_order,
                ],
                'my_answers' => $mapAnswer($userAnswers),
                'partner_answers' => $mapAnswer($partnerAnswers),
            ];
        });

        return Inertia::render('questionnaires/PartnerAnswers', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
            ],
            'partner_name' => $partner->display_name ?? $partner->name,
            'grouped_answers' => $grouped,
        ]);
    }

    public function compatibility(Request $request, Questionnaire $questionnaire): Response|RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        $partnerCompleted = $partner
            ? $this->questionnaireService->partnerHasFreshCompletedResponse($user, $partner, $questionnaire)
            : false;

        $compatibility = $partnerCompleted
            ? $this->compatibilityService->calculate($user, $questionnaire)
            : null;

        return Inertia::render('questionnaires/Compatibility', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
            ],
            'partner_name' => $partner?->display_name ?? $partner?->name,
            'partner_completed' => $partnerCompleted,
            'compatibility' => $compatibility,
        ]);
    }

    public function pastAttempt(Request $request, Questionnaire $questionnaire, QuestionnaireResponse $response): Response|RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        if (! $partner || $response->user_id !== $user->id || $response->questionnaire_id !== $questionnaire->id) {
            return redirect()->route('questionnaires.show', $questionnaire->slug);
        }

        if ($response->status !== CompletionStatus::Completed) {
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

        $response->load('answers.question', 'answers.questionOption');
        $partnerResponse->load('answers.question', 'answers.questionOption');

        $questions = $questionnaire->questions()->with('options')->get();
        $userAnswers = $this->questionnaireService->getAnswersForResponse($response);
        $partnerAnswers = $this->questionnaireService->getAnswersForResponse($partnerResponse);

        $grouped = $questions->map(function ($question) use ($userAnswers, $partnerAnswers) {
            $mapAnswer = fn ($answers) => $answers->where('question_id', $question->id)->map(fn ($a) => [
                'value' => $a->value,
                'option_title' => $a->questionOption?->title,
                'option_emoji' => $a->questionOption?->emoji,
            ])->values();

            return [
                'question' => [
                    'id' => $question->id,
                    'title' => $question->title,
                    'emoji' => $question->emoji,
                    'type' => $question->type->value,
                    'display_order' => $question->display_order,
                ],
                'my_answers' => $mapAnswer($userAnswers),
                'partner_answers' => $mapAnswer($partnerAnswers),
            ];
        });

        return Inertia::render('questionnaires/PartnerAnswers', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
            ],
            'partner_name' => $partner->display_name ?? $partner->name,
            'grouped_answers' => $grouped,
            'completed_at' => $response->completed_at?->toISOString(),
            'is_past_attempt' => true,
        ]);
    }
}
