<?php

namespace App\Http\Controllers\Admin;

use App\Models\Challenge;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminChallengeController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(Request $request): Response
    {
        $query = Challenge::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->string('difficulty'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('archived')) {
            $query->where('archived', $request->boolean('archived'));
        } else {
            $query->where('archived', false);
        }

        $challenges = $query->orderBy('display_order')->paginate(20)->withQueryString();

        return Inertia::render('admin/challenges/Index', [
            'challenges' => $challenges,
            'filters' => $request->only(['search', 'difficulty', 'category', 'archived']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'difficulty' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'season' => ['nullable', 'string', 'max:50'],
            'weight' => ['integer', 'min:1', 'max:10'],
            'active' => ['boolean'],
            'display_order' => ['integer', 'min:0'],
        ]);

        $challenge = Challenge::create($validated);
        $this->audit->logModel('challenge.created', $challenge, null, $challenge->toArray());

        return back()->with('success', 'Challenge created.');
    }

    public function update(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'difficulty' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'season' => ['nullable', 'string', 'max:50'],
            'weight' => ['integer', 'min:1', 'max:10'],
            'active' => ['boolean'],
            'display_order' => ['integer', 'min:0'],
        ]);

        $before = $challenge->toArray();
        $challenge->update($validated);
        $this->audit->logModel('challenge.updated', $challenge, $before, $challenge->fresh()->toArray());

        return back()->with('success', 'Challenge updated.');
    }

    public function destroy(Challenge $challenge): RedirectResponse
    {
        $this->audit->logModel('challenge.deleted', $challenge, $challenge->toArray());
        $challenge->delete();

        return back()->with('success', 'Challenge deleted.');
    }

    public function archive(Challenge $challenge): RedirectResponse
    {
        $challenge->update(['archived' => true, 'active' => false]);
        $this->audit->logModel('challenge.archived', $challenge);

        return back()->with('success', 'Challenge archived.');
    }

    public function bulkImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'challenges' => ['required', 'array', 'min:1'],
            'challenges.*.title' => ['required', 'string', 'max:255'],
            'challenges.*.difficulty' => ['required', 'string'],
            'challenges.*.emoji' => ['nullable', 'string', 'max:10'],
            'challenges.*.category' => ['nullable', 'string'],
        ]);

        foreach ($validated['challenges'] as $data) {
            Challenge::create(array_merge($data, ['active' => true, 'weight' => 1, 'display_order' => 0]));
        }

        $this->audit->log('challenge.bulk_imported', null, null, null, ['count' => count($validated['challenges'])]);

        return back()->with('success', count($validated['challenges']).' challenges imported.');
    }
}
