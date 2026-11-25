<?php

namespace App\Http\Controllers\Admin;

use App\Models\SystemSetting;
use App\Services\AdminAuditService;
use App\Services\RuntimeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSystemSettingController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(): Response
    {
        // The ai.* keys are edited, with validation, on the AI settings page.
        $settings = SystemSetting::where('group', '!=', 'ai')->orderBy('group')->orderBy('label')->get();

        return Inertia::render('admin/settings/Index', [
            'settings' => $settings->groupBy('group'),
            // The page labels every other setting as "not in use".
            'inUse' => RuntimeSettings::SETTINGS_IN_USE,
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
            $setting = SystemSetting::whereKey($item['id'])->firstOrFail();
            $before = ['value' => $setting->value];
            $setting->update(['value' => $item['value']]);
            $this->audit->logModel('setting.updated', $setting, $before, ['value' => $item['value']]);
        }

        return back()->with('success', 'Settings saved.');
    }
}
