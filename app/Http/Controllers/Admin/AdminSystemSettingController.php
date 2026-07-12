<?php

namespace App\Http\Controllers\Admin;

use App\Models\SystemSetting;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSystemSettingController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(): Response
    {
        $settings = SystemSetting::orderBy('group')->orderBy('label')->get();

        return Inertia::render('admin/settings/Index', [
            'settings' => $settings->groupBy('group'),
        ]);
    }

    public function update(Request $request, SystemSetting $systemSetting): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['nullable', 'string'],
        ]);

        $before = ['value' => $systemSetting->value];
        $systemSetting->update($validated);
        $this->audit->logModel('setting.updated', $systemSetting, $before, ['value' => $systemSetting->value]);

        return back()->with('success', "Setting '{$systemSetting->label}' updated.");
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.id' => ['required', 'exists:system_settings,id'],
            'settings.*.value' => ['nullable', 'string'],
        ]);

        foreach ($validated['settings'] as $item) {
            $setting = SystemSetting::find($item['id']);
            $before = ['value' => $setting->value];
            $setting->update(['value' => $item['value']]);
            $this->audit->logModel('setting.updated', $setting, $before, ['value' => $item['value']]);
        }

        return back()->with('success', 'Settings saved.');
    }
}
