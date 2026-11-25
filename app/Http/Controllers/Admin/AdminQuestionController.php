<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminQuestionController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function store(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        $validated = $this->validated($request);

        $question = $questionnaire->questions()->create($validated);
        $this->audit->logModel('question.created', $question, null, $question->toArray());

        return back()->with('success', 'Question created.');
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $this->validated($request);

        $before = $question->toArray();
        $question->update($validated);
        $this->audit->logModel('question.updated', $question, $before, $question->fresh()->toArray());

        return back()->with('success', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        // Deleting would cascade through the answers people have already given.
        if ($question->answers()->exists()) {
            return back()->with('error', 'This question already has answers and cannot be deleted.');
        }

        $this->audit->logModel('question.deleted', $question, $question->toArray());
        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'display_order' => ['required', 'integer', 'min:0'],
            'required' => ['boolean'],
            'minimum_value' => ['nullable', 'integer'],
            'maximum_value' => ['nullable', 'integer'],
            'step_value' => ['nullable', 'integer', 'min:1'],
            'unit' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
