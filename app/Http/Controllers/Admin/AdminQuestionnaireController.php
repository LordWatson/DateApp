<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Services\AdminAuditService;
use App\Services\RuleEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminQuestionnaireController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(Request $request): Response
    {
        $query = Questionnaire::withCount('questions', 'responses');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $questionnaires = $query->orderBy('display_order')->paginate(20)->withQueryString();

        return Inertia::render('admin/questionnaires/Index', [
            'questionnaires' => $questionnaires,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/questionnaires/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['slug'] = $this->uniqueSlug($validated['title']);

        $questionnaire = Questionnaire::create($validated);
        $this->audit->logModel('questionnaire.created', $questionnaire, null, $questionnaire->toArray());

        return redirect()->route('admin.questionnaires.edit', $questionnaire)->with('success', 'Questionnaire created.');
    }

    public function edit(Questionnaire $questionnaire): Response
    {
        $questionnaire->load(['questions.options']);

        return Inertia::render('admin/questionnaires/Edit', [
            'questionnaire' => $questionnaire,
            'signalOptions' => array_keys(RuleEngineService::SIGNALS),
        ]);
    }

    public function update(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $validated = $this->validated($request);

        $before = $questionnaire->toArray();
        $questionnaire->update($validated);
        $this->audit->logModel('questionnaire.updated', $questionnaire, $before, $questionnaire->fresh()->toArray());

        return back()->with('success', 'Questionnaire updated.');
    }

    public function destroy(Questionnaire $questionnaire): RedirectResponse
    {
        // Deleting would cascade through every user's answers and date night plans.
        if ($questionnaire->responses()->exists()) {
            $questionnaire->update(['status' => QuestionnaireStatus::Archived]);
            $this->audit->logModel('questionnaire.archived', $questionnaire);

            return redirect()->route('admin.questionnaires.index')
                ->with('success', 'People have answered this questionnaire, so it was archived instead of deleted.');
        }

        $this->audit->logModel('questionnaire.deleted', $questionnaire, $questionnaire->toArray());
        $questionnaire->delete();

        return redirect()->route('admin.questionnaires.index')->with('success', 'Questionnaire deleted.');
    }

    public function duplicate(Questionnaire $questionnaire): RedirectResponse
    {
        $new = $questionnaire->replicate();
        $new->title = $questionnaire->title.' (Copy)';
        $new->slug = Str::slug($new->title).'-'.Str::random(4);
        $new->status = QuestionnaireStatus::Draft;
        $new->save();

        foreach ($questionnaire->questions()->with('options')->get() as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->questionnaire_id = $new->id;
            $newQuestion->save();

            foreach ($question->options as $option) {
                $newOption = $option->replicate();
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }
        }

        $this->audit->logModel('questionnaire.duplicated', $new);

        return redirect()->route('admin.questionnaires.edit', $new)->with('success', 'Questionnaire duplicated.');
    }

    /**
     * Saves a new order for questions (the default) or, with type=options, for options.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $isOptions = $request->input('type') === 'options';

        $validated = $request->validate([
            'type' => ['sometimes', Rule::in(['questions', 'options'])],
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer', $isOptions ? 'exists:question_options,id' : 'exists:questions,id'],
            'order.*.display_order' => ['required', 'integer', 'min:0'],
        ]);

        $model = $isOptions ? QuestionOption::class : Question::class;

        foreach ($validated['order'] as $item) {
            $model::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        $this->audit->log(
            action: $isOptions ? 'options.reordered' : 'questions.reordered',
            auditableType: $model,
            after: ['order' => $validated['order']],
        );

        return back()->with('success', $isOptions ? 'Options reordered.' : 'Questions reordered.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'status' => ['required', Rule::enum(QuestionnaireStatus::class)],
            'visibility' => ['required', Rule::enum(QuestionnaireVisibility::class)],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'display_order' => ['required', 'integer', 'min:0'],
            'is_seasonal' => ['boolean'],
            'is_solo' => ['boolean'],
            'is_intimacy' => ['boolean'],
            'partner_optional' => ['boolean'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
        ]);

        // "Alone or with your partner" is a choice offered on a solo questionnaire only.
        if (array_key_exists('is_solo', $validated) && ! $validated['is_solo']) {
            $validated['partner_optional'] = false;
        }

        return $validated;
    }

    /**
     * The slug column is unique and two questionnaires may share a title.
     */
    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'questionnaire';
        $slug = $base;

        for ($suffix = 2; Questionnaire::where('slug', $slug)->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
