<?php

use App\Console\Commands\CleanTemporaryExportsCommand;
use App\Console\Commands\EvaluateStreaksCommand;
use App\Console\Commands\ExpireInvitationsCommand;
use App\Console\Commands\RefreshStatisticsCommand;
use App\Console\Commands\SelectDailyChallengeCommand;
use App\Console\Commands\SendRemindersCommand;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Providers\EventServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        EventServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'onboarding' => EnsureOnboardingComplete::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SelectDailyChallengeCommand::class)->dailyAt('00:01');
        $schedule->command(EvaluateStreaksCommand::class)->dailyAt('00:05');
        $schedule->command(ExpireInvitationsCommand::class)->dailyAt('00:10');
        $schedule->command(SendRemindersCommand::class)->dailyAt('09:00');
        $schedule->command(RefreshStatisticsCommand::class)->dailyAt('03:00');
        $schedule->command(CleanTemporaryExportsCommand::class)->dailyAt('02:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
