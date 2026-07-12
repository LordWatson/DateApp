<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PartnerController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Public invitation acceptance (redirects to register if not logged in)
Route::get('invite/{token}', [OnboardingController::class, 'acceptInvite'])
    ->name('onboarding.accept-invite');

Route::middleware(['auth', 'verified'])->group(function () {
    // Onboarding
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('index');
        Route::post('/profile', [OnboardingController::class, 'completeProfile'])->name('complete-profile');
        Route::post('/invite', [OnboardingController::class, 'sendInvite'])->name('send-invite');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });

    // Protected routes (require onboarding)
    Route::middleware('onboarding')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Partner management
        Route::prefix('partner')->name('partner.')->group(function () {
            Route::get('/', [PartnerController::class, 'index'])->name('index');
            Route::post('/invite', [PartnerController::class, 'sendInvite'])->name('send-invite');
            Route::post('/resend', [PartnerController::class, 'resendInvite'])->name('resend-invite');
            Route::delete('/disconnect', [PartnerController::class, 'disconnect'])->name('disconnect');
        });
    });
});

require __DIR__.'/settings.php';
