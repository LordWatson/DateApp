<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToPendingInvitation
{
    /**
     * Someone who opened an invitation link while logged out and then logged in
     * (password, two-factor or passkey) is taken to the invitation, once.
     * Registration consumes the same session token itself in CreateNewUser.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->isMethod('GET')
            && ! $request->expectsJson()
            && $request->hasSession()
            && $request->session()->has('invitation_token')
            && $request->user()
        ) {
            return redirect()->route('onboarding.accept-invite', [
                'token' => $request->session()->pull('invitation_token'),
            ]);
        }

        return $next($request);
    }
}
