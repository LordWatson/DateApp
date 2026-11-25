# Challenges, Achievements and Streaks

> Last verified against code: 2026-11-25

## Purpose

Three small gamification features. **Challenges** are a pool of short couple activities; one is picked per day, shown on the dashboard as "Today's challenge", and each partner can mark it done. **Achievements** are badges with points that unlock automatically when a user's counters (questionnaires, streak, love notes, moments, calendar events, compatibility) reach a threshold. **Streaks** count consecutive days on which a user completed a questionnaire.

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Models/Challenge.php`, `app/Models/DailyChallenge.php` | Challenge pool and the "challenge of the day" record; `DailyChallenge::forToday()` finds or lazily picks today's row, `completedUserIds()` lists who has done it |
| `app/Http/Controllers/DailyChallengeController.php`, `app/Actions/CompleteDailyChallengeAction.php` | `daily-challenge.complete`: records the completion once and dispatches `ChallengeCompleted` |
| `app/Listeners/NotifyPartnerOnChallengeCompleted.php` | Queued (`notifications`); tells the partner (`daily_challenge` notification) |
| `app/Listeners/EvaluateAchievementsOnActivity.php` | Re-checks achievements on `LoveNoteSent`, `CalendarEventCreated` and `ChallengeCompleted` |
| `app/Models/Achievement.php` | Achievement definition; `users()` pivot `user_achievements` |
| `app/Enums/ChallengeDifficulty.php` | `easy` / `medium` / `hard` (cast on `Challenge.difficulty`) |
| `app/Enums/AchievementCategory.php` | 8 categories + `label()`; only used by `AchievementFactory` (column is not cast) |
| `app/Services/AchievementService.php` | `checkAndAward()` (evaluation) and `getUserProgress()` |
| `app/Http/Controllers/AchievementController.php` | `achievements.index` page |
| `app/Jobs/AchievementEvaluationJob.php` | Runs `checkAndAward`, fires `AchievementUnlocked` per new unlock (queue `analytics`) |
| `app/Listeners/EvaluateAchievementsOnQuestionnaireCompleted.php`, `app/Listeners/EvaluateAchievementsOnMomentCreated.php` | The only two triggers that dispatch the evaluation job |
| `app/Events/AchievementUnlocked.php` | Listeners: `NotifyOnAchievementUnlocked`, `LogActivityOnAchievementUnlocked` |
| `app/Actions/UpdateStreakAction.php` | Streak increment logic |
| `app/Listeners/UpdateStreakOnQuestionnairCompleted.php` | Calls the action on `QuestionnaireCompleted` (note the typo in the class name) |
| `app/Console/Commands/EvaluateStreaksCommand.php` | `datenight:evaluate-streaks` - resets broken streaks |
| `app/Console/Commands/SelectDailyChallengeCommand.php` | `datenight:select-daily-challenge` - dispatches `DailyChallengeSelectionJob` |
| `app/Jobs/DailyChallengeSelectionJob.php` | Creates today's `daily_challenges` row |
| `app/Events/ChallengeCompleted.php` | Dispatched on a user's first completion of the day |
| `bootstrap/app.php` (`withSchedule`) | Scheduling of both commands (`routes/console.php` has no schedule) |
| `app/Http/Controllers/DashboardController.php` | Sends today's challenge with `completed_by_me` / `completed_by_partner`; exposes streak stats |
| `database/seeders/ChallengeSeeder.php`, `database/seeders/AchievementSeeder.php` | 100 challenges, 18 achievements |
| `database/factories/{Challenge,DailyChallenge,Achievement}Factory.php` | Factories |
| **Frontend** | |
| `resources/js/components/ChallengeCard.vue` | "Today's challenge" card: difficulty, who has done it, Done button |
| `resources/js/pages/Dashboard.vue` | Renders `ChallengeCard` + streak stat cards |
| `resources/js/pages/achievements/Index.vue` | Achievement list, category filter, progress bar |
| **Tests** | |
| `tests/Feature/AchievementTest.php`, `tests/Feature/ChallengeTest.php` | Page, service, models, seeder |
| `tests/Feature/DailyChallengeTest.php` | Dashboard pick, completion, partner state, notification, achievement |
| `tests/Feature/Commands/EvaluateStreaksCommandTest.php`, `tests/Feature/Commands/SelectDailyChallengeCommandTest.php` | Commands |
| `tests/Feature/Jobs/AchievementEvaluationJobTest.php`, `tests/Feature/Jobs/DailyChallengeSelectionJobTest.php` | Jobs |

## Data model

- `challenges`: `title`, `description?`, `emoji?`, `category?`, `season?`, `weight` (int, default 1), `difficulty` (string, default `easy`, enum cast), `active` (bool, default true), `archived` (bool, default false), `display_order`. Has many `daily_challenges`.
- `daily_challenges`: `challenge_id` (FK, cascade delete), `date` (**unique**, cast `date`).
- `daily_challenge_completions` (no model): `daily_challenge_id`, `user_id` (both FK, cascade; **unique pair**, so one completion per user per day), `completed_at`.
- `achievements`: `name`, `description`, `emoji`, `artwork?`, `category` (string), `points` (default 10), `hidden` (bool), `unlock_condition` (string), `unlock_value` (int, default 1).
- `user_achievements` (pivot): `user_id`, `achievement_id` (unique pair, both cascade), `unlocked_at`, timestamps. Relation: `User::achievements()` / `Achievement::users()`.
- Streak columns live on `users` (`database/migrations/2026_07_12_000001_extend_users_table.php`): `last_completed_questionnaire_at` (datetime), `current_streak`, `longest_streak`, `monthly_completion_count` (all unsigned int, default 0).

## Routes

All inside `auth`, `verified`, `onboarding` middleware (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `achievements.index` | GET `/achievements` | `AchievementController@index` | Only user-facing route in this module |
| `dashboard` | GET `/dashboard` | `DashboardController@index` | Supplies `todayChallenge` and `stats` (see dashboard-insights-search.md) |
| `daily-challenge.complete` | POST `/daily-challenge/complete` | `DailyChallengeController@complete` | Marks today's challenge done for the user; redirects back |

There is no "refresh" or "skip" for the daily challenge. Admin CRUD for challenges and achievements: see admin.md.

Scheduled (in `bootstrap/app.php`): `datenight:select-daily-challenge` daily at 00:01, `datenight:evaluate-streaks` daily at 00:05.

## Flows

### How achievement criteria are defined and evaluated

1. Each `achievements` row carries `unlock_condition` (a string key) and `unlock_value` (threshold).
2. `AchievementService::conditionMet()` is a hard-coded `match` on `unlock_condition`. Supported keys:

| `unlock_condition` | Met when |
|---|---|
| `questionnaires_completed` | user's `responses` with `status = completed` >= value |
| `streak_days` | `users.current_streak` >= value |
| `love_notes_sent` | `love_notes` where `sender_id` = user >= value |
| `calendar_events_created` | `$user->calendarEvents()->count()` >= value |
| `moments_created` | `$user->moments()->count()` >= value |
| `challenges_completed` | rows in `daily_challenge_completions` for the user >= value |
| `days_connected` | the user has a partner and `users.connected_at` is at least value days ago |
| `perfect_compatibility` | any `DateNightPlan` linked to the user's responses has `compatibility_score = 100` (value ignored) |
| `average_compatibility` | average `compatibility_score` over those plans >= value |
| `questionnaire_slug` | user completed the questionnaire with slug `anniversary` (slug is hard-coded; value ignored) |
| anything else | never met (`default => false`) |

3. `checkAndAward($user)` loops over **all** achievements, skips ones already in `user_achievements`, attaches newly met ones with `unlocked_at = now()`, and returns the newly unlocked models.

### Unlock flow

1. `QuestionnaireCompleted` (dispatched in `app/Services/QuestionnaireService.php`) or `MomentCreated` fires.
2. Queued listener (`analytics` queue) dispatches `AchievementEvaluationJob($user)` (`analytics` queue, 3 tries, 60 s timeout).
3. Job calls `checkAndAward`, then dispatches `AchievementUnlocked($user, $achievement)` for each new unlock.
4. `NotifyOnAchievementUnlocked` (queued, `notifications`) creates an `AppNotification`; `LogActivityOnAchievementUnlocked` (sync) writes an activity log entry `achievement_unlocked` with `points`.

### Streak computation and reset

1. `QuestionnaireCompleted` -> queued listener `UpdateStreakOnQuestionnairCompleted` (`default` queue) -> `UpdateStreakAction::execute($user)`.
2. Based on `last_completed_questionnaire_at`, compared as dates in the user's timezone (`User::today()`): same day -> streak unchanged; yesterday -> `current_streak + 1`; otherwise (null or older) -> `1`.
3. Always sets `last_completed_questionnaire_at = now()` and `longest_streak = max(longest, new)`. `monthly_completion_count` is incremented unless it was a same-day completion.
4. Reset: `datenight:evaluate-streaks` (daily 00:05 UTC) sets `current_streak = 0` for users with `current_streak > 0` whose last completion is earlier than the start of yesterday in their own timezone. `longest_streak` is never reduced. Because it runs once a day, a broken streak can show for up to a day before it is zeroed; the next completion sets it to 1 either way.

### Daily challenge

1. 00:01 schedule -> `SelectDailyChallengeCommand` -> `DailyChallengeSelectionJob` (`default` queue).
2. The job calls `DailyChallenge::forToday()`: it returns today's row, or picks a random challenge with `active = true` and `archived = false` and creates the row (the job logs a warning if that pool is empty).
3. The dashboard calls the same method, so the row is created on the first page load if the schedule has not run. Everyone sees the same challenge all day.
4. "Done" on the card posts to `daily-challenge.complete`. `CompleteDailyChallengeAction` inserts into `daily_challenge_completions` with `insertOrIgnore`; a repeat does nothing. The first time, `ChallengeCompleted` fires: the partner gets a `daily_challenge` notification and `AchievementEvaluationJob` runs (`First Challenge` at 1, `Challenge Champion` at 10).
5. The card shows your state and the partner's from `completed_by_me` / `completed_by_partner` (null with no partner). Completion cannot be undone.

## Rules and gotchas

- **Visibility**: achievements and streaks are per user, not per couple. `AchievementController` only reads `$request->user()`; there is no policy and no partner view. Challenges are global content.
- Adding a new achievement type requires a new arm in `AchievementService::conditionMet()`; a row with an unknown `unlock_condition` silently never unlocks.
- Achievements are evaluated (`AchievementEvaluationJob`) on questionnaire completion, moment creation, a sent love note, a created calendar event and a completed daily challenge. An event added to the calendar from a plan does not dispatch `CalendarEventCreated`, so it counts at the next evaluation.
- Achievements are never revoked (e.g. deleting moments does not remove the badge).
- Hidden achievements are sent to the page but locked+hidden ones are not rendered (`resources/js/pages/achievements/Index.vue:51`).
- Streak days are days in the user's timezone (`users.timezone` through `User::today()`), in both `UpdateStreakAction` and `datenight:evaluate-streaks`. The daily challenge deliberately stays on the UTC day.
- Streaks require the queue worker: the update is a queued listener. Tests run with `QUEUE_CONNECTION=sync`.
- The daily pick ignores `weight`, `season` and recent history, so a challenge can repeat on consecutive days. The day is the application (UTC) day, not the user's.
- Suspected issue: `app/Listeners/NotifyOnAchievementUnlocked.php:19` reads `$event->achievement->title`, but the column is `name`, so the notification title has no achievement name. Line 18 also uses `NotificationType::PartnerCompleted` (there is no achievement type in the enum).
- Suspected issue: race between queues. The streak update (`default` queue) and achievement evaluation (`analytics` queue) are independent queued listeners of the same event, so `streak_days` achievements may be evaluated before the streak is incremented.
- Suspected issue: `monthly_completion_count` is only ever incremented (`app/Actions/UpdateStreakAction.php:28`); nothing resets it at month end, yet the dashboard labels it with the current month.
- Suspected issue: `getUserProgress()` (`app/Services/AchievementService.php:86-94`) counts `total` over non-hidden achievements but `unlocked` over all, so unlocking a hidden one can push the percentage over 100.
- Suspected issue: `AchievementController@index` runs one extra pivot query per unlocked achievement (`app/Http/Controllers/AchievementController.php:31`).
- Dead code: `EvaluateStreaksCommand::handle()` injects `UpdateStreakAction` but does not use it (line 16).
- `artwork` exists on `achievements` but is not in the model's `#[Fillable]` list (`app/Models/Achievement.php:24`); see admin.md.
- `AchievementSeeder` is idempotent (`firstOrCreate` on name + condition); `ChallengeSeeder` uses `create` and will duplicate rows if run twice.

## Related modules

- questionnaires.md - source of `QuestionnaireCompleted`.
- moments-timeline.md - `MomentCreated` trigger; unlocked achievements appear in the timeline.
- love-notes.md, calendar.md - counters used by unlock conditions.
- dashboard-insights-search.md - dashboard, relationship hub, insights and search read streaks/progress.
- notifications-activity-mail.md, events-jobs.md - notification and queue details.
- admin.md - CRUD for challenges and achievements.

## Tests

- `tests/Feature/AchievementTest.php`, `tests/Feature/ChallengeTest.php`
- `tests/Feature/Commands/EvaluateStreaksCommandTest.php`, `tests/Feature/Commands/SelectDailyChallengeCommandTest.php`
- `tests/Feature/Jobs/AchievementEvaluationJobTest.php`, `tests/Feature/Jobs/DailyChallengeSelectionJobTest.php`

- `tests/Feature/DailyChallengeTest.php`

Run: `php artisan test --filter="AchievementTest|ChallengeTest|DailyChallengeTest|EvaluateStreaksCommandTest|SelectDailyChallengeCommandTest|AchievementEvaluationJobTest|DailyChallengeSelectionJobTest"`

`tests/Feature/UserTimezoneTest.php` covers `UpdateStreakAction` and the reset command for non-UTC users. Not covered: only `streak_days` is exercised among the unlock conditions.

## Change log

- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 1: `EvaluateAchievementsOnActivity` re-checks achievements on `LoveNoteSent` and `CalendarEventCreated`; the achievement notification uses the achievement name and its own type.
- 2026-11-24 — Roadmap Phase 2 (2.4): the dashboard shows today's `DailyChallenge` (lazy, active and non-archived only); completion table, `daily-challenge.complete` route and Done control with partner state; `ChallengeCompleted` notifies the partner and unlocks the new `challenges_completed` achievements.
- 2026-11-25 — Roadmap Phase 3 (3.14): streak days are counted in the user's timezone.
- 2026-11-26 — Roadmap Phase 4: removed the unused `ChallengeResource`.
- 2026-11-25 — Roadmap Phase 5.9: `days_connected` condition and the seeded "One Year Together" (365); also evaluated by the daily anniversary command.
