<?php

namespace App\Listeners;

use App\Models\DeviceToken;
use Illuminate\Auth\Events\Logout;

final class DeleteDeviceTokensOnLogout
{
    /**
     * A signed-out phone must not keep receiving someone's notifications.
     * The server cannot tell which phone signed out, so all of the user's tokens go;
     * any phone still signed in registers again the next time the app opens.
     */
    public function handle(Logout $event): void
    {
        if ($event->user !== null) {
            DeviceToken::where('user_id', $event->user->getAuthIdentifier())->delete();
        }
    }
}
