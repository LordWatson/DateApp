# Notifications, Activity Log and Mail
> Last verified against code: 2026-11-25

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
- **`users.email_notifications`** boolean default true (`2026_07_12_000001_extend_users_table.php`), changed at `/settings/notifications`. It is the master switch: every job that sends a notification email asks `User::wantsEmail(NotificationCategory)`, which also honours the per-category choices in `users.notification_preferences` (see `auth-settings.md`); the in-app notification is created either way. The five notification emails carry a signed "Stop emails like this" link for their category. Account mail (verification, password reset) and the invitation email ignore it. `push_notifications` switches push notifications off (see [mobile-push.md](mobile-push.md)).
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
| `partner_left` | `NotifyFormerPartnerOnPartnerDisconnected` (also `DeleteAccountAction`) | The other partner | `PartnerDisconnected`, or the partner deleting their account |
| `invitation_expired` | `ExpireInvitationsCommand` | Invitation sender | Daily 00:10, once per invitation that ran out unanswered |
| `data_export_ready` | `ExportUserDataJob` | The user | Their data export file is ready (links to `/settings/profile`) |
| `moment_created` | `SendMomentNotificationJob` | Creator's partner | `MomentCreated` (only if a partner exists). `data`: `moment_id` |
| `partner_viewed_plan` | `NotificationService::notifyPartnerViewedPlan` from `DateNightPlanController@show` (line 39) | Partner | Every time the plan page is opened, if the partner can view the plan |
| `partner_liked_plan` | `NotificationService::notifyPartnerLikedPlan` from `LikeDateNightPlanAction` (line 36) | Partner | Like added (not on unlike) |
| `partner_added_date_night_to_calendar` | `NotificationService::notifyPartnerAddedDateNightToCalendar` from `AddDateNightPlanToCalendarAction` (line 42) | Partner | Plan added to calendar. `data`: `plan_id`, `calendar_event_id` |
| `partner_nudge` | `NudgePartnerAction` (button on the waiting screen, or `datenight:nudge-partners`) | Partner | A couple attempt has been waiting for their answers. `data`: `questionnaire_id`, `questionnaire_slug` |
| `love_note_reaction` | `LoveNoteController@react` | Note sender | The recipient's first reaction to a note (not on change or clear). `data`: `love_note_id` |
| `connection_anniversary` | `ConnectionAnniversariesCommand` | Both partners | Daily 09:00, on the anniversary of `users.connected_at`. `data`: `years` |
| `partner_completed_plan` | `NotificationService::notifyPartnerCompletedPlan` from `CompleteDateNightPlanAction` | Partner | First time a person marks a plan as done. `data`: `plan_id` |
| `date_night_plan_ready` (reused) | `NotificationService::notifyPartnerChangedPlan` from `DateNightPlanController@regenerate` | Partner | A plan was rewritten with a steer. `data`: `plan_id` |
| `connection_paused`, `connection_resumed` | `PartnerController@pause` / `@resume` | The other partner | Once per pause or resume |
| `compatibility_ready` | nothing | - | Never created |
| `daily_challenge` | nothing | - | Never created |

### Mailables
All render Blade views in `resources/views/emails/`. None read `EmailTemplate`.

| Mailable (view) | `ShouldQueue` | Sent by job (queue) | Trigger | Recipient | Checks `email_notifications` |
|---|---|---|---|---|---|
| `PartnerInvitationMail` (`partner-invitation`; never sent for a link invitation, which has no address) | No | `SendInvitationEmailJob` (`emails`) | `SendPartnerInvitationAction` line 20; `PartnerController@resendInvite` line 52 | `invitation->email` | n/a; job skips accepted or expired invitations |
| `PartnerConnectedMail` (`partner-connected`) | Yes | `SendPartnerConnectedEmailJob` (`emails`) | `PartnerConnected` | Both users | Yes |
| `DateNightReadyMail` (`date-night-ready`) | Yes | `SendSummaryEmailJob` (`emails`) | `DateNightPlanGenerated` | `userOne`, `userTwo` if set | Yes |
| `LoveNoteReceivedMail` (`love-note-received`) | Yes | `SendLoveNoteNotificationJob` (`notifications`) | `LoveNoteSent` | Note recipient | Yes |
| `MomentCreatedMail` (`moment-created`) | Yes | `SendMomentNotificationJob` (`notifications`) | `MomentCreated` | Creator's partner | Yes |
| `QuestionnaireCompletedMail` (`questionnaire-completed`) | Yes | `SendQuestionnaireCompletedNotificationJob` (`notifications`) | `QuestionnaireCompleted` (non-solo, has partner) | Partner | Yes |

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
- Notifications are created in two styles: `NotificationService` (plan viewed, plan liked, plan added to calendar) and direct `AppNotification::create()` in listeners/jobs/commands. Follow the nearest existing example for the feature you touch.
- `QuestionnaireService::wipeOverhangingPendingResponses()` deletes the partner's `partner_completed` notifications whose `data->questionnaire_id` matches, when a user re-completes a questionnaire before the partner answered.
- Every `AppNotification::create()` also sends a push notification through the model's `created` hook (see [mobile-push.md](mobile-push.md)); do not send one by hand.
- Authorization is a manual owner check in `markRead`; there is no policy.
- `LogActivityOn*` listeners are synchronous: a DB failure in the activity log will throw inside the request (or job) that dispatched the event.
- Suspected issue: `NotificationType::PartnerCompleted` is reused for partner-connected, achievement-unlocked and reminder notifications (`app/Listeners/CreatePartnerConnectedNotificationOnPartnerConnected.php:25`, `app/Listeners/NotifyOnAchievementUnlocked.php:18`, `app/Console/Commands/SendRemindersCommand.php:29`). They all show the party emoji and cannot be filtered by type.
- Suspected issue: `app/Listeners/NotifyOnAchievementUnlocked.php:19` reads `$event->achievement->title`, but the `achievements` column is `name`, so the title renders as "Achievement unlocked: !".
- Suspected issue: `DateNightPlanController@show` (line 39) creates a `partner_viewed_plan` notification on every page view with no de-duplication.
- Suspected issue: `SendRemindersCommand` has no "already reminded" check, so an inactive couple gets a new reminder every day.
- Suspected issue: `app/Mail/PartnerInvitationMail.php:23` builds the subject from `sender->display_name` with no fallback to `name` (the body on line 32 has the fallback).
- Suspected issue: `notifications/Index.vue` has no click target for `partner_viewed_plan` or `partner_added_date_night_to_calendar` even though both carry `plan_id`, and `typeEmoji` has no entry for the calendar type.
- Dead code: enum cases `CompatibilityReady` and `DailyChallenge`, route `notifications.unread-count`.
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
- 2026-11-24 — Roadmap Phase 1: new types `partner_connected`, `achievement_unlocked`, `questionnaire_reminder`, `plan_failed`; every type links somewhere (`destination()` in `notifications/Index.vue`); "partner viewed plan" is sent once per plan; the weekly reminder is sent at most once every 7 days.
- 2026-11-24 — Roadmap Phase 2 (2.3, 2.4): new type `calendar_reminder` (hourly `SendCalendarRemindersCommand`, links to the event); `daily_challenge` is now sent to the partner when a daily challenge is completed.
- 2026-11-24 — Roadmap Phase 2 (2.6, 2.12, 2.14): love note, moment and questionnaire-completed emails honour `email_notifications` (settable in settings); new types `invitation_expired` and `data_export_ready`; `partner_left` is also sent on disconnect.
- 2026-11-26 — Roadmap Phase 4: removed `NotificationResource`, `NotificationCreated`, the three uncalled `NotificationService` methods and the unregistered invitation listener.
- 2026-11-25 — Roadmap Phase 5A: new types `partner_nudge`, `love_note_reaction`, `connection_anniversary`, each with a link and emoji in `notifications/Index.vue`. The nudge reuses `QuestionnaireCompletedMail`.
- 2026-11-25 — Roadmap Phase 5B: new type `partner_completed_plan`; `date_night_plan_ready` is also sent when a partner changes a plan.
- 2026-11-25 — Roadmap Phase 5.8: `SendInvitationEmailJob` skips invitations with no email.
- 2026-11-25 — Roadmap Phase 5.10: mail jobs use `User::wantsEmail()` with a category; emails carry an unsubscribe link; `datenight:send-reminders` follows each user's reminder frequency.
- 2026-11-25 — Roadmap Phase 5.12: `connection_paused` and `connection_resumed`; `partner_left` opens the "what you keep" page; partner notifications and emails are not sent while a connection is paused.
- 2026-11-25 — Every notification is also sent as a push notification (`SendPushNotificationJob`).
