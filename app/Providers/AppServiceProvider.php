<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerGates();
        $this->registerRateLimiters();
    }

    protected function registerRateLimiters(): void
    {
        $perUser = static fn (Request $request): string => (string) ($request->user()->id ?? $request->ip());

        // Each invite or resend sends an email to an address the user typed.
        RateLimiter::for('invites', fn (Request $request) => Limit::perDay(10)->by($perUser($request)));

        // Each of these costs one AI request.
        RateLimiter::for('ai', fn (Request $request) => Limit::perHour(20)->by($perUser($request)));
    }

    protected function registerGates(): void
    {
        Gate::define('access-admin', fn (User $user) => $user->isAdmin());
        Gate::define('manage-users', fn (User $user) => $user->adminRole()?->canManageUsers() ?? false);
        Gate::define('manage-content', fn (User $user) => $user->adminRole()?->canManageContent() ?? false);
        Gate::define('view-analytics', fn (User $user) => $user->adminRole()?->canViewAnalytics() ?? false);
        Gate::define('super-admin', fn (User $user) => $user->isSuperAdmin());

        // Restrict opcodesio/log-viewer to Super Administrators only.
        Gate::define('viewLogViewer', fn (?User $user) => $user?->isSuperAdmin() ?? false);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // The default dev worker only listens to "default"; partner notifications, emails,
        // achievements and AI jobs sit on their own queues and would never run locally.
        DevCommands::artisan(
            'queue:listen --tries=1 --timeout=0 --queue=default,notifications,emails,analytics,ai,exports',
            'queue',
        );

        // Pail needs pcntl, which doesn't exist on Windows; its crash kills the whole `composer dev`.
        if (! extension_loaded('pcntl')) {
            DevCommands::except('logs');
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
