<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While an administrator is viewing the app as a user: nothing can be changed,
 * the account's security pages are closed, and the session ends by itself.
 */
class RestrictImpersonation
{
    /**
     * Pages that write (or call the AI) when merely opened, and pages that
     * belong to the account holder alone.
     */
    private const CLOSED_ROUTES = [
        'security.edit',
        'data-export.download',
        'weekly-reflection.index',
        'two-factor.*',
        'passkey.*',
        'password.confirm',
        'password.confirmation',
    ];

    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->impersonation->isActive() || $request->routeIs('impersonation.stop', 'logout')) {
            return $next($request);
        }

        if ($this->impersonation->hasExpired()) {
            $target = $this->impersonation->stop();

            return $target
                ? redirect()->route('admin.users.show', $target)->with('error', 'Viewing as a user ended after '.ImpersonationService::MINUTES.' minutes.')
                : redirect()->route('login');
        }

        if (! $request->isMethodSafe() || $request->routeIs(...self::CLOSED_ROUTES)) {
            $message = 'Not available while viewing as a user: this is read-only.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 403)
                : back()->with('error', $message);
        }

        return $next($request);
    }
}
