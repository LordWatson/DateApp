# Moments and Timeline

> Last verified against code: 2026-11-24

## Purpose

**Moments** are a shared memory journal: a user records a titled, dated memory with optional description, mood emoji, tags, photo and owner-only private notes; the partner can see it and is notified. **Timeline** is a read-only chronological feed that merges the user's completed questionnaires, date night plans, moments, love notes, calendar events and unlocked achievements.

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/MomentController.php` | CRUD + favourite toggle; `formatMoment()` shapes the page payload |
| `app/Models/Moment.php` | Model; `user()`, `dateNightPlan()` |
| `app/Policies/MomentPolicy.php` | `view` (owner or partner), `update`/`delete` (owner) |
| `app/Http/Requests/Moment/StoreMomentRequest.php` | Validation for both store and update |
| `app/Events/MomentCreated.php` | Fired on store only |
| `app/Listeners/SendMomentNotificationOnMomentCreated.php` | Queued (`notifications`); dispatches the job if the creator has a partner |
| `app/Jobs/SendMomentNotificationJob.php` | Creates `AppNotification` + queues `MomentCreatedMail` |
| `app/Mail/MomentCreatedMail.php`, `resources/views/emails/moment-created.blade.php` | Email to partner |
| `app/Listeners/LogActivityOnMomentCreated.php` | Sync; activity log `moment_created` |
| `app/Listeners/EvaluateAchievementsOnMomentCreated.php` | Queued (`analytics`); see challenges-achievements-streaks.md |
| `app/Http/Resources/MomentResource.php` | Unused (controller formats arrays itself) |
| `app/Http/Controllers/TimelineController.php` | `timeline.index`; passes filter list |
| `app/Services/TimelineBuilderService.php` | Builds the merged feed at request time |
| `app/Models/TimelineEntry.php` | Model for `timeline_entries` table - unused (see gotchas) |
| `database/factories/MomentFactory.php` | Factory |
| **Frontend** | |
| `resources/js/pages/moments/Index.vue` | Card list + create/edit bottom-sheet form |
| `resources/js/pages/moments/Show.vue` | Detail page; owner-only actions and private notes |
| `resources/js/pages/timeline/Index.vue` | Filter tabs + feed |
| **Tests** | |
| `tests/Feature/MomentTest.php`, `tests/Feature/TimelineTest.php` | Feature tests |

## Data model

- `moments`: `user_id` (FK, cascade), `date_night_plan_id?` (FK, null on delete), `title`, `description?`, `mood?` (free string, UI offers emojis), `photo?` (path on the `public` disk), `date` (cast `date`), `is_favourite` (bool), `private_notes?`, `tags?` (JSON, cast `array`). Indexes: `(user_id, date)`, `is_favourite`.
- `timeline_entries`: `user_id`, `type`, `title`, `description?`, `emoji?`, morph `subject`, `occurred_at`. Table and model exist (`User::timelineEntries()`), but nothing writes or reads them.
- A moment belongs to one user. "Shared with partner" is derived from `users.partner_id` at read time; there is no couple id.

## Routes

All inside `auth`, `verified`, `onboarding` middleware (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `moments.index` | GET `/moments` | `MomentController@index` | Own + partner's moments, `date` desc, paginated 12 |
| `moments.store` | POST `/moments` | `MomentController@store` | `StoreMomentRequest`; fires `MomentCreated` |
| `moments.show` | GET `/moments/{moment}` | `MomentController@show` | Policy `view` |
| `moments.update` | PUT `/moments/{moment}` | `MomentController@update` | Policy `update`; same request class as store |
| `moments.destroy` | DELETE `/moments/{moment}` | `MomentController@destroy` | Policy `delete`; deletes photo file |
| `moments.toggle-favourite` | POST `/moments/{moment}/favourite` | `MomentController@toggleFavourite` | Policy `update` |
| `timeline.index` | GET `/timeline?filter=` | `TimelineController@index` | Filters: `all`, `questionnaires`, `plans`, `moments`, `love_notes`, `calendar`, `achievements` |

## Flows

### Create a moment

1. `moments/Index.vue` posts the form to `moments.store`.
2. `StoreMomentRequest` validates. If a `photo` file is present it is stored with `store('moments', 'public')` and the path saved.
3. Moment is created through `$request->user()->moments()`.
4. `MomentCreated::dispatch($user, $moment)`:
   - `SendMomentNotificationOnMomentCreated` (queued) -> if partner exists -> `SendMomentNotificationJob` (`notifications` queue, 3 tries, 30 s) -> `AppNotification` of type `moment_created` for the partner + queued `MomentCreatedMail`.
   - `LogActivityOnMomentCreated` (sync) -> activity log.
   - `EvaluateAchievementsOnMomentCreated` (queued) -> `AchievementEvaluationJob`.
5. Redirect to `moments.index` with flash `success`.

### Update / delete / favourite

1. Owner-only via policy. Update replaces the stored photo (old file deleted) when a new file is sent; delete removes the file then the row.
2. No event is fired for update, delete or favourite.

### Timeline

1. `TimelineController@index` reads `filter` (default `all`); unknown values fall back to `all` inside the service (the page still receives the raw value as `filter`).
2. `TimelineBuilderService::build()` queries each source (max 20 rows per type, achievements unlimited), maps to `{id, type, title, description, emoji, occurred_at, link, ...}` and sorts by `occurred_at` desc.
3. Only date night plan entries carry a `link` (`date-night.show`).

## Rules and gotchas

- **Who can see / edit**:
  - View: owner and the owner's current partner. Enforced in `MomentPolicy::view` (show) and by the `whereIn('user_id', [me, partner_id])` query in `MomentController@index`.
  - Edit, delete, favourite: owner only (`MomentPolicy::update` / `delete`, called via `$this->authorize()` in the controller).
  - `private_notes` are returned only to the owner (`MomentController::formatMoment`).
  - Policies are not registered explicitly; they rely on Laravel's `App\Policies\{Model}Policy` auto-discovery.
  - Timeline is strictly personal: `TimelineController` passes only `$request->user()`. It shows the user's **own** moments and calendar events (not the partner's), and love notes both sent and received.
- Validation (`StoreMomentRequest`): `title` required max 255; `date` required; `description` and `private_notes` max 2000; `mood` max 50; `photo` image max 5120 KB; `tags` array of strings max 50 each; `date_night_plan_id` must exist. The same rules apply to update, so `title` and `date` are always required.
- **Photos / storage**: files go to the `public` disk under `moments/` (`storage/app/public/moments`), so `php artisan storage:link` is required for them to be served. There is no resizing or thumbnailing.
- Disconnecting a partner immediately hides moments from the ex-partner (visibility is computed from `partner_id`).
- Email is sent to the partner regardless of the user's `email_notifications` setting (the job does not check it).
- Suspected issue: no photo upload UI. `resources/js/pages/moments/Index.vue` has `photo` in the form state (line 62) but no file input, so `photo` is always null. If one is added, note that edit uses `form.put` (line 101); Inertia file uploads need POST with method spoofing.
- Suspected issue: photo URLs are built with `Storage::url()` on the **default** disk (`app/Http/Controllers/MomentController.php:112`, `app/Http/Resources/MomentResource.php:18`) while files are written to the `public` disk. Works only while the default disk also resolves to `/storage/...`; breaks if `FILESYSTEM_DISK` is changed (e.g. s3).
- Suspected issue: `moments/Index.vue` shows Edit, Delete and the favourite star on every card, including the partner's (lines 232, 242, 248); the controller sends `is_owner` but the page ignores it. The partner gets a 403. `moments/Show.vue` gates correctly with `is_owner`.
- Suspected issue: pagination is returned (`pagination` prop) but `moments/Index.vue` renders no pager, so only the newest 12 moments are reachable.
- Suspected issue: `ziggy-js` `route()` is used for delete/favourite (`moments/Index.vue:117,121`, `moments/Show.vue:36,40,52`) and `timeline/Index.vue:35` calls `route()` without importing it. No Ziggy config is provided anywhere (no `tightenco/ziggy` in `composer.json`, no `@routes` in `resources/views/app.blade.php`), so these calls are expected to throw in the browser. Other pages use Wayfinder helpers from `@/routes/*`. Not verified in a running browser.
- Suspected issue: `date_night_plan_id` is validated with `exists` only, not ownership, so a moment can be linked to another couple's plan and its `theme` is then echoed back in `formatMoment`. The current UI never sends this field.
- Suspected issue: timeline `occurred_at` mixes formats (ISO-8601 for responses/plans/notes, `Y-m-d` for moments/calendar, raw DB string for achievement `unlocked_at`) and is sorted as strings (`app/Services/TimelineBuilderService.php:49`), so ordering within a day is unreliable.
- Suspected issue: timeline plan entries only match plans via the user's responses (`TimelineBuilderService.php:88-91`); `DashboardController` additionally matches `partner_user_id`, so some plans may be missing from the timeline. Completed questionnaires use `updated_at`, not `completed_at`.
- Dead code: `TimelineEntry` model + `timeline_entries` table, and `MomentResource`.

## Related modules

- challenges-achievements-streaks.md - `moments_created` achievements, evaluation job.
- notifications-activity-mail.md - `AppNotification`, activity log, mail.
- events-jobs.md - queues and listener registration (`app/Providers/EventServiceProvider.php`).
- date-night.md - `date_night_plan_id` link and plan entries in the timeline.
- love-notes.md, calendar.md, questionnaires.md - other timeline sources.
- dashboard-insights-search.md - relationship hub, insights and search also read moments.
- onboarding-partner.md - `partner_id` drives sharing.

## Tests

- `tests/Feature/MomentTest.php` - index, create, delete own, cannot delete other's, toggle favourite.
- `tests/Feature/TimelineTest.php` - page loads, filter echoed, moments appear.

Run: `php artisan test --filter="MomentTest|TimelineTest"`

Not covered: partner visibility, `private_notes` hiding, update, photo upload, notification/mail, non-moment timeline sources.

## Change log

- 2026-11-24 — Initial doc.
