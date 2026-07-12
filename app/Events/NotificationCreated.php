<?php

namespace App\Events;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NotificationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly AppNotification $notification,
    ) {}
}
