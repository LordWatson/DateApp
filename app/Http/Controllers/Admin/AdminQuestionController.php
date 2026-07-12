<?php

namespace App\Http\Controllers\Admin;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminQuestionController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function store(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'type' => ['required', 'string'],
            'display_order' => ['required', 'integer', 'min:0'],
            'required' => ['boolean'],
            'min_value' => ['nullable', 'integer'],
            'max_value' => ['nullable', 'integer'],
        ]);

        $question = $questionnaire->questions()->create($validated);
        $this->audit->logModel('question.created', $question, null, $question->toArray());

        return back()->with('success', 'Question created.');
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'type' => ['required', 'string'],
            'display_order' => ['required', 'integer', 'min:0'],
            'required' => ['boolean'],
            'min_value' => ['nullable', 'integer'],
            'max_value' => ['nullable', 'integer'],
        ]);

        $before = $question->toArray();
        $question->update($validated);
        $this->audit->logModel('question.updated', $question, $before, $question->fresh()->toArray());

        return back()->with('success', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->audit->logModel('question.deleted', $question, $question->toArray());
        $question->delete();

        return back()->with('success', 'Question deleted.');
    }
}
