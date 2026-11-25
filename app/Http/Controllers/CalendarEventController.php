<?php

namespace App\Http\Controllers;

use App\Enums\CalendarEventType;
use App\Events\CalendarEventCreated;
use App\Http\Requests\CalendarEvent\StoreCalendarEventRequest;
use App\Http\Requests\CalendarEvent\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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

        $today = $user->today();

        $userEvents = $user->calendarEvents()
            ->orderBy('date')
            ->get()
            ->map(fn ($e) => $this->formatEvent($e, $user->name, true, $today))
            ->toBase();

        $partnerEvents = $partner
            ? $partner->calendarEvents()
                ->orderBy('date')
                ->get()
                ->map(fn ($e) => $this->formatEvent($e, $partner->name, false, $today))
                ->toBase()
            : collect();

        // A repeating event sits where it next falls, not where it was first entered.
        $events = $userEvents->merge($partnerEvents)->sortBy('next_date')->values();

        return Inertia::render('calendar/Index', [
            'events' => $events,
            // The user's own "today": it splits upcoming from past and is the default date.
            'today' => $today->toDateString(),
            'eventTypes' => $this->eventTypes(),
        ]);
    }

    public function show(Request $request, CalendarEvent $calendarEvent): Response
    {
        $this->authorize('view', $calendarEvent);

        $owner = $calendarEvent->user;
        $isMine = $request->user()->id === $owner->id;

        return Inertia::render('calendar/Show', [
            'event' => $this->formatEvent($calendarEvent, $owner->name, $isMine, $request->user()->today()),
        ]);
    }

    public function store(StoreCalendarEventRequest $request): RedirectResponse
    {
        $event = $request->user()->calendarEvents()->create($request->validated());

        CalendarEventCreated::dispatch($request->user(), $event);

        return redirect()->route('calendar.index')->with('success', 'Event created!');
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorize('update', $calendarEvent);
        $calendarEvent->fill($request->validated());

        // A moved event or a changed reminder is due again.
        if ($calendarEvent->isDirty(['date', 'reminder', 'recurrence'])) {
            $calendarEvent->reminder_sent = false;
            $calendarEvent->reminded_for_date = null;
        }

        $calendarEvent->save();

        return redirect()->route('calendar.index')->with('success', 'Event updated!');
    }

    public function destroy(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorize('delete', $calendarEvent);
        $calendarEvent->delete();

        return redirect()->route('calendar.index')->with('success', 'Event deleted!');
    }

    /** @return array<string, mixed> */
    private function formatEvent(CalendarEvent $event, string $ownerName, bool $isMine, CarbonInterface $today): array
    {
        $next = $event->nextOccurrence($today);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'type' => $event->type,
            'emoji' => $event->emoji,
            'date' => $event->date->toDateString(),
            'recurrence' => $event->recurrence?->value,
            // Where the event next falls (its own date for a one-off), and how far away that is.
            'next_date' => $next->toDateString(),
            'days_until' => (int) CarbonImmutable::parse($today->toDateString())->diffInDays($next, false),
            // "5th anniversary": how many years after the first one the next occurrence is.
            'occurrence' => $event->recurrence ? $next->year - $event->date->year : null,
            'time' => $event->time,
            'location' => $event->location,
            'notes' => $event->notes,
            'colour' => $event->colour,
            'reminder' => $event->reminder,
            'owner' => $ownerName,
            'is_mine' => $isMine,
        ];
    }

    /** @return list<array{value: string, label: string, emoji: string}> */
    private function eventTypes(): array
    {
        return array_map(
            static fn (CalendarEventType $type): array => ['value' => $type->value, 'label' => $type->label(), 'emoji' => $type->emoji()],
            CalendarEventType::cases(),
        );
    }
}
