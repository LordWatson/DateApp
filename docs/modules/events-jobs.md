# Events, Listeners and Jobs (catalog)
> Last verified against code: 2026-11-25

## Purpose
Reference for every domain event, listener and queued job in the app: where each is dispatched, what is wired to it, and which queue it runs on. Read this before adding a side effect to a user action, and before debugging "the notification/email/plan never arrived".

## Key files
| Path | Role |
|---|---|
| **Backend** | |
| `app/Providers/EventServiceProvider.php` | The only event-to-listener map (`$listen`). `shouldDiscoverEvents()` returns `false` |
| `bootstrap/app.php` | Registers the provider (`withProviders`), `withEvents(discover: false)`, and the scheduler entries |
| `app/Events/*.php` | 10 events, all `final`, `Dispatchable` + `SerializesModels`, readonly constructor props |
| `app/Listeners/*.php` | 20 listeners, named `<Action>On<Event>` |
| `app/Jobs/*.php` | 10 jobs, all `ShouldQueue` |
| `config/queue.php` | Default connection `env('QUEUE_CONNECTION','database')`; failed jobs driver `database-uuids`, table `failed_jobs` |
| `app/Providers/AppServiceProvider.php` | Drops the `logs` (Pail) dev command when `pcntl` is missing (Windows) |
| **Tests** | |
| `tests/Feature/Events/*`, `tests/Feature/Listeners/*`, `tests/Feature/Jobs/*` | See Tests section |

## Events
"Registered" means present as a key in `EventServiceProvider::$listen`.

| Event | Dispatched from | Registered listeners | Flag |
|---|---|---|---|
| `PartnerConnected` | `app/Actions/AcceptPartnerInvitationAction.php:25` | `SendPartnerConnectedEmailsOnPartnerConnected`, `CreatePartnerConnectedNotificationOnPartnerConnected`, `LogActivityOnPartnerConnected` | |
| `QuestionnaireCompleted` | `app/Services/QuestionnaireService.php:178` | `UpdateStreakOnQuestionnairCompleted`, `NotifyPartnerOnQuestionnaireCompleted`, `DispatchDateNightPlanOnQuestionnaireCompleted`, `EvaluateAchievementsOnQuestionnaireCompleted`, `LogActivityOnQuestionnaireCompleted` | |
| `DateNightPlanGenerated` | `app/Actions/GenerateDateNightPlanAction.php:50` (solo), `:72` (couple) | `SendPlanEmailsOnDateNightPlanGenerated`, `NotifyPlanReadyOnDateNightPlanGenerated` | |
| `LoveNoteSent` | `app/Actions/SendLoveNoteAction.php:19` | `SendLoveNoteNotificationOnLoveNoteSent`, `LogActivityOnLoveNoteSent`, `EvaluateAchievementsOnActivity` | |
| `MomentCreated` | `app/Http/Controllers/MomentController.php:59` | `SendMomentNotificationOnMomentCreated`, `LogActivityOnMomentCreated`, `EvaluateAchievementsOnMomentCreated` | |
| `AchievementUnlocked` | `app/Jobs/AchievementEvaluationJob.php:34` | `NotifyOnAchievementUnlocked`, `LogActivityOnAchievementUnlocked` | |
| `PartnerInvitationAccepted` | `app/Actions/AcceptPartnerInvitationAction.php:24` | none | No listeners |
| `PartnerDisconnected` | `app/Http/Controllers/PartnerController.php` (`disconnect`) | `NotifyFormerPartnerOnPartnerDisconnected` | Tells the other partner (in-app, `partner_left`) |
| `CalendarEventCreated` | `app/Http/Controllers/CalendarEventController.php` (`store`) | `EvaluateAchievementsOnActivity` | |
| `ChallengeCompleted` | `app/Actions/CompleteDailyChallengeAction.php` | `NotifyPartnerOnChallengeCompleted`, `EvaluateAchievementsOnActivity` | |

## Listeners
Queued listeners set the queue with `public string $queue = '...'`. Sync listeners run inline in whatever process dispatched the event.

| Listener | Event | Mode (queue) | What it does |
|---|---|---|---|
| `SendPartnerConnectedEmailsOnPartnerConnected` | `PartnerConnected` | queued (`emails`) | Dispatches `SendPartnerConnectedEmailJob` for each user |
| `CreatePartnerConnectedNotificationOnPartnerConnected` | `PartnerConnected` | queued (`notifications`) | Creates an `AppNotification` for both users |
| `LogActivityOnPartnerConnected` | `PartnerConnected` | sync | Writes two `activity_log` rows |
| `UpdateStreakOnQuestionnairCompleted` | `QuestionnaireCompleted` | queued (`default`) | Runs `UpdateStreakAction` for the user (class name has a typo: `Questionnair`) |
| `NotifyPartnerOnQuestionnaireCompleted` | `QuestionnaireCompleted` | queued (`notifications`) | Skips solo / no partner; creates partner `AppNotification` and dispatches `SendQuestionnaireCompletedNotificationJob` |
| `DispatchDateNightPlanOnQuestionnaireCompleted` | `QuestionnaireCompleted` | queued (`default`) | Solo: dispatches `GenerateDateNightPlanJob(user, null, q)`. Couple: only if a partner exists and `QuestionnaireService::partnerHasFreshCompletedResponse()` |
| `EvaluateAchievementsOnQuestionnaireCompleted` | `QuestionnaireCompleted` | queued (`analytics`) | Dispatches `AchievementEvaluationJob` |
| `LogActivityOnQuestionnaireCompleted` | `QuestionnaireCompleted` | sync | Writes `activity_log` row |
| `SendPlanEmailsOnDateNightPlanGenerated` | `DateNightPlanGenerated` | queued (`emails`) | Dispatches `SendSummaryEmailJob` for `userOne` and `userTwo` (if set) |
| `NotifyPlanReadyOnDateNightPlanGenerated` | `DateNightPlanGenerated` | queued (`notifications`) | Creates "plan ready" `AppNotification` for `userOne` and `userTwo` (if set) |
| `SendLoveNoteNotificationOnLoveNoteSent` | `LoveNoteSent` | queued (`notifications`) | Dispatches `SendLoveNoteNotificationJob` |
| `LogActivityOnLoveNoteSent` | `LoveNoteSent` | sync | Writes `activity_log` row |
| `SendMomentNotificationOnMomentCreated` | `MomentCreated` | queued (`notifications`) | If the creator has a partner, dispatches `SendMomentNotificationJob` |
| `LogActivityOnMomentCreated` | `MomentCreated` | sync | Writes `activity_log` row |
| `EvaluateAchievementsOnMomentCreated` | `MomentCreated` | queued (`analytics`) | Dispatches `AchievementEvaluationJob` |
| `NotifyOnAchievementUnlocked` | `AchievementUnlocked` | queued (`notifications`) | Creates an `AppNotification` for the user |
| `LogActivityOnAchievementUnlocked` | `AchievementUnlocked` | sync | Writes `activity_log` row |
| `NotifyPartnerOnChallengeCompleted` | `ChallengeCompleted` | queued (`notifications`) | Creates a `daily_challenge` `AppNotification` for the partner, if any |
| `EvaluateAchievementsOnActivity` | `LoveNoteSent`, `CalendarEventCreated`, `ChallengeCompleted` | queued (`analytics`) | Dispatches `AchievementEvaluationJob` for the acting user |
| `NotifyFormerPartnerOnPartnerDisconnected` | `PartnerDisconnected` | queued (`notifications`) | Creates a `partner_left` `AppNotification` for the other partner |

## Jobs
All jobs set `$tries = 3`, set their queue with `onQueue()` in the constructor, and log in `failed()`.

| Job | Queue | Timeout / backoff | Dispatched from | What it does |
|---|---|---|---|---|
| `GenerateDateNightPlanJob` | `default` | 120s | `DispatchDateNightPlanOnQuestionnaireCompleted.php:23`, `:38` | Finds latest completed responses, skips if a plan already exists for them, runs `GenerateDateNightPlanAction` (which fires `DateNightPlanGenerated`) |
| `AchievementEvaluationJob` | `analytics` | 60s | `EvaluateAchievementsOnMomentCreated.php:15`, `EvaluateAchievementsOnQuestionnaireCompleted.php:15`, `EvaluateAchievementsOnActivity.php` | `AchievementService::checkAndAward()`, fires `AchievementUnlocked` per new achievement |
| `DailyChallengeSelectionJob` | `default` | 60s | `app/Console/Commands/SelectDailyChallengeCommand.php:16` | Calls `DailyChallenge::forToday()` (random active, non-archived challenge if none exists) |
| `SendInvitationEmailJob` | `emails` | 30s | `app/Actions/SendPartnerInvitationAction.php:20`, `app/Http/Controllers/PartnerController.php:52` | Skips accepted/expired invitations; sends `PartnerInvitationMail` synchronously |
| `SendPartnerConnectedEmailJob` | `emails` | 30s | `SendPartnerConnectedEmailsOnPartnerConnected.php:15-16` | Sends `PartnerConnectedMail` if `email_notifications` |
| `SendSummaryEmailJob` | `emails` | 30s | `SendPlanEmailsOnDateNightPlanGenerated.php:15`, `:18` | Sends `DateNightReadyMail` if `email_notifications` |
| `SendLoveNoteNotificationJob` | `notifications` | 30s | `SendLoveNoteNotificationOnLoveNoteSent.php:15` | Creates `AppNotification`; queues `LoveNoteReceivedMail` only if the recipient has `email_notifications` on |
| `SendMomentNotificationJob` | `notifications` | 30s | `SendMomentNotificationOnMomentCreated.php:21` | Creates `AppNotification`; queues `MomentCreatedMail` only if the recipient has `email_notifications` on |
| `SendQuestionnaireCompletedNotificationJob` | `notifications` | 30s | `NotifyPartnerOnQuestionnaireCompleted.php:40` | Queues `QuestionnaireCompletedMail` only if the recipient has `email_notifications` on |
| `ExportUserDataJob` | `exports` | 300s | `Settings/DataExportController@store` (once an hour per user) | Writes the user's data to `exports/user-{id}.json` on the private `local` disk and creates a `data_export_ready` notification |

### Scheduler (`bootstrap/app.php`)
| Time | Command | Queue work |
|---|---|---|
| 00:01 | `SelectDailyChallengeCommand` | dispatches `DailyChallengeSelectionJob` |
| 00:05 | `EvaluateStreaksCommand` | none |
| 00:10 | `ExpireInvitationsCommand` | none (creates `invitation_expired` notifications directly) |
| 02:00 | `CleanTemporaryExportsCommand` (`datenight:clean-exports`) | none |
| 09:00 | `SendRemindersCommand` (`datenight:send-reminders`) | none (creates notifications inline); skips users who chose `off`, and waits 30 days for `monthly` |
| hourly | `SendCalendarRemindersCommand` (`datenight:send-calendar-reminders`) | none (creates notifications inline) |
| hourly | `NudgePartnersCommand` (`datenight:nudge-partners`) | dispatches `SendQuestionnaireCompletedNotificationJob` per reminder |
| 09:00 | `ConnectionAnniversariesCommand` (`datenight:connection-anniversaries`) | dispatches `AchievementEvaluationJob` per notified user |

### Queue configuration and local running
- Default connection: `database` (`config/queue.php`, `.env.example`). Jobs live in the `jobs` table; failures in `failed_jobs`.
- `composer dev` runs `php artisan dev`, whose framework defaults start `serve`, `queue:listen --tries=1 --timeout=0` on all six queues, `pail` (removed on Windows by `AppServiceProvider`) and Vite. Nothing in `composer dev` runs the scheduler; use `php artisan schedule:work` or run the commands by hand.
- Tests: `QUEUE_CONNECTION=sync` in `phpunit.xml`, so queued listeners and jobs run inline.

## Rules and gotchas
- **Auto-discovery is off** in two places (`EventServiceProvider::shouldDiscoverEvents()` and `withEvents(discover: false)`). A new listener does nothing until it is added to `$listen` by hand.
- **Six queue names are in use**: `default`, `notifications`, `emails`, `analytics`, `ai`, `exports`. `queue:listen` without `--queue` only reads the connection's default queue (`DB_QUEUE`, default `default`). `AppServiceProvider` re-registers the dev `queue` command with `--queue=default,notifications,emails,analytics,ai,exports`, so `composer dev` works every queue. Production workers still need the same list.
- A typical email takes three hops (queued listener -> job -> `ShouldQueue` mailable), and the mailable lands on the default queue, not `emails`.
- Sync `LogActivityOn*` listeners run before the dispatching code continues; an exception there fails the request/job.
- Events use `SerializesModels`; queued listeners re-fetch models, so a model deleted before the worker runs makes the listener fail with `ModelNotFoundException`.
- `QuestionnaireCompletedListenersTest::test_questionnaire_completed_has_correct_listeners_registered` asserts on the listener list; check it when changing `QuestionnaireCompleted` wiring.

## Related modules
- [notifications-activity-mail.md](notifications-activity-mail.md) - what the notification/mail/activity listeners produce
- [date-night.md](date-night.md), [questionnaires.md](questionnaires.md) - the `QuestionnaireCompleted` -> plan pipeline
- [challenges-achievements-streaks.md](challenges-achievements-streaks.md) - streak, achievement and daily challenge jobs
- [ai.md](ai.md) - `AIService` (called inline; no job sits on the `ai` queue today)
- [onboarding-partner.md](onboarding-partner.md) - invitation and connection events
- [platform.md](platform.md) - scheduler, environment, deployment

## Tests
| File | Covers |
|---|---|
| `tests/Feature/Events/PartnerConnectedEventTest.php` | `PartnerConnected` fired with both users |
| `tests/Feature/Events/QuestionnaireCompletedEventTest.php` | `QuestionnaireCompleted` fired |
| `tests/Feature/Listeners/QuestionnaireCompletedListenersTest.php` | Listener registration, plan-job dispatch rules, partner notification |
| `tests/Feature/Jobs/AchievementEvaluationJobTest.php`, `DailyChallengeSelectionJobTest.php`, `GenerateDateNightPlanJobTest.php` | Those three jobs |

Run: `php artisan test tests/Feature/Events tests/Feature/Listeners tests/Feature/Jobs`

## Change log
- 2026-11-24 — Initial doc.
- 2026-11-24 — Dev queue worker now listens to all six queues (re-registered in `AppServiceProvider`).
- 2026-11-24 — Roadmap Phase 2 (2.3, 2.4): hourly `SendCalendarRemindersCommand`; `ChallengeCompleted` is dispatched and has two listeners.
- 2026-11-24 — Roadmap Phase 2 (2.6, 2.12, 2.14): `PartnerDisconnected` has a listener; `ExportUserDataJob` is dispatched from settings; the three notification mail jobs honour `email_notifications`; `ExpireInvitationsCommand` notifies senders.
- 2026-11-26 — Roadmap Phase 4: removed 10 events nothing dispatched or listened to, the unregistered invitation listener, the four cache-forgetting listeners with `RefreshRelationshipStatisticsJob` and `datenight:refresh-statistics`, and the five jobs nothing dispatched. Every event left is dispatched; every job left has a dispatcher.
- 2026-11-25 — Roadmap Phase 5A: scheduled `datenight:nudge-partners` (hourly) and `datenight:connection-anniversaries` (09:00).
- 2026-11-25 — Roadmap Phase 5.10: reminder frequency in `SendRemindersCommand`; the notification mail jobs check a category.
