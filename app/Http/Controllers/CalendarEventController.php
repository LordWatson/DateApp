<?php

namespace App\Http\Controllers;

use App\Enums\CalendarEventType;
use App\Events\CalendarEventCreated;
use App\Http\Requests\CalendarEvent\StoreCalendarEventRequest;
use App\Http\Requests\CalendarEvent\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\DateNightPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CalendarEventController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $partner = $user->partner;

        $today = $user->today();

        $ownEvents = $user->calendarEvents()->orderBy('date')->get();
        $theirEvents = $partner ? $partner->calendarEvents()->orderBy('date')->get() : collect();
        $plans = $this->plansFor($user, $ownEvents->concat($theirEvents));

        $userEvents = $ownEvents
            ->map(fn ($e) => $this->formatEvent($e, $user->name, true, $today, $plans->get($e->date_night_plan_id)))
            ->toBase();

        $partnerEvents = $theirEvents
            ->map(fn ($e) => $this->formatEvent($e, $partner->name, false, $today, $plans->get($e->date_night_plan_id)))
            ->toBase();

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

        $plan = $this->plansFor($request->user(), collect([$calendarEvent]))->first();

        return Inertia::render('calendar/Show', [
            'event' => $this->formatEvent($calendarEvent, $owner->name, $isMine, $request->user()->today(), $plan),
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

    /**
     * The date night plans these events came from, keyed by id, leaving out
     * any the viewer may not open (a partner's event can point at one).
     *
     * @param  Collection<int, CalendarEvent>  $events
     * @return Collection<int, DateNightPlan>
     */
    private function plansFor(User $viewer, Collection $events): Collection
    {
        $planIds = $events->pluck('date_night_plan_id')->filter();

        if ($planIds->isEmpty()) {
            return collect();
        }

        return DateNightPlan::visibleTo($viewer)->whereKey($planIds)->get(['id', 'theme', 'theme_emoji'])->keyBy('id');
    }

    /** @return array<string, mixed> */
    private function formatEvent(CalendarEvent $event, string $ownerName, bool $isMine, CarbonInterface $today, ?DateNightPlan $plan = null): array
    {
        $next = $event->nextOccurrence($today);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'type' => $event->type,
            'type_label' => CalendarEventType::tryFrom($event->type)?->label() ?? 'Event',
            // The plan this event was added from, when the viewer can open it.
            'plan' => $plan ? ['id' => $plan->id, 'theme' => $plan->theme, 'emoji' => $plan->theme_emoji] : null,
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
