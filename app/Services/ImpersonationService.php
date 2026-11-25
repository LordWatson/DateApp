<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "View as user": a super administrator browses the app as someone else,
 * read-only and for a limited time. The session keeps who they really are.
 */
class ImpersonationService
{
    public const MINUTES = 30;

    private const ADMIN_KEY = 'impersonator_id';

    private const STARTED_KEY = 'impersonation_started_at';

    public function __construct(
        private readonly Request $request,
        private readonly AdminAuditService $audit,
    ) {}

    public function isActive(): bool
    {
        return $this->request->hasSession() && $this->request->session()->has(self::ADMIN_KEY);
    }

    public function hasExpired(): bool
    {
        $startedAt = (int) $this->request->session()->get(self::STARTED_KEY, 0);

        return now()->getTimestamp() - $startedAt > self::MINUTES * 60;
    }

    public function start(User $admin, User $target): void
    {
        // Logged while still the administrator, so the entry carries their id.
        $this->audit->logModel('user.view_as.started', $target);

        Auth::guard('web')->login($target);

        $this->request->session()->put(self::ADMIN_KEY, $admin->id);
        $this->request->session()->put(self::STARTED_KEY, now()->getTimestamp());
        // The administrator confirming their own password says nothing about this account.
        $this->request->session()->forget('auth.password_confirmed_at');
    }

    /**
     * Become the administrator again.
     *
     * @return User|null The person who was being viewed, or null when the administrator no longer exists.
     */
    public function stop(): ?User
    {
        $target = $this->request->user();
        $admin = User::whereKey($this->request->session()->pull(self::ADMIN_KEY))->first();
        $this->request->session()->forget(self::STARTED_KEY);

        if (! $admin) {
            Auth::guard('web')->logout();

            return null;
        }

        Auth::guard('web')->login($admin);

        if ($target) {
            $this->audit->logModel('user.view_as.stopped', $target);
        }

        return $target;
    }
}
