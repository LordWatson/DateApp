<?php

namespace App\Http\Middleware;

use App\Services\RuntimeSettings;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * While the `maintenance_mode` feature flag is on, everyone except admins gets the
 * maintenance page. Signing in and out keep working so an admin can get in to switch it off.
 */
class EnsureNotInMaintenance
{
    /** Paths that stay reachable: sign-in (with its 2FA and passkey steps), sign-out, health checks, legal pages. */
    private const ALLOWED = ['login', 'logout', 'two-factor-challenge', 'passkeys/*', 'up', 'health', 'privacy', 'terms'];

    public function __construct(private readonly RuntimeSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $this->settings->flag('maintenance_mode')
            || $request->user()?->isAdmin()
            || $request->is(...self::ALLOWED)
        ) {
            return $next($request);
        }

        if ($request->expectsJson() && ! $request->inertia()) {
            return response()->json(['message' => 'Date Night is down for maintenance. Please try again soon.'], 503);
        }

        return Inertia::render('Maintenance')->toResponse($request)->setStatusCode(503);
    }
}
