# Notifications, Activity Log and Mail
> Last verified against code: 2026-11-24

## Purpose
Three ways the app records or announces that something happened: in-app notifications (bell icon + `/notifications` list), an append-only activity log (written, never read by any UI), and outbound email. All three are driven almost entirely by domain events; see [events-jobs.md](events-jobs.md) for the full event/listener/job catalog.

## Key files
| Path | Role |
|---|---|
| **Backend - notifications** | |
| `app/Models/AppNotification.php` | Model on table `app_notifications`; `isRead()`, `markAsRead()` |
| `app/Enums/NotificationType.php` | 9 backed string cases (see table below) |
| `app/Services/NotificationService.php` | Helper methods that create notifications; only 3 of 6 methods are called |
| `app/Http/Controllers/NotificationController.php` | `index`, `markRead`, `markAllRead`, `unreadCount` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shares `unreadNotificationsCount` on every Inertia response (line 45) |
| `app/Http/Resources/NotificationResource.php` | Unused (controller maps the array by hand) |
| `app/Events/NotificationCreated.php` | Unused (never dispatched, not registered) |
| `app/Console/Commands/SendRemindersCommand.php` | `datenight:send-reminders`, daily 09:00, creates reminder notifications |
| **Backend - activity log** | |
| `app/Models/ActivityLog.php` | Model on table `activity_log`; `user()`, morph `subject()` |
| `app/Services/ActivityLogService.php` | `log(User, string $action, ?Model $subject, array $metadata)` - writes a row and a `Log::info('activity')` line |
| `app/Listeners/LogActivityOn*.php` | 5 synchronous listeners that call the service |
| **Backend - mail** | |
| `app/Mail/*.php` | 6 Mailables, each renders a Blade view |
| `resources/views/emails/*.blade.php` | 6 standalone HTML emails (inline `<style>`, no shared layout, not Markdown mail) |
| `app/Jobs/Send*Job.php` | 6 jobs that send mail (two also create the in-app notification) |
| `config/mail.php`, `config/services.php` | Default mailer `env('MAIL_MAILER','log')`; `resend` transport defined, key from `RESEND_API_KEY` (`resend/resend-laravel` in composer.json) |
| `app/Models/EmailTemplate.php` | DB table `email_templates`, edited in admin only. **Not used by any Mailable.** |
| **Frontend** | |
| `resources/js/pages/notifications/Index.vue` | List, mark read, click-through routing by type |
| `resources/js/layouts/MobileLayout.vue` | Bell link + unread badge (reads `page.props.unreadNotificationsCount`, shows `9+` above 9) |
| **Tests** | |
| `tests/Feature/ActivityLog/ActivityLogServiceTest.php` | Service writes rows with/without subject |
| `tests/Feature/Listeners/QuestionnaireCompletedListenersTest.php` | Covers partner notification + email job dispatch |
| `tests/Feature/DateNightPlanTest.php` | Covers `PartnerLikedPlan` notification |

## Data model
- **`app_notifications`** (migration `2026_07_13_000004_create_notifications_table.php` - file name says `notifications`, table is `app_notifications`): `user_id` (cascade delete), `type` string, `title`, `body` nullable text, `data` nullable json, `read_at` nullable. Indexes `(user_id, read_at)`, `type`. Casts: `type` -> `NotificationType`, `data` -> array, `read_at` -> datetime.
- **`activity_log`** (`2026_07_12_200000_create_activity_log_table.php`): `user_id` (cascade), `action` string, nullable morph `subject_type`/`subject_id`, `metadata` nullable json (cast array; empty array stored as `null`). Indexes `(user_id, created_at)`, `action`.
- **`email_templates`** (`2026_07_12_161522_create_email_templates_table.php`): `key` unique, `label`, `subject`, `html_body`, `text_body`, `variables` json, `active`. No seeder.
- **`users.email_notifications`** boolean default true (`2026_07_12_000001_extend_users_table.php`); `push_notifications` also exists.
- Laravel's built-in database notifications are not used, even though `User` has the `Notifiable` trait.

### Notification types
| Type (value) | Created by | Recipient | Trigger |
|---|---|---|---|
| `partner_completed` | `NotifyPartnerOnQuestionnaireCompleted` | Partner | Non-solo questionnaire completed and user has a partner. `data`: `questionnaire_id`, `questionnaire_slug` |
| `partner_completed` (reused) | `CreatePartnerConnectedNotificationOnPartnerConnected` | Both users | Invitation accepted (`PartnerConnected`) |
| `partner_completed` (reused) | `NotifyOnAchievementUnlocked` | The user | `AchievementUnlocked`. `data`: `achievement_id` |
| `partner_completed` (reused) | `SendRemindersCommand` | Onboarded users with a partner whose `last_completed_questionnaire_at` is null or older than 7 days | Scheduler, daily 09:00 |
| `date_night_plan_ready` | `NotifyPlanReadyOnDateNightPlanGenerated` | `userOne` and `userTwo` (if not null) | `DateNightPlanGenerated`. `data`: `plan_id` |
| `love_note_received` | `SendLoveNoteNotificationJob` | Note recipient | `LoveNoteSent`. `data`: `love_note_id` |
| `moment_created` | `SendMomentNotificationJob` | Creator's partner | `MomentCreated` (only if a partner exists). `data`: `moment_id` |
| `partner_viewed_plan` | `NotificationService::notifyPartnerViewedPlan` from `DateNightPlanController@show` (line 39) | Partner | Every time the plan page is opened, if the partner can view the plan |
| `partner_liked_plan` | `NotificationService::notifyPartnerLikedPlan` from `LikeDateNightPlanAction` (line 36) | Partner | Like added (not on unlike) |
| `partner_added_date_night_to_calendar` | `NotificationService::notifyPartnerAddedDateNightToCalendar` from `AddDateNightPlanToCalendarAction` (line 42) | Partner | Plan added to calendar. `data`: `plan_id`, `calendar_event_id` |
| `compatibility_ready` | nothing | - | Never created |
| `daily_challenge` | nothing | - | Never created |

### Mailables
All render Blade views in `resources/views/emails/`. None read `EmailTemplate`.

| Mailable (view) | `ShouldQueue` | Sent by job (queue) | Trigger | Recipient | Checks `email_notifications` |
|---|---|---|---|---|---|
| `PartnerInvitationMail` (`partner-invitation`) | No | `SendInvitationEmailJob` (`emails`) | `SendPartnerInvitationAction` line 20; `PartnerController@resendInvite` line 52 | `invitation->email` | n/a; job skips accepted or expired invitations |
| `PartnerConnectedMail` (`partner-connected`) | Yes | `SendPartnerConnectedEmailJob` (`emails`) | `PartnerConnected` | Both users | Yes |
| `DateNightReadyMail` (`date-night-ready`) | Yes | `SendSummaryEmailJob` (`emails`) | `DateNightPlanGenerated` | `userOne`, `userTwo` if set | Yes |
| `LoveNoteReceivedMail` (`love-note-received`) | Yes | `SendLoveNoteNotificationJob` (`notifications`) | `LoveNoteSent` | Note recipient | No |
| `MomentCreatedMail` (`moment-created`) | Yes | `SendMomentNotificationJob` (`notifications`) | `MomentCreated` | Creator's partner | No |
| `QuestionnaireCompletedMail` (`questionnaire-completed`) | Yes | `SendQuestionnaireCompletedNotificationJob` (`notifications`) | `QuestionnaireCompleted` (non-solo, has partner) | Partner | No |

### Activity log actions
| `action` | Listener | Logged for | Subject / metadata |
|---|---|---|---|
| `partner_connected` | `LogActivityOnPartnerConnected` | Both users (2 rows) | The other `User` |
| `questionnaire_completed` | `LogActivityOnQuestionnaireCompleted` | Completing user | `Questionnaire`; `response_id` |
| `love_note_sent` | `LogActivityOnLoveNoteSent` | Sender | `LoveNote`; `recipient_id` |
| `moment_created` | `LogActivityOnMomentCreated` | Creator | `Moment` |
| `achievement_unlocked` | `LogActivityOnAchievementUnlocked` | The user | `Achievement`; `points` |

## Routes
All under middleware `auth`, `verified`, `onboarding` (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `notifications.index` | GET `/notifications` | `NotificationController@index` | Latest 50, newest first, no pagination |
| `notifications.mark-read` | POST `/notifications/{notification}/read` | `@markRead` | `abort(403)` if not the owner; `back()` |
| `notifications.mark-all-read` | POST `/notifications/read-all` | `@markAllRead` | Bulk update of the user's unread rows |
| `notifications.unread-count` | GET `/notifications/unread-count` | `@unreadCount` | JSON `{count}`; not called by any Vue code |

There are no routes for the activity log. Email template admin routes live in `routes/admin.php` (see [admin.md](admin.md)).

## Flows
**Unread count reaching the frontend**
1. `HandleInertiaRequests::share()` runs a `COUNT` on `app_notifications` for the logged-in user on every Inertia request and shares `unreadNotificationsCount` (0 for guests).
2. `MobileLayout.vue` renders the badge from that prop. There is no polling or broadcasting; the count refreshes on the next Inertia visit.

**Reading a notification** (`notifications/Index.vue`)
1. Click posts to `/notifications/{id}/read` with `preserveScroll`.
2. On success the page navigates by type: `partner_completed` with `data.questionnaire_slug` -> `/questionnaires/{slug}`; `date_night_plan_ready` and `partner_liked_plan` -> `/date-night/{plan_id}`; `love_note_received` -> `/love-notes/{id}` or `/love-notes`; `moment_created` -> `/moments/{id}` or `/moments`. Other types only get marked read.

**Typical event-driven notification + email** (love note example)
1. `SendLoveNoteAction` dispatches `LoveNoteSent`.
2. Sync: `LogActivityOnLoveNoteSent` writes `activity_log`.
3. Queued listener `SendLoveNoteNotificationOnLoveNoteSent` (queue `notifications`) dispatches `SendLoveNoteNotificationJob` (queue `notifications`).
4. The job creates the `AppNotification`, then `Mail::...->queue(new LoveNoteReceivedMail)` which is queued a third time on the connection's default queue.

## Rules and gotchas
- **Blade views, not DB templates.** Editing an `EmailTemplate` row in admin changes nothing that is sent. To change an email, edit the Mailable and its Blade view.
- `Mail::to()->send()` with a `ShouldQueue` Mailable still queues it (framework behaviour), so every email except the invitation takes three queue hops: listener -> job -> mailable.
- Queue names matter: see the worker warning in [events-jobs.md](events-jobs.md). With the stock `composer dev` worker only the `default` queue is processed, so notifications/emails created on the `notifications` and `emails` queues do not appear locally unless a worker listens to those queues. Tests use `QUEUE_CONNECTION=sync` and `MAIL_MAILER=array` (`phpunit.xml`).
- Notifications are created in three different styles: `NotificationService`, direct `AppNotification::create()` in listeners/jobs/commands, and nothing fires `NotificationCreated`. Follow the nearest existing example for the feature you touch.
- `QuestionnaireService::wipeOverhangingPendingResponses()` deletes the partner's `partner_completed` notifications whose `data->questionnaire_id` matches, when a user re-completes a questionnaire before the partner answered.
- Authorization is a manual owner check in `markRead`; there is no policy.
- `LogActivityOn*` listeners are synchronous: a DB failure in the activity log will throw inside the request (or job) that dispatched the event.
- Suspected issue: `NotificationType::PartnerCompleted` is reused for partner-connected, achievement-unlocked and reminder notifications (`app/Listeners/CreatePartnerConnectedNotificationOnPartnerConnected.php:25`, `app/Listeners/NotifyOnAchievementUnlocked.php:18`, `app/Console/Commands/SendRemindersCommand.php:29`). They all show the party emoji and cannot be filtered by type.
- Suspected issue: `app/Listeners/NotifyOnAchievementUnlocked.php:19` reads `$event->achievement->title`, but the `achievements` column is `name`, so the title renders as "Achievement unlocked: !".
- Suspected issue: `DateNightPlanController@show` (line 39) creates a `partner_viewed_plan` notification on every page view with no de-duplication.
- Suspected issue: `SendRemindersCommand` has no "already reminded" check, so an inactive couple gets a new reminder every day.
- Suspected issue: `email_notifications` is only honoured by `SendPartnerConnectedEmailJob` and `SendSummaryEmailJob`; love note, moment and questionnaire-completed emails ignore it. No controller or page lets the user change the flag.
- Suspected issue: `app/Mail/PartnerInvitationMail.php:23` builds the subject from `sender->display_name` with no fallback to `name` (the body on line 32 has the fallback).
- Suspected issue: `notifications/Index.vue` has no click target for `partner_viewed_plan` or `partner_added_date_night_to_calendar` even though both carry `plan_id`, and `typeEmoji` has no entry for the calendar type.
- Dead code: `NotificationResource`, `NotificationCreated`, `NotificationService::notifyPartnerCompleted/notifyDateNightPlanReady/notifyLoveNoteReceived`, enum cases `CompatibilityReady` and `DailyChallenge`, route `notifications.unread-count`, `SendInvitationEmailOnPartnerInvitationCreated` (unregistered; invitation mail is dispatched directly).
- Dead data: nothing reads `activity_log`. `AppNotification` and `ActivityLog` use `HasFactory` but have no factory class in `database/factories`.

## Related modules
- [events-jobs.md](events-jobs.md) - who fires what, queue names
- [love-notes.md](love-notes.md), [moments-timeline.md](moments-timeline.md), [date-night.md](date-night.md), [questionnaires.md](questionnaires.md), [onboarding-partner.md](onboarding-partner.md), [challenges-achievements-streaks.md](challenges-achievements-streaks.md) - the features that trigger notifications and mail
- [admin.md](admin.md) - email template editor
- [frontend.md](frontend.md) - `MobileLayout`, shared Inertia props

## Tests
- `tests/Feature/ActivityLog/ActivityLogServiceTest.php` - `php artisan test --filter=ActivityLogServiceTest`
- `tests/Feature/Listeners/QuestionnaireCompletedListenersTest.php` - `php artisan test --filter=QuestionnaireCompletedListenersTest`
- `PartnerLikedPlan` assertions - `php artisan test --filter=DateNightPlanTest`
- No feature test hits `NotificationController`, and no test asserts on any Mailable.

## Change log
- 2026-11-24 — Initial doc.
