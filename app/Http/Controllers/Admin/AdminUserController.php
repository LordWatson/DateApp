<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(Request $request): Response
    {
        $query = User::with(['role', 'partner'])
            ->withCount(['responses', 'moments']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            );
        }

        if ($request->filled('filter')) {
            match ($request->string('filter')->toString()) {
                'active' => $query->where('is_suspended', false),
                'suspended' => $query->where('is_suspended', true),
                'connected' => $query->whereNotNull('partner_id'),
                'waiting' => $query->whereNull('partner_id')->where('onboarding_completed', true),
                'admins' => $query->whereNotNull('role_id'),
                default => null,
            };
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'roles' => Role::all(),
            'filters' => $request->only(['search', 'filter']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['role', 'partner', 'responses.questionnaire', 'moments', 'achievements']);

        return Inertia::render('admin/users/Show', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);

        $before = $user->only(['name', 'email', 'role_id']);
        $user->update($validated);
        $this->audit->logModel('user.updated', $user, $before, $user->fresh()->only(['name', 'email', 'role_id']));

        return back()->with('success', 'User updated successfully.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $validated['reason'] ?? null,
        ]);

        $this->audit->logModel('user.suspended', $user);

        return back()->with('success', 'User suspended.');
    }

    public function unsuspend(User $user): RedirectResponse
    {
        $user->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        $this->audit->logModel('user.unsuspended', $user);

        return back()->with('success', 'User unsuspended.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->audit->logModel('user.deleted', $user, $user->toArray());
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function disconnect(User $user): RedirectResponse
    {
        $partner = $user->partner;

        $user->update(['partner_id' => null]);
        $partner?->update(['partner_id' => null]);

        $this->audit->logModel('user.disconnected', $user);

        return back()->with('success', 'Users disconnected.');
    }
}
