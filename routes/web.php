<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\SavedProfileController;
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

        // Questionnaires
        Route::prefix('questionnaires')->name('questionnaires.')->group(function () {
            Route::get('/', [QuestionnaireController::class, 'index'])->name('index');
            Route::get('/{questionnaire:slug}', [QuestionnaireController::class, 'show'])->name('show');
            Route::post('/{questionnaire:slug}/start', [QuestionnaireController::class, 'start'])->name('start');
            Route::get('/{questionnaire:slug}/question/{order}', [QuestionnaireController::class, 'question'])->name('question');
            Route::post('/{questionnaire:slug}/question/{order}/answer', [QuestionnaireController::class, 'answer'])->name('answer');
            Route::get('/{questionnaire:slug}/complete', [QuestionnaireController::class, 'complete'])->name('complete');
            Route::post('/{questionnaire:slug}/finish', [QuestionnaireController::class, 'finish'])->name('finish');
            Route::get('/{questionnaire:slug}/summary', [QuestionnaireController::class, 'summary'])->name('summary');
            Route::get('/{questionnaire:slug}/compatibility', [QuestionnaireController::class, 'compatibility'])->name('compatibility');
        });

        // Saved profiles
        Route::prefix('saved-profiles')->name('saved-profiles.')->group(function () {
            Route::get('/', [SavedProfileController::class, 'index'])->name('index');
            Route::post('/', [SavedProfileController::class, 'store'])->name('store');
            Route::put('/{savedProfile}', [SavedProfileController::class, 'update'])->name('update');
            Route::delete('/{savedProfile}', [SavedProfileController::class, 'destroy'])->name('destroy');
            Route::post('/{savedProfile}/apply/{questionnaireSlug}', [SavedProfileController::class, 'apply'])->name('apply');
        });

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
