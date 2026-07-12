<?php

namespace App\Http\Controllers\Admin;

use App\Models\FeatureFlag;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminFeatureFlagController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(): Response
    {
        $flags = FeatureFlag::orderBy('group')->orderBy('label')->get();

        return Inertia::render('admin/feature-flags/Index', [
            'flags' => $flags->groupBy('group'),
        ]);
    }

    public function update(Request $request, FeatureFlag $featureFlag): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $before = ['enabled' => $featureFlag->enabled];
        $featureFlag->update($validated);
        $this->audit->logModel('feature_flag.updated', $featureFlag, $before, ['enabled' => $featureFlag->enabled]);

        return back()->with('success', "Feature flag '{$featureFlag->label}' updated.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'unique:feature_flags,key'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'enabled' => ['boolean'],
            'group' => ['required', 'string', 'max:100'],
        ]);

        $flag = FeatureFlag::create($validated);
        $this->audit->logModel('feature_flag.created', $flag, null, $flag->toArray());

        return back()->with('success', 'Feature flag created.');
    }

    public function destroy(FeatureFlag $featureFlag): RedirectResponse
    {
        $this->audit->logModel('feature_flag.deleted', $featureFlag, $featureFlag->toArray());
        $featureFlag->delete();

        return back()->with('success', 'Feature flag deleted.');
    }
}
