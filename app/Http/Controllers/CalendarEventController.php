<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarEvent\StoreCalendarEventRequest;
use App\Http\Requests\CalendarEvent\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarEventController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $partner = $user->partner;

        $userEvents = $user->calendarEvents()
            ->orderBy('date')
            ->get()
            ->map(fn ($e) => $this->formatEvent($e, $user->name, true))
            ->toBase();

        $partnerEvents = $partner
            ? $partner->calendarEvents()
                ->orderBy('date')
                ->get()
                ->map(fn ($e) => $this->formatEvent($e, $partner->name, false))
                ->toBase()
            : collect();

        $events = $userEvents->merge($partnerEvents)->sortBy('date')->values();

        return Inertia::render('calendar/Index', [
            'events' => $events,
            'eventTypes' => $this->eventTypes(),
        ]);
    }

    public function show(Request $request, CalendarEvent $calendarEvent): Response
    {
        $this->authorize('view', $calendarEvent);

        $owner = $calendarEvent->user;
        $isMine = $request->user()->id === $owner->id;

        return Inertia::render('calendar/Show', [
            'event' => $this->formatEvent($calendarEvent, $owner->name, $isMine),
        ]);
    }

    public function store(StoreCalendarEventRequest $request): RedirectResponse
    {
        $request->user()->calendarEvents()->create($request->validated());

        return redirect()->route('calendar.index')->with('success', 'Event created!');
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorize('update', $calendarEvent);
        $calendarEvent->update($request->validated());

        return redirect()->route('calendar.index')->with('success', 'Event updated!');
    }

    public function destroy(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorize('delete', $calendarEvent);
        $calendarEvent->delete();

        return redirect()->route('calendar.index')->with('success', 'Event deleted!');
    }

    private function formatEvent(CalendarEvent $event, string $ownerName, bool $isMine): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'type' => $event->type,
            'emoji' => $event->emoji,
            'date' => $event->date->toDateString(),
            'time' => $event->time,
            'location' => $event->location,
            'notes' => $event->notes,
            'colour' => $event->colour,
            'reminder' => $event->reminder,
            'owner' => $ownerName,
            'is_mine' => $isMine,
        ];
    }

    private function eventTypes(): array
    {
        return [
            ['value' => 'date_night', 'label' => 'Date Night', 'emoji' => '💕'],
            ['value' => 'anniversary', 'label' => 'Anniversary', 'emoji' => '💍'],
            ['value' => 'birthday', 'label' => 'Birthday', 'emoji' => '🎂'],
            ['value' => 'holiday', 'label' => 'Holiday', 'emoji' => '✈️'],
            ['value' => 'weekend_away', 'label' => 'Weekend Away', 'emoji' => '🏨'],
            ['value' => 'movie_night', 'label' => 'Movie Night', 'emoji' => '🎬'],
            ['value' => 'dinner_reservation', 'label' => 'Dinner Reservation', 'emoji' => '🍽️'],
            ['value' => 'custom', 'label' => 'Custom Event', 'emoji' => '📅'],
        ];
    }
}
