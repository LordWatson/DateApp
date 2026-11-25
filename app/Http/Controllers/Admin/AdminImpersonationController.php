<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminImpersonationController extends AdminController
{
    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    /**
     * See the app as this user sees it. Super administrators only (route middleware).
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403, 'You are already signed in as yourself.');
        abort_if($user->isAdmin(), 403, 'An administrator account cannot be viewed as.');
        abort_if($this->impersonation->isActive(), 403, 'Stop viewing as the current user first.');

        $this->impersonation->start($request->user(), $user);

        return redirect()->route('dashboard');
    }

    public function stop(): RedirectResponse
    {
        abort_unless($this->impersonation->isActive(), 403);

        $target = $this->impersonation->stop();

        return $target
            ? redirect()->route('admin.users.show', $target)->with('success', 'You are back in your own account.')
            : redirect()->route('login');
    }
}
