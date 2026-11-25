<?php

use App\Console\Commands\CleanTemporaryExportsCommand;
use App\Console\Commands\ConnectionAnniversariesCommand;
use App\Console\Commands\EvaluateStreaksCommand;
use App\Console\Commands\ExpireInvitationsCommand;
use App\Console\Commands\NudgePartnersCommand;
use App\Console\Commands\SelectDailyChallengeCommand;
use App\Console\Commands\SendCalendarRemindersCommand;
use App\Console\Commands\SendRemindersCommand;
use App\Http\Middleware\EnsureNotInMaintenance;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsNotSuspended;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectToPendingInvitation;
use App\Providers\EventServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\ExceptionResponse;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        EventServiceProvider::class,
    ])
    ->withEvents(discover: false)
    ->withRouting(
        web: [__DIR__.'/../routes/web.php', __DIR__.'/../routes/admin.php'],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            EnsureUserIsNotSuspended::class,
            RedirectToPendingInvitation::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            EnsureNotInMaintenance::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'onboarding' => EnsureOnboardingComplete::class,
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SelectDailyChallengeCommand::class)->dailyAt('00:01');
        // Hourly: a day ends at a different hour for each user's timezone.
        $schedule->command(EvaluateStreaksCommand::class)->hourlyAt(5);
        $schedule->command(ExpireInvitationsCommand::class)->dailyAt('00:10');
        $schedule->command(SendRemindersCommand::class)->dailyAt('09:00');
        $schedule->command(SendCalendarRemindersCommand::class)->hourly();
        $schedule->command(NudgePartnersCommand::class)->hourly();
        $schedule->command(ConnectionAnniversariesCommand::class)->dailyAt('09:00');
        $schedule->command(CleanTemporaryExportsCommand::class)->dailyAt('02:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Error pages in the app's own design instead of Laravel's, which Inertia would show in a modal.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if ($response instanceof JsonResponse || $request->expectsJson()) {
                return $response;
            }

            if ($status === 419) {
                return back()->with('error', 'The page expired, please try again.');
            }

            // A 500 keeps Laravel's debug page while debugging.
            if (! in_array($status, [403, 404, 500, 503], true) || ($status === 500 && config('app.debug'))) {
                return $response;
            }

            // No shared props: the session may not have started (an unknown URL never reaches the web middleware).
            return new ExceptionResponse($exception, $request, $response, app(Router::class), app(Kernel::class))
                ->render('Error', ['status' => $status])
                ->usingMiddleware(HandleInertiaRequests::class)
                ->toResponse($request);
        });
    })->create();
