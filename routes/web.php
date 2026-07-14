<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DateNightPlanController;
use App\Http\Controllers\DateNightPlanPdfController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\LoveNoteController;
use App\Http\Controllers\MomentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\RelationshipHubController;
use App\Http\Controllers\SavedProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TimelineController;
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

        // Date Night Plans
        Route::prefix('date-night')->name('date-night.')->group(function () {
            Route::get('/history', [DateNightPlanController::class, 'history'])->name('history');
            Route::get('/favourites', [DateNightPlanController::class, 'favourites'])->name('favourites');
            Route::get('/{dateNightPlan}', [DateNightPlanController::class, 'show'])->name('show');
            Route::post('/{dateNightPlan}/favourite', [DateNightPlanController::class, 'toggleFavourite'])->name('toggle-favourite');
            Route::get('/{dateNightPlan}/export', [DateNightPlanPdfController::class, 'export'])->name('export');
        });

        // Love Notes
        Route::prefix('love-notes')->name('love-notes.')->group(function () {
            Route::get('/', [LoveNoteController::class, 'index'])->name('index');
            Route::post('/', [LoveNoteController::class, 'store'])->name('store');
            Route::get('/unread-count', [LoveNoteController::class, 'unreadCount'])->name('unread-count');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('mark-read');
            Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
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

        // Relationship Hub
        Route::get('relationship-hub', [RelationshipHubController::class, 'index'])->name('relationship-hub.index');

        // Calendar
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [CalendarEventController::class, 'index'])->name('index');
            Route::post('/', [CalendarEventController::class, 'store'])->name('store');
            Route::put('/{calendarEvent}', [CalendarEventController::class, 'update'])->name('update');
            Route::delete('/{calendarEvent}', [CalendarEventController::class, 'destroy'])->name('destroy');
        });

        // Moments
        Route::prefix('moments')->name('moments.')->group(function () {
            Route::get('/', [MomentController::class, 'index'])->name('index');
            Route::post('/', [MomentController::class, 'store'])->name('store');
            Route::get('/{moment}', [MomentController::class, 'show'])->name('show');
            Route::put('/{moment}', [MomentController::class, 'update'])->name('update');
            Route::delete('/{moment}', [MomentController::class, 'destroy'])->name('destroy');
            Route::post('/{moment}/favourite', [MomentController::class, 'toggleFavourite'])->name('toggle-favourite');
        });

        // Timeline
        Route::get('timeline', [TimelineController::class, 'index'])->name('timeline.index');

        // Achievements
        Route::get('achievements', [AchievementController::class, 'index'])->name('achievements.index');

        // Insights
        Route::get('insights', [InsightsController::class, 'index'])->name('insights.index');

        // Search
        Route::get('search', [SearchController::class, 'index'])->name('search.index');
    });
});

Route::get('/health', HealthController::class)->name('health');

require __DIR__.'/settings.php';
