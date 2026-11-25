<?php

namespace App\Http\Controllers\Admin;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\AdminAuditService;
use App\Services\RuleEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminOptionController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function store(Request $request, Question $question): RedirectResponse
    {
        $validated = $this->validated($request);

        $option = $question->options()->create($validated);
        $this->audit->logModel('option.created', $option, null, $option->toArray());

        return back()->with('success', 'Option created.');
    }

    public function update(Request $request, QuestionOption $option): RedirectResponse
    {
        $validated = $this->validated($request);

        $before = $option->toArray();
        $option->update($validated);
        $this->audit->logModel('option.updated', $option, $before, $option->fresh()->toArray());

        return back()->with('success', 'Option updated.');
    }

    public function destroy(QuestionOption $option): RedirectResponse
    {
        $this->audit->logModel('option.deleted', $option, $option->toArray());
        $option->delete();

        return back()->with('success', 'Option deleted.');
    }

    public function duplicate(QuestionOption $option): RedirectResponse
    {
        $new = $option->replicate();
        $new->title = $option->title.' (Copy)';
        $new->display_order = $option->display_order + 1;
        $new->save();

        $this->audit->logModel('option.duplicated', $new);

        return back()->with('success', 'Option duplicated.');
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
            'value' => ['required', 'string'],
            'display_order' => ['required', 'integer', 'min:0'],
            'signals' => ['nullable', 'array'],
            'signals.*' => ['string', 'distinct', Rule::in(array_keys(RuleEngineService::SIGNALS))],
        ]);
    }
}
