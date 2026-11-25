<?php

namespace App\Http\Controllers\Settings;

use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\NotificationsUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    /**
     * Show the user's notification settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Notifications', [
            'emailNotifications' => $request->user()->email_notifications,
            'emailCategories' => array_map(fn (NotificationCategory $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
                'description' => $category->description(),
                'enabled' => $request->user()->notification_preferences['emails'][$category->value] ?? true,
            ], NotificationCategory::cases()),
            'reminderFrequency' => $request->user()->reminderFrequency(),
        ]);
    }

    /**
     * Update the user's notification settings.
     */
    public function update(NotificationsUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $preferences = $user->notification_preferences ?? [];

        if ($request->has('emails')) {
            $preferences['emails'] = array_map('boolval', [...($preferences['emails'] ?? []), ...$request->validated('emails')]);
        }

        if ($request->has('reminder_frequency')) {
            $preferences['reminder_frequency'] = $request->validated('reminder_frequency');
        }

        $user->update([
            'email_notifications' => $request->boolean('email_notifications'),
            'notification_preferences' => $preferences ?: null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification settings updated.')]);

        return to_route('notification-settings.edit');
    }
}
