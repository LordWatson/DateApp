<?php

namespace App\Http\Controllers;

use App\Enums\CompletionStatus;
use App\Enums\QuestionnaireStatus;
use App\Http\Requests\Questionnaire\SaveAnswerRequest;
use App\Models\Questionnaire;
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
        $questionnaires = Questionnaire::where('status', QuestionnaireStatus::Active)
            ->orderBy('display_order')
            ->get();

        $user = $request->user();
        $responses = $user->responses()->whereIn('questionnaire_id', $questionnaires->pluck('id'))->get()->keyBy('questionnaire_id');

        return Inertia::render('questionnaires/Index', [
            'questionnaires' => $questionnaires->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'slug' => $q->slug,
                'description' => $q->description,
                'emoji' => $q->emoji,
                'estimated_minutes' => $q->estimated_minutes,
                'question_count' => $q->questions()->count(),
                'response_status' => $responses->get($q->id)?->status->value,
            ]),
        ]);
    }

    public function show(Request $request, Questionnaire $questionnaire): Response
    {
        $user = $request->user();
        $response = $this->questionnaireService->getResponseWithAnswers($user, $questionnaire);
        $partner = $user->partner;
        $partnerResponse = $partner
            ? $this->questionnaireService->getResponseWithAnswers($partner, $questionnaire)
            : null;

        return Inertia::render('questionnaires/Show', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
                'description' => $questionnaire->description,
                'emoji' => $questionnaire->emoji,
                'estimated_minutes' => $questionnaire->estimated_minutes,
                'question_count' => $questionnaire->questions()->count(),
            ],
            'response' => $response ? [
                'status' => $response->status->value,
                'answered_count' => count($this->questionnaireService->getAnsweredQuestionIds($response)),
                'resume_order' => $this->questionnaireService->getFirstUnansweredOrder($questionnaire, $response),
            ] : null,
            'partner_completed' => $partnerResponse?->status === CompletionStatus::Completed,
            'current_streak' => $user->current_streak,
            'saved_profiles' => $user->savedProfiles->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'emoji' => $p->emoji,
                'colour' => $p->colour,
            ]),
        ]);
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
        $partnerResponse = $partner
            ? $this->questionnaireService->getResponseWithAnswers($partner, $questionnaire)
            : null;

        return Inertia::render('questionnaires/Complete', [
            'questionnaire' => [
                'id' => $questionnaire->id,
                'title' => $questionnaire->title,
                'slug' => $questionnaire->slug,
            ],
            'completed_at' => $response->completed_at?->toISOString(),
            'current_streak' => $user->current_streak,
            'partner_completed' => $partnerResponse?->status === CompletionStatus::Completed,
        ]);
    }

    public function finish(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $user = $request->user();
        $response = $this->questionnaireService->getResponseWithAnswers($user, $questionnaire);

        if ($response && $response->status !== CompletionStatus::Completed) {
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

    public function compatibility(Request $request, Questionnaire $questionnaire): Response|RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;

        $partnerResponse = $partner
            ? $this->questionnaireService->getResponseWithAnswers($partner, $questionnaire)
            : null;

        $partnerCompleted = $partnerResponse?->status === CompletionStatus::Completed;

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
}
