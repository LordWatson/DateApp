<?php

use App\Http\Controllers\Settings\AiConsentController;
use App\Http\Controllers\Settings\DataExportController;
use App\Http\Controllers\Settings\EmailUnsubscribeController;
use App\Http\Controllers\Settings\NotificationsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::put('settings/ai-consent', AiConsentController::class)->name('ai-consent.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/notifications', [NotificationsController::class, 'edit'])->name('notification-settings.edit');
    Route::patch('settings/notifications', [NotificationsController::class, 'update'])->name('notification-settings.update');

    Route::post('settings/data-export', [DataExportController::class, 'store'])->name('data-export.store');
    Route::get('settings/data-export', [DataExportController::class, 'download'])->name('data-export.download');
});

// The "stop emails like this" link in an email: signed, so it works without signing in.
Route::middleware('signed')->group(function () {
    Route::get('email-preferences/{user}/{category}', [EmailUnsubscribeController::class, 'show'])->name('email-unsubscribe.show');
    Route::post('email-preferences/{user}/{category}', [EmailUnsubscribeController::class, 'store'])->name('email-unsubscribe.store');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
