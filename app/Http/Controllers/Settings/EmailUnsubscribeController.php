<?php

namespace App\Http\Controllers\Settings;

use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The "stop these emails" link in the footer of a notification email.
 * Reached through a signed URL, so it works without signing in.
 */
class EmailUnsubscribeController extends Controller
{
    /**
     * Opening the link only asks: a mail scanner following it must not change anything.
     */
    public function show(Request $request, User $user, NotificationCategory $category): View
    {
        return view('unsubscribe', [
            'category' => $category,
            'action' => $request->fullUrl(),
            'done' => ! $user->wantsEmail($category),
        ]);
    }

    public function store(User $user, NotificationCategory $category): View
    {
        $user->setEmailPreference($category, false);

        return view('unsubscribe', ['category' => $category, 'action' => null, 'done' => true]);
    }
}
