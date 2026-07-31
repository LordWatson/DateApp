<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreAiPromptTemplateRequest;
use App\Http\Requests\Admin\UpdateAiPromptTemplateRequest;
use App\Models\AiPromptTemplate;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminAiPromptController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(): Response
    {
        $templates = AiPromptTemplate::query()
            ->orderBy('name')
            ->orderByDesc('version')
            ->get();

        return Inertia::render('admin/ai-prompts/Index', [
            'templates' => $templates,
        ]);
    }

    public function store(StoreAiPromptTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['version'] = 1;
        $data['active'] = $data['active'] ?? true;

        $template = AiPromptTemplate::create($data);
        $this->audit->logModel('ai_prompt_template.created', $template, null, $template->toArray());

        return back()->with('success', 'AI prompt template created.');
    }

    public function update(UpdateAiPromptTemplateRequest $request, AiPromptTemplate $aiPromptTemplate): RedirectResponse
    {
        $data = $request->validated();

        $before = $aiPromptTemplate->only(['system_prompt', 'user_prompt_template', 'description', 'active', 'max_tokens', 'version']);
        $data['version'] = $aiPromptTemplate->version + 1;
        $aiPromptTemplate->update($data);
        $this->audit->logModel(
            'ai_prompt_template.updated',
            $aiPromptTemplate,
            $before,
            $aiPromptTemplate->fresh()->only(['system_prompt', 'user_prompt_template', 'description', 'active', 'max_tokens', 'version'])
        );

        return back()->with('success', 'AI prompt template updated.');
    }

    public function destroy(AiPromptTemplate $aiPromptTemplate): RedirectResponse
    {
        $this->audit->logModel('ai_prompt_template.deleted', $aiPromptTemplate, $aiPromptTemplate->toArray());
        $aiPromptTemplate->delete();

        return back()->with('success', 'AI prompt template deleted.');
    }
}
