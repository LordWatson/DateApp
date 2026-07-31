<?php

namespace App\Actions;

use App\Models\CalendarEvent;
use App\Models\DateNightPlan;
use App\Models\User;
use App\Services\NotificationService;

class AddDateNightPlanToCalendarAction
{
    private const DEFAULT_COLOUR = '#EC4899';

    private const EVENT_TYPE = 'date_night';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Create a calendar event for a Date Night plan on behalf of the given user
     * and notify their partner (if any).
     *
     * @param  array{date: string, time?: string|null, location?: string|null}  $data
     */
    public function execute(User $user, DateNightPlan $plan, array $data): CalendarEvent
    {
        $event = $user->calendarEvents()->create([
            'title' => $plan->theme,
            'description' => $plan->summary,
            'type' => self::EVENT_TYPE,
            'emoji' => $plan->theme_emoji,
            'date' => $data['date'],
            'time' => $data['time'] ?? null,
            'location' => $data['location'] ?? null,
            'colour' => self::DEFAULT_COLOUR,
        ]);

        $partner = $user->partner;

        if ($partner) {
            $this->notificationService->notifyPartnerAddedDateNightToCalendar(
                $partner,
                $user,
                $plan,
                $event,
            );
        }

        return $event;
    }
}
