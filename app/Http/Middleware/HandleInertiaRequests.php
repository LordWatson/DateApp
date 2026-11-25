<?php

namespace App\Http\Middleware;

use App\Models\AppNotification;
use App\Services\RuntimeSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                // An explicit list: the user row also holds role, suspension and date of birth.
                'user' => $request->user() ? [
                    ...$request->user()->only([
                        'id', 'name', 'display_name', 'email', 'avatar', 'email_verified_at', 'timezone', 'partner_id', 'onboarding_completed',
                    ]),
                    // true = agreed, false = declined, null = not asked yet.
                    'ai_consent' => $request->user()->aiConsent(),
                ] : null,
                'can' => $request->user()?->isAdmin() ? [
                    'manageUsers' => $request->user()->can('manage-users'),
                    'manageContent' => $request->user()->can('manage-content'),
                    'viewAnalytics' => $request->user()->can('view-analytics'),
                    'superAdmin' => $request->user()->can('super-admin'),
                ] : null,
            ],
            'unreadNotificationsCount' => $request->user()
                ? AppNotification::where('user_id', $request->user()->id)
                    ->whereNull('read_at')
                    ->count()
                : 0,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'registrationOpen' => app(RuntimeSettings::class)->flag('registration'),
        ];
    }
}
