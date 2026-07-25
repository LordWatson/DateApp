<?php

use App\Http\Controllers\Admin\AdminAchievementController;
use App\Http\Controllers\Admin\AdminAiPromptController;
use App\Http\Controllers\Admin\AdminAiSettingController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminChallengeController;
use App\Http\Controllers\Admin\AdminCoupleController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Admin\AdminFeatureFlagController;
use App\Http\Controllers\Admin\AdminMediaController;
use App\Http\Controllers\Admin\AdminOptionController;
use App\Http\Controllers\Admin\AdminQuestionController;
use App\Http\Controllers\Admin\AdminQuestionnaireController;
use App\Http\Controllers\Admin\AdminSystemSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
        Route::post('/{user}/suspend', [AdminUserController::class, 'suspend'])->name('suspend');
        Route::post('/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('unsuspend');
        Route::post('/{user}/disconnect', [AdminUserController::class, 'disconnect'])->name('disconnect');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
    });

    // Couples
    Route::prefix('couples')->name('couples.')->group(function () {
        Route::get('/', [AdminCoupleController::class, 'index'])->name('index');
        Route::post('/{user}/disconnect', [AdminCoupleController::class, 'disconnect'])->name('disconnect');
        Route::post('/reconnect', [AdminCoupleController::class, 'reconnect'])->name('reconnect');
    });

    // Questionnaires
    Route::prefix('questionnaires')->name('questionnaires.')->group(function () {
        Route::get('/', [AdminQuestionnaireController::class, 'index'])->name('index');
        Route::get('/create', [AdminQuestionnaireController::class, 'create'])->name('create');
        Route::post('/', [AdminQuestionnaireController::class, 'store'])->name('store');
        Route::get('/{questionnaire}/edit', [AdminQuestionnaireController::class, 'edit'])->name('edit');
        Route::put('/{questionnaire}', [AdminQuestionnaireController::class, 'update'])->name('update');
        Route::delete('/{questionnaire}', [AdminQuestionnaireController::class, 'destroy'])->name('destroy');
        Route::post('/{questionnaire}/duplicate', [AdminQuestionnaireController::class, 'duplicate'])->name('duplicate');
        Route::post('/reorder', [AdminQuestionnaireController::class, 'reorder'])->name('reorder');
    });

    // Questions
    Route::prefix('questionnaires/{questionnaire}/questions')->name('questions.')->group(function () {
        Route::post('/', [AdminQuestionController::class, 'store'])->name('store');
    });
    Route::prefix('questions')->name('questions.')->group(function () {
        Route::put('/{question}', [AdminQuestionController::class, 'update'])->name('update');
        Route::delete('/{question}', [AdminQuestionController::class, 'destroy'])->name('destroy');
    });

    // Options
    Route::prefix('questions/{question}/options')->name('options.')->group(function () {
        Route::post('/', [AdminOptionController::class, 'store'])->name('store');
    });
    Route::prefix('options')->name('options.')->group(function () {
        Route::put('/{option}', [AdminOptionController::class, 'update'])->name('update');
        Route::delete('/{option}', [AdminOptionController::class, 'destroy'])->name('destroy');
        Route::post('/{option}/duplicate', [AdminOptionController::class, 'duplicate'])->name('duplicate');
    });

    // Challenges
    Route::prefix('challenges')->name('challenges.')->group(function () {
        Route::get('/', [AdminChallengeController::class, 'index'])->name('index');
        Route::post('/', [AdminChallengeController::class, 'store'])->name('store');
        Route::put('/{challenge}', [AdminChallengeController::class, 'update'])->name('update');
        Route::delete('/{challenge}', [AdminChallengeController::class, 'destroy'])->name('destroy');
        Route::post('/{challenge}/archive', [AdminChallengeController::class, 'archive'])->name('archive');
        Route::post('/bulk-import', [AdminChallengeController::class, 'bulkImport'])->name('bulk-import');
    });

    // Achievements
    Route::prefix('achievements')->name('achievements.')->group(function () {
        Route::get('/', [AdminAchievementController::class, 'index'])->name('index');
        Route::post('/', [AdminAchievementController::class, 'store'])->name('store');
        Route::put('/{achievement}', [AdminAchievementController::class, 'update'])->name('update');
        Route::delete('/{achievement}', [AdminAchievementController::class, 'destroy'])->name('destroy');
    });

    // Feature Flags
    Route::prefix('feature-flags')->name('feature-flags.')->group(function () {
        Route::get('/', [AdminFeatureFlagController::class, 'index'])->name('index');
        Route::post('/', [AdminFeatureFlagController::class, 'store'])->name('store');
        Route::put('/{featureFlag}', [AdminFeatureFlagController::class, 'update'])->name('update');
        Route::delete('/{featureFlag}', [AdminFeatureFlagController::class, 'destroy'])->name('destroy');
    });

    // System Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AdminSystemSettingController::class, 'index'])->name('index');
        Route::put('/{systemSetting}', [AdminSystemSettingController::class, 'update'])->name('update');
        Route::post('/bulk', [AdminSystemSettingController::class, 'bulkUpdate'])->name('bulk-update');
    });

    // Email Templates
    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::get('/', [AdminEmailTemplateController::class, 'index'])->name('index');
        Route::get('/{emailTemplate}/edit', [AdminEmailTemplateController::class, 'edit'])->name('edit');
        Route::put('/{emailTemplate}', [AdminEmailTemplateController::class, 'update'])->name('update');
    });

    // AI Prompts
    Route::prefix('ai-prompts')->name('ai-prompts.')->group(function () {
        Route::get('/', [AdminAiPromptController::class, 'index'])->name('index');
        Route::post('/', [AdminAiPromptController::class, 'store'])->name('store');
        Route::put('/{aiPrompt}', [AdminAiPromptController::class, 'update'])->name('update');
        Route::delete('/{aiPrompt}', [AdminAiPromptController::class, 'destroy'])->name('destroy');
    });

    // AI Settings (provider, model, tokens, retries, plus test/preview + analytics)
    Route::prefix('ai-settings')->name('ai-settings.')->group(function () {
        Route::get('/', [AdminAiSettingController::class, 'index'])->name('index');
        Route::put('/', [AdminAiSettingController::class, 'update'])->name('update');
        Route::post('/test-connection', [AdminAiSettingController::class, 'testConnection'])->name('test-connection');
        Route::get('/preview-prompt', [AdminAiSettingController::class, 'previewPrompt'])->name('preview-prompt');
        Route::post('/preview-response', [AdminAiSettingController::class, 'previewResponse'])->name('preview-response');
    });

    // Media Library
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [AdminMediaController::class, 'index'])->name('index');
        Route::post('/', [AdminMediaController::class, 'store'])->name('store');
        Route::put('/{mediaLibrary}', [AdminMediaController::class, 'update'])->name('update');
        Route::delete('/{mediaLibrary}', [AdminMediaController::class, 'destroy'])->name('destroy');
    });

    // Audit Logs
    Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');

    // Analytics
    Route::get('analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
});
