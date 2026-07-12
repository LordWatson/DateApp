<?php

namespace App\Events;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CalendarEventCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly CalendarEvent $calendarEvent,
    ) {}
}
