<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->id === $calendarEvent->user_id;
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->id === $calendarEvent->user_id;
    }
}
