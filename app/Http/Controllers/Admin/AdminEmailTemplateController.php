<?php

namespace App\Http\Controllers\Admin;

use App\Models\EmailTemplate;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminEmailTemplateController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(): Response
    {
        $templates = EmailTemplate::orderBy('label')->get();

        return Inertia::render('admin/email-templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        return Inertia::render('admin/email-templates/Edit', [
            'template' => $emailTemplate,
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]);

        $before = $emailTemplate->only(['subject', 'html_body', 'active']);
        $emailTemplate->update($validated);
        $this->audit->logModel('email_template.updated', $emailTemplate, $before, $emailTemplate->fresh()->only(['subject', 'active']));

        return back()->with('success', 'Email template updated.');
    }
}
