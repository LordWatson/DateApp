<?php

namespace App\Http\Controllers\Admin;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'string'],
            'visibility' => ['required', 'string'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'display_order' => ['required', 'integer', 'min:0'],
            'is_seasonal' => ['boolean'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date'],
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        $questionnaire = Questionnaire::create($validated);
        $this->audit->logModel('questionnaire.created', $questionnaire, null, $questionnaire->toArray());

        return redirect()->route('admin.questionnaires.edit', $questionnaire)->with('success', 'Questionnaire created.');
    }

    public function edit(Questionnaire $questionnaire): Response
    {
        $questionnaire->load(['questions.options']);

        return Inertia::render('admin/questionnaires/Edit', [
            'questionnaire' => $questionnaire,
        ]);
    }

    public function update(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'string'],
            'visibility' => ['required', 'string'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'display_order' => ['required', 'integer', 'min:0'],
            'is_seasonal' => ['boolean'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date'],
        ]);

        $before = $questionnaire->toArray();
        $questionnaire->update($validated);
        $this->audit->logModel('questionnaire.updated', $questionnaire, $before, $questionnaire->fresh()->toArray());

        return back()->with('success', 'Questionnaire updated.');
    }

    public function destroy(Questionnaire $questionnaire): RedirectResponse
    {
        $this->audit->logModel('questionnaire.deleted', $questionnaire, $questionnaire->toArray());
        $questionnaire->delete();

        return redirect()->route('admin.questionnaires.index')->with('success', 'Questionnaire deleted.');
    }

    public function duplicate(Questionnaire $questionnaire): RedirectResponse
    {
        $new = $questionnaire->replicate();
        $new->title = $questionnaire->title.' (Copy)';
        $new->slug = Str::slug($new->title).'-'.Str::random(4);
        $new->status = 'draft';
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

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'exists:questions,id'],
            'order.*.display_order' => ['required', 'integer'],
        ]);

        foreach ($validated['order'] as $item) {
            Question::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        return back()->with('success', 'Questions reordered.');
    }
}
