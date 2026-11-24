# Challenges, Achievements and Streaks

> Last verified against code: 2026-11-24

## Purpose

Three small gamification features. **Challenges** are a pool of short couple activities; one is shown on the dashboard as "Today's Challenge". **Achievements** are badges with points that unlock automatically when a user's counters (questionnaires, streak, love notes, moments, calendar events, compatibility) reach a threshold. **Streaks** count consecutive days on which a user completed a questionnaire.

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Models/Challenge.php`, `app/Models/DailyChallenge.php` | Challenge pool and the "challenge of the day" record |
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
| `app/Events/ChallengeCompleted.php`, `app/Http/Resources/ChallengeResource.php` | Defined but unused (see gotchas) |
| `bootstrap/app.php` (`withSchedule`) | Scheduling of both commands (`routes/console.php` has no schedule) |
| `app/Http/Controllers/DashboardController.php` | Picks the challenge shown on the dashboard; exposes streak stats |
| `database/seeders/ChallengeSeeder.php`, `database/seeders/AchievementSeeder.php` | 100 challenges, 15 achievements |
| `database/factories/{Challenge,DailyChallenge,Achievement}Factory.php` | Factories |
| **Frontend** | |
| `resources/js/components/ChallengeCard.vue` | "Today's Challenge" card |
| `resources/js/pages/Dashboard.vue` | Renders `ChallengeCard` + streak stat cards |
| `resources/js/pages/achievements/Index.vue` | Achievement list, category filter, progress bar |
| **Tests** | |
| `tests/Feature/AchievementTest.php`, `tests/Feature/ChallengeTest.php` | Page, service, models, seeder |
| `tests/Feature/Commands/EvaluateStreaksCommandTest.php`, `tests/Feature/Commands/SelectDailyChallengeCommandTest.php` | Commands |
| `tests/Feature/Jobs/AchievementEvaluationJobTest.php`, `tests/Feature/Jobs/DailyChallengeSelectionJobTest.php` | Jobs |

## Data model

- `challenges`: `title`, `description?`, `emoji?`, `category?`, `season?`, `weight` (int, default 1), `difficulty` (string, default `easy`, enum cast), `active` (bool, default true), `archived` (bool, default false), `display_order`. Has many `daily_challenges`.
- `daily_challenges`: `challenge_id` (FK, cascade delete), `date` (**unique**, cast `date`).
- `achievements`: `name`, `description`, `emoji`, `artwork?`, `category` (string), `points` (default 10), `hidden` (bool), `unlock_condition` (string), `unlock_value` (int, default 1).
- `user_achievements` (pivot): `user_id`, `achievement_id` (unique pair, both cascade), `unlocked_at`, timestamps. Relation: `User::achievements()` / `Achievement::users()`.
- Streak columns live on `users` (`database/migrations/2026_07_12_000001_extend_users_table.php`): `last_completed_questionnaire_at` (datetime), `current_streak`, `longest_streak`, `monthly_completion_count` (all unsigned int, default 0).

## Routes

All inside `auth`, `verified`, `onboarding` middleware (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `achievements.index` | GET `/achievements` | `AchievementController@index` | Only user-facing route in this module |
| `dashboard` | GET `/dashboard` | `DashboardController@index` | Supplies `todayChallenge` and `stats` (see dashboard-insights-search.md) |

There are no user routes for challenges (no "complete", no "refresh"). Admin CRUD for challenges and achievements: see admin.md.

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
2. Based on `last_completed_questionnaire_at`: same day -> streak unchanged; yesterday -> `current_streak + 1`; otherwise (null or older) -> `1`.
3. Always sets `last_completed_questionnaire_at = now()` and `longest_streak = max(longest, new)`. `monthly_completion_count` is incremented unless it was a same-day completion.
4. Reset: `datenight:evaluate-streaks` (daily 00:05) sets `current_streak = 0` for users with `current_streak > 0` and `last_completed_questionnaire_at` earlier than the start of yesterday. `longest_streak` is never reduced.

### Daily challenge

1. 00:01 schedule -> `SelectDailyChallengeCommand` -> `DailyChallengeSelectionJob` (`default` queue).
2. If a `daily_challenges` row exists for today it returns; otherwise picks `Challenge::inRandomOrder()->first()` and creates the row (logs a warning if the pool is empty).
3. Dashboard does **not** read that row (see gotchas).

## Rules and gotchas

- **Visibility**: achievements and streaks are per user, not per couple. `AchievementController` only reads `$request->user()`; there is no policy and no partner view. Challenges are global content.
- Adding a new achievement type requires a new arm in `AchievementService::conditionMet()`; a row with an unknown `unlock_condition` silently never unlocks.
- Achievements are only evaluated on questionnaire completion and moment creation. Love-note and calendar achievements therefore unlock late (on the next questionnaire/moment), because `LoveNoteSent` has no evaluation listener and `CalendarEventCreated` is never dispatched.
- Achievements are never revoked (e.g. deleting moments does not remove the badge).
- Hidden achievements are sent to the page but locked+hidden ones are not rendered (`resources/js/pages/achievements/Index.vue:51`).
- Streak days use the application clock (`Carbon::now()`, `isToday()`, `isYesterday()`), not `users.timezone`.
- Streaks require the queue worker: the update is a queued listener. Tests run with `QUEUE_CONNECTION=sync`.
- Suspected issue: the dashboard ignores `daily_challenges`. `app/Http/Controllers/DashboardController.php:62` uses `Challenge::inRandomOrder()->first()`, so the "daily" challenge changes on every page load and ignores `active`/`archived`. The `daily_challenges` table is written by the job but read nowhere.
- Suspected issue: `DailyChallengeSelectionJob` (`app/Jobs/DailyChallengeSelectionJob.php:35`) also ignores `active`, `archived` and `weight`.
- Suspected issue: the refresh button in `ChallengeCard.vue` emits `refresh` (line 23) but `Dashboard.vue:193` does not listen, so it does nothing. The `category` chip actually shows the difficulty.
- Suspected issue: `app/Listeners/NotifyOnAchievementUnlocked.php:19` reads `$event->achievement->title`, but the column is `name`, so the notification title has no achievement name. Line 18 also uses `NotificationType::PartnerCompleted` (there is no achievement type in the enum).
- Suspected issue: race between queues. The streak update (`default` queue) and achievement evaluation (`analytics` queue) are independent queued listeners of the same event, so `streak_days` achievements may be evaluated before the streak is incremented.
- Suspected issue: `monthly_completion_count` is only ever incremented (`app/Actions/UpdateStreakAction.php:28`); nothing resets it at month end, yet the dashboard labels it with the current month.
- Suspected issue: `getUserProgress()` (`app/Services/AchievementService.php:86-94`) counts `total` over non-hidden achievements but `unlocked` over all, so unlocking a hidden one can push the percentage over 100.
- Suspected issue: `AchievementController@index` runs one extra pivot query per unlocked achievement (`app/Http/Controllers/AchievementController.php:31`).
- Dead code: `App\Events\ChallengeCompleted` is never dispatched and not in `EventServiceProvider`; `ChallengeResource` is never used; `NotificationType::DailyChallenge` is never used; `EvaluateStreaksCommand::handle()` injects `UpdateStreakAction` but does not use it (line 16).
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

Run: `php artisan test --filter="AchievementTest|ChallengeTest|EvaluateStreaksCommandTest|SelectDailyChallengeCommandTest|AchievementEvaluationJobTest|DailyChallengeSelectionJobTest"`

Not covered: `UpdateStreakAction` has no direct test; only `streak_days` is exercised among the unlock conditions.

## Change log

- 2026-11-24 — Initial doc.
