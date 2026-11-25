# Moments and Timeline

> Last verified against code: 2026-11-25

## Purpose

**Moments** are a shared memory journal: a user records a titled, dated memory with optional description, mood emoji, tags, up to three photos and owner-only private notes; the partner can see it and is notified. **Timeline** is a read-only chronological feed that merges the user's completed questionnaires, date night plans, moments, love notes, calendar events and unlocked achievements.

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
| `app/Http/Controllers/TimelineController.php` | `timeline.index`; passes filter list |
| `app/Services/TimelineBuilderService.php` | Builds the merged feed at request time |
| `database/factories/MomentFactory.php` | Factory |
| **Frontend** | |
| `resources/js/pages/moments/Index.vue` | Card list + create/edit bottom-sheet form (photo grid picker); opens the edit form for `?edit=<id>` |
| `resources/js/pages/moments/Show.vue` | Detail page; owner-only actions (edit, delete) and private notes |
| `resources/js/components/PhotoCarousel.vue` | Photos shown whole, one at a time: swipe (CSS scroll snap), arrows, dots, counter; `zoomable` opens them full screen |
| `resources/js/pages/timeline/Index.vue` | Filter tabs + feed |
| **Tests** | |
| `tests/Feature/MomentTest.php`, `tests/Feature/TimelineTest.php` | Feature tests |

## Data model

- `moments`: `user_id` (FK, cascade), `date_night_plan_id?` (FK, null on delete), `title`, `description?`, `mood?` (free string, UI offers emojis), `photos?` (JSON list of paths on the uploads disk, `config('filesystems.uploads')`, cast `array`, at most `Moment::MAX_PHOTOS` = 3, the first is the cover; replaced the single `photo` column), `date` (cast `date`), `is_favourite` (bool), `private_notes?`, `tags?` (JSON, cast `array`). Indexes: `(user_id, date)`, `is_favourite`.
- A moment belongs to one user. "Shared with partner" is derived from `users.partner_id` at read time; there is no couple id.

## Routes

All inside `auth`, `verified`, `onboarding` middleware (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `moments.index` | GET `/moments` | `MomentController@index` | Own + partner's moments, `date` desc, paginated 12 |
| `moments.store` | POST `/moments` | `MomentController@store` | `StoreMomentRequest`; fires `MomentCreated` |
| `moments.show` | GET `/moments/{moment}` | `MomentController@show` | Policy `view` |
| `moments.photo` | GET `/moments/{moment}/photo/{index?}` | `MomentController@photo` | Policy `view` (owner or partner); streams photo number `index` (default 0) from the uploads disk; 404 when there is none |
| `moments.update` | PUT `/moments/{moment}` | `MomentController@update` | Policy `update`; same request class as store. The page sends POST with `_method: 'put'` so a file can be carried |
| `moments.destroy` | DELETE `/moments/{moment}` | `MomentController@destroy` | Policy `delete`; deletes the photo files |
| `moments.toggle-favourite` | POST `/moments/{moment}/favourite` | `MomentController@toggleFavourite` | Policy `update` |
| `timeline.index` | GET `/timeline?filter=` | `TimelineController@index` | Filters: `all`, `questionnaires`, `plans`, `moments`, `love_notes`, `calendar`, `achievements` |

## Flows

### Create a moment

1. `moments/Index.vue` posts the form to `moments.store`.
2. `StoreMomentRequest` validates. Each file in `photos` is stored with `store('moments', config('filesystems.uploads'))` and the list of paths saved.
3. Moment is created through `$request->user()->moments()`.
4. `MomentCreated::dispatch($user, $moment)`:
   - `SendMomentNotificationOnMomentCreated` (queued) -> if partner exists -> `SendMomentNotificationJob` (`notifications` queue, 3 tries, 30 s) -> `AppNotification` of type `moment_created` for the partner + queued `MomentCreatedMail`.
   - `LogActivityOnMomentCreated` (sync) -> activity log.
   - `EvaluateAchievementsOnMomentCreated` (queued) -> `AchievementEvaluationJob`.
5. Redirect to `moments.index` with flash `success`.

### Update / delete / favourite

1. Owner-only via policy. Update keeps every saved photo unless its position is listed in `remove_photos` (those files are deleted), then appends the new files in `photos`; with neither field the photos are left alone. Delete removes the files then the row. An update with no `tags` clears them (a multipart form cannot send an empty list).
2. No event is fired for update, delete or favourite.

### Timeline

1. `TimelineController@index` reads `filter` (default `all`); unknown values fall back to `all` inside the service (the page still receives the raw value as `filter`).
2. `TimelineBuilderService::build()` queries each source (max 20 rows per type, achievements unlimited), maps to `{id, type, title, description, emoji, occurred_at, link, ...}` and sorts by `occurred_at` desc.
3. Only date night plan entries carry a `link` (`date-night.show`).

### Capture from a date night

1. `GET /moments?plan={id}` (the link on a plan marked as done): when the user can view that plan, `index` sends `prefill` (`date_night_plan_id`, `title` = the plan's theme, `date` = the user's completion date or their today); otherwise `prefill` is null.
2. `moments/Index.vue` opens the create form with those values and submits `date_night_plan_id`. The form now always sends that field, so an edit carries the moment's existing link (`openEdit` copies it) instead of clearing it.
3. Moments linked to a plan are listed on the plan page for the owner and their partner (see `date-night.md`).

## Rules and gotchas

- **Who can see / edit**:
  - View: owner and the owner's current partner. Enforced in `MomentPolicy::view` (show) and by the `whereIn('user_id', [me, partner_id])` query in `MomentController@index`.
  - Edit, delete, favourite: owner only (`MomentPolicy::update` / `delete`, called via `$this->authorize()` in the controller).
  - `private_notes` are returned only to the owner (`MomentController::formatMoment`).
  - Policies are not registered explicitly; they rely on Laravel's `App\Policies\{Model}Policy` auto-discovery.
  - Timeline is strictly personal: `TimelineController` passes only `$request->user()`. It shows the user's **own** moments and calendar events (not the partner's), and love notes both sent and received.
- Validation (`StoreMomentRequest`): `title` required max 255; `date` required; `description` and `private_notes` max 2000; `mood` max 50; `photos` a list of images, max 5120 KB each, at most 3 counting the saved photos that stay (`photos.N` carries a bad file's error, `photos` the limit); `remove_photos` a list of positions; `tags` array of strings max 50 each; `date_night_plan_id` must be a plan the author can view. The same rules apply to update, so `title` and `date` are always required.
- **Photos / storage**: photos are private. Files go to the uploads disk (`config('filesystems.uploads')`, env `UPLOADS_DISK`, default the private `local` disk, `storage/app/private/moments`) and are only readable through `moments.photo`, which checks the moment policy and streams the file with `Storage::disk(...)->response()`. No `storage:link` is needed and nothing is reachable by URL alone. The page prop `photos` is a list of that route's URLs (`Moment::photoUrls()`), each with `?v=<file name>`, so a replaced photo is not served from the browser cache (the response is `Cache-Control: private, max-age=3600`). The plan page still gets one `photo` (the cover). There is no resizing or thumbnailing, and photos are never cropped: `PhotoCarousel` scales them down to fit (`object-contain`) and never up. The table does not record the disk: after changing `UPLOADS_DISK`, run `php artisan datenight:move-uploads --from=<old disk>` (default `public`; copies then deletes, safe to run again). `DeleteAccountAction` deletes the owner's photos from the same disk.
- Three photos of 5 MB each need PHP's `post_max_size` and `upload_max_filesize` (and the web server's body limit) to allow about 16 MB in one request; a smaller limit fails the whole save.
- Edit on the moment page goes to `/moments?edit=<id>`, which only opens the form when that moment is on the first page of the list (12 newest).
- **Today**: `moments.index` sends `today` (`User::today()`, the user's own date) and the form uses it as the default date. The timeline's calendar entries stop at the user's own today.
- Disconnecting a partner immediately hides moments from the ex-partner (visibility is computed from `partner_id`).
- Email is sent to the partner regardless of the user's `email_notifications` setting (the job does not check it).
- Suspected issue: `moments/Index.vue` shows Edit, Delete and the favourite star on every card, including the partner's (lines 232, 242, 248); the controller sends `is_owner` but the page ignores it. The partner gets a 403. `moments/Show.vue` gates correctly with `is_owner`.
- Suspected issue: pagination is returned (`pagination` prop) but `moments/Index.vue` renders no pager, so only the newest 12 moments are reachable.
- Suspected issue: `date_night_plan_id` is validated with `exists` only, not ownership, so a moment can be linked to another couple's plan and its `theme` is then echoed back in `formatMoment`. The current UI never sends this field.
- Suspected issue: timeline `occurred_at` mixes formats (ISO-8601 for responses/plans/notes, `Y-m-d` for moments/calendar, raw DB string for achievement `unlocked_at`) and is sorted as strings (`app/Services/TimelineBuilderService.php:49`), so ordering within a day is unreliable.
- Suspected issue: timeline plan entries only match plans via the user's responses (`TimelineBuilderService.php:88-91`); `DashboardController` additionally matches `partner_user_id`, so some plans may be missing from the timeline. Completed questionnaires use `updated_at`, not `completed_at`.

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
- `tests/Feature/MomentPhotoTest.php` - upload on create and edit, new photos go after the saved ones and each is served by its index, the limit of three, replace and remove delete the old file, edit without a file keeps it, size and type rejected, partner cannot change it; owner and partner can fetch the photo, a stranger gets 403 and a guest is sent to login; nothing is written to the `public` disk. `tests/Feature/Commands/MoveUploadsCommandTest.php` covers the move command.
- `tests/Feature/TimelineTest.php` - page loads, filter echoed, moments appear.

Run: `php artisan test --filter="MomentTest|MomentPhotoTest|TimelineTest"`

`tests/Feature/DateNightPlanCompletionTest.php` covers the `prefill` prop and a moment created with a plan id.

Not covered: partner visibility, `private_notes` hiding, notification/mail, non-moment timeline sources.

## Change log

- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 0: a moment can only be linked to a plan its author can view (`StoreMomentRequest`).
- 2026-11-24 — Roadmap Phase 1: delete and favourite work (Wayfinder), delete asks first, edit/delete/star only on your own moments, pager added; timeline includes the partner's moments and past events only, links every entry and uses one ISO date format.
- 2026-11-24 — Roadmap Phase 2 (2.5): photo upload with preview and remove on the create/edit form; edits post with `_method: 'put'`; photo URL comes from the `public` disk; an edit without a new file no longer clears the photo.
- 2026-11-25 — Roadmap Phase 3 (3.4, 3.14): photos are private on the configurable uploads disk and served by the new `moments.photo` route; `datenight:move-uploads` moves old files; the default date comes from the user's timezone.
- 2026-11-26 — Roadmap Phase 4: removed `MomentResource`, the `TimelineEntry` model and `User::timelineEntries()`; a new migration drops the never-used `timeline_entries` table. The timeline is still built on the fly.
- 2026-11-25 — Roadmap Phase 5.2: `?plan=` prefills the create form and the form sends `date_night_plan_id`.
- 2026-11-25 — Up to three photos per moment (`photos` JSON list replaces `photo`, `moments.photo` takes an index, `remove_photos`); new `PhotoCarousel` shows them uncropped with swipe, and full screen on the moment page; photo grid picker in the form; Edit on the moment page.
