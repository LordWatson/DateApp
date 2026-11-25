<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\CalendarEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class SendCalendarRemindersCommand extends Command
{
    /** Days before the event date on which each reminder option falls due. */
    private const DAYS_BEFORE = [
        'same_day' => 0,
        '1_day' => 1,
        '3_days' => 3,
        '1_week' => 7,
    ];

    protected $signature = 'datenight:send-calendar-reminders';

    protected $description = 'Notify the owner and their partner about calendar events whose reminder is due';

    public function handle(): int
    {
        $sent = 0;

        $events = CalendarEvent::with('user.partner')
            ->whereNotNull('reminder')
            ->where('reminder_sent', false)
            // The server runs in UTC: a day of slack keeps "today" events for owners west of UTC.
            ->whereDate('date', '>=', now()->subDay()->toDateString())
            ->get();

        foreach ($events as $event) {
            $daysBefore = self::DAYS_BEFORE[$event->reminder] ?? null;

            if ($daysBefore === null) {
                continue;
            }

            // Reminders work on whole days in the owner's timezone; `time` is optional and only shown.
            $today = now($event->user->timezone ?: 'UTC')->toDateString();
            $eventDate = $event->date->toDateString();
            $dueDate = Carbon::parse($eventDate)->subDays($daysBefore)->toDateString();

            if ($today < $dueDate || $today > $eventDate) {
                continue;
            }

            $daysUntil = (int) Carbon::parse($today)->diffInDays(Carbon::parse($eventDate));
            $when = match ($daysUntil) {
                0 => 'today',
                1 => 'tomorrow',
                default => 'on '.$event->date->format('l j F'),
            };
            $at = $event->time ? ' at '.substr($event->time, 0, 5) : '';

            foreach (array_filter([$event->user, $event->user->partner]) as $recipient) {
                AppNotification::create([
                    'user_id' => $recipient->id,
                    'type' => NotificationType::CalendarReminder,
                    'title' => "Reminder: {$event->title}",
                    'body' => "{$event->title} is {$when}{$at}.",
                    'data' => ['calendar_event_id' => $event->id],
                ]);
            }

            $event->update(['reminder_sent' => true]);
            $sent++;
        }

        $this->info("Sent reminders for {$sent} events.");

        return self::SUCCESS;
    }
}
