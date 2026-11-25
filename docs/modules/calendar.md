# Calendar

> Last verified against code: 2026-11-25

## Purpose

A simple shared event list for a couple: date nights, anniversaries, birthdays, trips and custom events. Each user creates their own events; the calendar page shows both partners' events split into upcoming and past. Date night plans can be added to the calendar in one step.

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/CalendarEventController.php` | CRUD; `formatEvent()` and `eventTypes()` build the page payload |
| `app/Models/CalendarEvent.php` | Model; `user()` |
| `app/Policies/CalendarEventPolicy.php` | `view` (owner or partner), `update`/`delete` (owner) |
| `app/Http/Requests/CalendarEvent/StoreCalendarEventRequest.php`, `UpdateCalendarEventRequest.php` | Identical rule sets |
| `app/Enums/CalendarEventType.php` | The 8 types with `label()` / `emoji()`: the only list. Validation (`Rule::enum`) and the `eventTypes` prop come from it |
| `app/Events/CalendarEventCreated.php` | Dispatched by `store`; `EvaluateAchievementsOnActivity` listens |
| `app/Actions/AddDateNightPlanToCalendarAction.php` | Creates an event from a plan - owned by date-night.md |
| `app/Console/Commands/SendCalendarRemindersCommand.php` | `datenight:send-calendar-reminders` - sends due reminders (hourly) |
| `database/migrations/2026_07_14_000001_create_calendar_events_table.php` | Schema |
| `database/factories/CalendarEventFactory.php` | Factory |
| **Frontend** | |
| `resources/js/pages/calendar/Index.vue` | Upcoming/past lists + create/edit form |
| `resources/js/pages/calendar/Show.vue` | Event detail; delete for owner |
| **Tests** | |
| `tests/Feature/CalendarEventTest.php` | Feature tests |

## Data model

`calendar_events`: `user_id` (FK, cascade), `title`, `description?`, `type` (string, default `custom`), `emoji?`, `date` (cast `date`), `time?` (TIME column, not cast), `location?`, `notes?`, `colour` (default `#EC4899`), `reminder?` (string), `reminder_sent` (bool, default false), `recurrence?` (string, cast `CalendarRecurrence`: only `yearly`), `reminded_for_date?` (date: the occurrence a repeating event was last reminded for). Indexes: `(user_id, date)`, `type`.

- Relation: `User::calendarEvents()` hasMany.
- `type` values: `date_night`, `anniversary`, `birthday`, `holiday`, `weekend_away`, `movie_night`, `dinner_reservation`, `custom`.
- `reminder` values: `same_day`, `1_day`, `3_days`, `1_week`.
- Events are single-day; there is no end date or attendee list. An event can repeat every year (`recurrence = yearly`); nothing else repeats.
- `CalendarEvent::nextOccurrence($today)` is the date an event next falls on: its own date for a one-off, this year's or next year's date for a yearly one (29 February falls on 28 February in other years). Use it wherever "upcoming" is decided.

## Routes

All inside `auth`, `verified`, `onboarding` middleware (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `calendar.index` | GET `/calendar` | `CalendarEventController@index` | All own + all partner events (no pagination) |
| `calendar.store` | POST `/calendar` | `CalendarEventController@store` | `StoreCalendarEventRequest` |
| `calendar.show` | GET `/calendar/{calendarEvent}` | `CalendarEventController@show` | Policy `view` |
| `calendar.update` | PUT `/calendar/{calendarEvent}` | `CalendarEventController@update` | Policy `update` |
| `calendar.destroy` | DELETE `/calendar/{calendarEvent}` | `CalendarEventController@destroy` | Policy `delete` |
| `date-night.add-to-calendar` | POST `/date-night/{dateNightPlan}/add-to-calendar` | `DateNightPlanController@addToCalendar` | See date-night.md |

## Flows

### Create / edit / delete

1. `calendar/Index.vue` posts the form (`store`) or puts it (`update`) using Wayfinder helpers from `@/routes/calendar`.
2. Request class validates; the event is created via `$request->user()->calendarEvents()->create()`.
3. Redirect to `calendar.index` with flash `success`. No event is dispatched, no notification is sent, no activity is logged.

### Repeating events and the countdown

1. The form has a "Repeats every year" checkbox; on a new event it is ticked automatically when the type is `anniversary` or `birthday`. Existing events were not changed.
2. `formatEvent()` adds `recurrence`, `next_date`, `days_until` (from the user's own today; negative for a past one-off) and `occurrence` (years since the first date, for "3rd"). `calendar.index` sorts by `next_date`, and the page splits upcoming and past on it and shows "In 12 days" and "3rd · every year" badges. `Show.vue` shows the next date, the countdown and "Every year since".
3. The relationship hub's upcoming events include repeating events from earlier years, at their next occurrence.
4. Timeline and search still use the original date only.

### Reminders

1. `bootstrap/app.php` runs `datenight:send-calendar-reminders` hourly.
2. The command loads events with a `reminder`, `reminder_sent = false` and a date from yesterday (UTC) onwards. A reminder is due when the owner's local date (`users.timezone`) is on or after `date` minus the option's days (`same_day` 0, `1_day` 1, `3_days` 3, `1_week` 7) and not after `date`.
3. It creates one `calendar_reminder` `AppNotification` for the owner and one for their partner (`data.calendar_event_id`, linking to `/calendar/{id}`), then sets `reminder_sent = true`.
4. `CalendarEventController@update` sets `reminder_sent = false` and clears `reminded_for_date` when `date`, `reminder` or `recurrence` changes, so a moved event is reminded again.
5. A repeating event is reminded once per occurrence: the command works from `nextOccurrence()` in the owner's timezone, skips it when `reminded_for_date` already equals that occurrence, and writes `reminded_for_date` instead of `reminder_sent`. Repeating events are loaded however old their first date is.

### Add a date night plan to the calendar

1. `DateNightPlanController@addToCalendar` authorises `addToCalendar` on the plan, then calls `AddDateNightPlanToCalendarAction::execute($user, $plan, $data)`.
2. The action creates an event for the acting user (`title` = plan theme, `description` = plan summary, `type` = `date_night`, `emoji` = theme emoji, `colour` = `#EC4899`, plus `date`, optional `time` / `location`).
3. If the user has a partner, `NotificationService::notifyPartnerAddedDateNightToCalendar()` is called. Details in date-night.md and notifications-activity-mail.md.

## Rules and gotchas

- **Who can see / edit**:
  - View: owner and the owner's partner. Enforced by `CalendarEventPolicy::view` on show, and by `CalendarEventController@index` loading `$user->calendarEvents()` plus `$partner->calendarEvents()`.
  - Update / delete: owner only (`CalendarEventPolicy::update` / `delete` via `$this->authorize()`). The partner cannot edit, even though the calendar is presented as shared.
  - The payload carries `is_mine` and `owner` (owner's `name`); both Vue pages use `is_mine` to hide edit/delete.
  - Policies rely on Laravel's auto-discovery (no explicit registration).
- Validation: `title` required max 255; `type` required and in the list above; `date` required; `time` must be `H:i`; `description` max 1000; `notes` max 2000; `location` max 255; `emoji` max 10; `colour` max 20 (not validated as a colour); `reminder` nullable and in the list above; `recurrence` nullable and a `CalendarRecurrence` value.
- Event types live only in `CalendarEventType`: add a case (with label and emoji) and both request classes and the page pick it up. The model does not cast `type`; it stays a string.
- Upcoming vs past is split in the browser using the client's date (`calendar/Index.vue:57-69`).
- **Today is the user's own day**: `calendar.index` sends `today` (`User::today()`, from `users.timezone`); the page uses it for the upcoming/past split and as the default date of a new event. The relationship hub's upcoming events and the timeline's calendar entries use the same date. Event dates stay plain dates.
- Reminders are whole-day: `time` is never used to decide when to send (it is nullable and has no timezone), only shown in the text. The reminder appears within an hour of the due day starting in the owner's timezone; the partner gets it at the same moment. An event created after its due day has started is reminded on the next hourly run; a past event never is. In-app only, no email.
- `CalendarEventCreated` is dispatched by `store` only, not by `AddDateNightPlanToCalendarAction`: an event added from a plan counts towards `calendar_events_created` achievements at the next evaluation (see challenges-achievements-streaks.md).
- Suspected issue: `time` is validated as `H:i`, but the column is `TIME` and is not cast, so on databases that return `HH:MM:SS` the edit form would send back a value that fails validation. Not verified against the production database driver.
- `StoreCalendarEventRequest` and `UpdateCalendarEventRequest` are byte-for-byte the same rules.

## Related modules

- date-night.md - `AddDateNightPlanToCalendarAction`, `AddToCalendarRequest`, plan policy.
- notifications-activity-mail.md - partner notification when a plan is added.
- moments-timeline.md - the user's own events appear in the timeline.
- challenges-achievements-streaks.md - `calendar_events_created` achievements.
- dashboard-insights-search.md - relationship hub (upcoming events), insights and search read calendar events.
- weekly-reflection.md - calendar events feed the weekly recap.
- onboarding-partner.md - `partner_id` drives sharing.

## Tests

- `tests/Feature/CalendarEventTest.php` - index, create, update own, cannot update other's, delete own, cannot delete other's.

- `tests/Feature/Commands/SendCalendarRemindersCommandTest.php` - sent once to owner and partner, not before due, not without a reminder or for a past event, owner timezone, re-armed when the date changes.

Run: `php artisan test --filter="CalendarEventTest|SendCalendarRemindersCommandTest"`

- `tests/Feature/CalendarRecurrenceTest.php` - next occurrence this year, next year, today and for a leap day; ordering; the hub; validation. The reminders test also covers one reminder per occurrence.

Not covered: partner visibility on index/show, validation limits.

## Change log

- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 1: delete asks first; `CalendarEventCreated` is dispatched on create and re-evaluates achievements.
- 2026-11-24 — Roadmap Phase 2 (2.3): reminders are sent. New hourly `datenight:send-calendar-reminders` notifies the owner and partner (`calendar_reminder`) and sets `reminder_sent`; changing the date or reminder re-arms it.
- 2026-11-25 — Roadmap Phase 3 (3.14): the upcoming/past split and the default date use the user's timezone (`today` prop).
- 2026-11-26 — Roadmap Phase 4: (4.6) the two request classes and `CalendarEventController::eventTypes()` use `CalendarEventType` instead of their own copies of the list.
- 2026-11-25 — Roadmap Phase 5.7: yearly recurrence (`recurrence`, `reminded_for_date`, `CalendarEvent::nextOccurrence()`), countdown and occurrence badges, a reminder for every occurrence.
