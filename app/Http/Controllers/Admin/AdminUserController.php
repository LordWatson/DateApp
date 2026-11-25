<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteAccountAction;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends AdminController
{
    public function __construct(
        private readonly AdminAuditService $audit,
        private readonly DeleteAccountAction $deleteAccount,
    ) {}

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

        $this->guardTarget($request->user(), $user);

        $newRoleId = isset($validated['role_id']) ? (int) $validated['role_id'] : null;

        if ($newRoleId !== $user->role_id) {
            // Only a super administrator hands out roles, and never to themselves.
            Gate::authorize('super-admin');
            abort_if($user->is($request->user()), 403, 'You cannot change your own role.');
            abort_if($this->isLastSuperAdmin($user), 403, 'The last super administrator cannot be demoted.');
        }

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

        $this->guardTarget($request->user(), $user, allowSelf: false);

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

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardTarget($request->user(), $user, allowSelf: false);

        // Enough to identify the event later, and nothing personal: the account is gone.
        $this->audit->logModel('user.deleted', $user, $user->only(['id', 'role_id', 'created_at', 'email_verified_at']));
        $this->deleteAccount->execute($user);

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

    /**
     * Rules that hold for every change to a user, whatever the actor's role.
     */
    private function guardTarget(User $actor, User $target, bool $allowSelf = true): void
    {
        abort_if(! $allowSelf && $target->is($actor), 403, 'You cannot do this to your own account.');
        abort_if($target->isSuperAdmin() && ! $actor->isSuperAdmin(), 403, 'Only a super administrator can change a super administrator.');
        abort_if(! $allowSelf && $this->isLastSuperAdmin($target), 403, 'The last super administrator cannot be removed or suspended.');
    }

    private function isLastSuperAdmin(User $user): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        return User::whereHas('role', fn ($query) => $query->where('name', UserRole::SuperAdministrator->value))->count() <= 1;
    }
}
