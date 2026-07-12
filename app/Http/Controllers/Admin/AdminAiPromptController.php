<?php

namespace App\Http\Controllers\Admin;

use App\Models\AiPrompt;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAiPromptController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(Request $request): Response
    {
        $query = AiPrompt::query();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $prompts = $query->orderBy('type')->orderBy('label')->get();

        return Inertia::render('admin/ai-prompts/Index', [
            'prompts' => $prompts->groupBy('type'),
            'filters' => $request->only(['type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'unique:ai_prompts,key'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'content' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]);

        $prompt = AiPrompt::create($validated);
        $this->audit->logModel('ai_prompt.created', $prompt, null, $prompt->toArray());

        return back()->with('success', 'AI prompt created.');
    }

    public function update(Request $request, AiPrompt $aiPrompt): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]);

        $before = $aiPrompt->only(['content', 'active', 'version']);
        $validated['version'] = $aiPrompt->version + 1;
        $aiPrompt->update($validated);
        $this->audit->logModel('ai_prompt.updated', $aiPrompt, $before, $aiPrompt->fresh()->only(['content', 'active', 'version']));

        return back()->with('success', 'AI prompt updated.');
    }

    public function destroy(AiPrompt $aiPrompt): RedirectResponse
    {
        $this->audit->logModel('ai_prompt.deleted', $aiPrompt, $aiPrompt->toArray());
        $aiPrompt->delete();

        return back()->with('success', 'AI prompt deleted.');
    }
}
