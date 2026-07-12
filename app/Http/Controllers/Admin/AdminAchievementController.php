<?php

namespace App\Http\Controllers\Admin;

use App\Models\Achievement;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAchievementController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(Request $request): Response
    {
        $query = Achievement::withCount('users');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $achievements = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('admin/achievements/Index', [
            'achievements' => $achievements,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'emoji' => ['required', 'string', 'max:10'],
            'artwork' => ['nullable', 'string'],
            'category' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:0'],
            'hidden' => ['boolean'],
            'unlock_condition' => ['required', 'string'],
            'unlock_value' => ['required', 'integer', 'min:0'],
        ]);

        $achievement = Achievement::create($validated);
        $this->audit->logModel('achievement.created', $achievement, null, $achievement->toArray());

        return back()->with('success', 'Achievement created.');
    }

    public function update(Request $request, Achievement $achievement): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'emoji' => ['required', 'string', 'max:10'],
            'artwork' => ['nullable', 'string'],
            'category' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:0'],
            'hidden' => ['boolean'],
            'unlock_condition' => ['required', 'string'],
            'unlock_value' => ['required', 'integer', 'min:0'],
        ]);

        $before = $achievement->toArray();
        $achievement->update($validated);
        $this->audit->logModel('achievement.updated', $achievement, $before, $achievement->fresh()->toArray());

        return back()->with('success', 'Achievement updated.');
    }

    public function destroy(Achievement $achievement): RedirectResponse
    {
        $this->audit->logModel('achievement.deleted', $achievement, $achievement->toArray());
        $achievement->delete();

        return back()->with('success', 'Achievement deleted.');
    }
}
