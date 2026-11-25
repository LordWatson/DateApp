# Dashboard, Relationship Hub, Insights and Search
> Last verified against code: 2026-11-25

## Purpose
The read-only "overview" screens. The Dashboard is the post-login home (partner status, streak stats, a challenge, recent date nights, saved profiles). The Relationship Hub is a card menu into the other modules with a few live numbers. Insights shows lifetime statistics computed from the user's data. Search is a site-wide `LIKE` search over the user's own content.

## Key files
| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/DashboardController.php` | `index` - builds all dashboard props with live queries |
| `app/Http/Controllers/RelationshipHubController.php` | `index` - hub props (events, moments, achievements, avg compatibility, seasonal questionnaires) |
| `app/Http/Controllers/InsightsController.php` | `index` - renders `InsightsService::getInsights()` |
| `app/Services/InsightsService.php` | All insight aggregates; SQLite/MySQL date helpers |
| `app/Http/Controllers/SearchController.php` | `index` - trims `q`, delegates to the service |
| `app/Services/GlobalSearchService.php` | Per-type search; `PER_TYPE_LIMIT = 5`, `MIN_QUERY_LENGTH = 2` |
| `app/Jobs/RefreshRelationshipStatisticsJob.php` | Forgets `dashboard:*` / `compatibility:*` cache keys (no-op today) |
| `app/Console/Commands/RefreshStatisticsCommand.php` | `datenight:refresh-statistics`, scheduled 03:00, dispatches the job above per partnered user |
| `app/Jobs/GenerateRelationshipInsightJob.php`, `app/Jobs/GenerateConversationPromptJob.php` | AI jobs that cache a result under a caller-supplied key; never dispatched by app code |
| **Frontend** | |
| `resources/js/pages/Dashboard.vue` | Home page (`MobileLayout`) |
| `resources/js/pages/relationship-hub/Index.vue` | Card grid with hard-coded hrefs |
| `resources/js/pages/insights/Index.vue` | Stat grid, compatibility-over-time, highlights, entry to Weekly Reflection |
| `resources/js/pages/search/Index.vue` | Debounced search box + grouped results |
| `resources/js/components/StatCard.vue` | Used by Dashboard (props `label`, `value`, `emoji`, `description`, `variant`) |
| `resources/js/components/CompatibilityCard.vue` | Imported by Dashboard but its usage is commented out |
| `resources/js/components/ProfileCard.vue` | Not imported anywhere |
| **Tests** | |
| `tests/Feature/DashboardTest.php`, `tests/Feature/InsightsTest.php`, `tests/Feature/SearchTest.php` | See Tests |

## Data model
No tables of its own. Reads `users` (streak columns, `partner_id`), `responses`, `date_night_plans`, `challenges`, `saved_profiles`, `calendar_events`, `moments`, `love_notes`, `questionnaires`, `achievements`, `partner_invitations`.

"Plans belonging to a user" is expressed two different ways:
- Dashboard: plan where `partner_one_response_id` or `partner_two_response_id` is one of the user's responses, **or** `partner_user_id` = user (same rule as `DateNightPlanPolicy::view`).
- Hub, Insights, Search: only the two response relations (`whereHas('partnerOneResponse' / 'partnerTwoResponse')`). Plans visible to the user only through `partner_user_id` (a partner's solo plan) are not counted.

## Routes
All under middleware `auth`, `verified`, `onboarding` (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `dashboard` | GET `/dashboard` | `DashboardController@index` | |
| `relationship-hub.index` | GET `/relationship-hub` | `RelationshipHubController@index` | |
| `insights.index` | GET `/insights` | `InsightsController@index` | |
| `search.index` | GET `/search?q=` | `SearchController@index` | Full Inertia page render per query |

## Flows
**Dashboard load** - props built by `DashboardController@index`:
1. `partner`: the full partner `User` model (or null). `pendingInvitation`: `{email, expires_at}` from `User::pendingInvitation()`.
2. `stats`: `current_streak`, `longest_streak`, `monthly_completion_count` straight from `users` columns.
3. `todayChallenge`: `Challenge::inRandomOrder()->first()`.
4. `recentQuestionnaires` (only with a partner): user's 5 latest completed responses for questionnaires the partner has also completed. The template section that renders them is commented out (`Dashboard.vue` lines 296-337).
5. `recentDateNights`: 3 newest plans (rule above). `savedProfiles`: id, name, emoji, colour.

**Relationship Hub load**: next 3 calendar events (user + partner), 3 most recent own moments, moment count (user + partner), `AchievementService::getUserProgress()`, rounded average `compatibility_score`, up to 3 seasonal questionnaires.

**Insights load**: `InsightsService::getInsights()` returns `total_questionnaires`, `total_plans`, `average_compatibility`, `love_notes_sent`, `love_notes_received`, `current_streak`, `longest_streak`, `most_completed_month`, `favourite_theme`, `most_selected_atmosphere`, `compatibility_over_time` (up to 12 `{month: 'YYYY-MM', score}` rows), `moments_count`, `calendar_events_count`. Everything is computed per request. The page also links to Weekly Reflection ([weekly-reflection.md](weekly-reflection.md)).

**Search**:
1. `search/Index.vue` watches the input, debounces 400 ms, then `router.get(..., {q}, {preserveState: true, replace: true})`.
2. Service returns `[]` for queries shorter than 2 characters; otherwise a map with only the non-empty groups among `plans`, `moments`, `love_notes`, `questionnaires`, `saved_profiles`, `calendar_events`, `achievements`, max 5 each.
3. Scoping: plans/moments/saved profiles/calendar events are the user's own; love notes where the user is sender or recipient; questionnaires and non-hidden achievements are global.
4. Result URLs: plans -> `date-night.show`, questionnaires -> `questionnaires.show`; every other type links to its index page, not the item.

## Rules and gotchas
- The dashboard shows `components/AiConsentCard.vue` under the hero while `auth.user.ai_consent` is null (the user has not answered the AI question yet). See `auth-settings.md`.
- **Time zones**: the dashboard's `stats.monthly_completions` counts from the start of the month in the user's timezone, and the hub's `upcoming_events` start at the user's own today (`User::today()`). A `today()` value compared with a timestamp column must be converted with `->utc()` first, because query bindings are not converted.
- **There is no caching.** Nothing in these controllers/services calls `Cache::remember` or `Cache::put`. The keys `dashboard:{userId}` and `compatibility:{userId}` are only ever *forgotten* (`InvalidateCacheOnPartnerConnected`, `InvalidateCacheOnQuestionnaireCompleted`, `RefreshRelationshipStatisticsJob`), so those listeners, the job and the nightly `datenight:refresh-statistics` command are no-ops. If you add caching, those are the intended keys and invalidation points; note they are not triggered by plan generation, love notes, moments or disconnect.
- Insights is not AI-backed: `GenerateRelationshipInsightJob` and `GenerateConversationPromptJob` are never dispatched and nothing reads their cache keys.
- Feature flag `insights` is seeded (`FeatureFlagSeeder`) but `FeatureFlag::isEnabled()` is not called by any non-admin code, so it does not gate anything.
- `InsightsService` branches on `DB::getDriverName()`: `strftime` for SQLite, `MONTH()` / `DATE_FORMAT()` otherwise (MySQL syntax; other drivers are not handled).
- Search uses raw `LIKE "%{$query}%"` with bindings; `%` and `_` typed by the user act as wildcards.
- Tests only assert the page renders and a few keys exist; aggregates are untested.
- Suspected issue (XSS): `resources/js/pages/search/Index.vue:110` and `:115` render `highlight(item.title/description, ...)` with `v-html`. `highlight()` does not HTML-escape the text, and the text is user content (moment titles, calendar events, love note messages written by the partner).
- Suspected issue: `resources/js/pages/search/Index.vue:38` calls `route('search.index')` but the file has no `route` import and no global Ziggy setup was found (no `@routes`, no `tightenco/ziggy` package), so typing in the box is expected to throw. Other pages use Wayfinder imports from `@/actions` / `@/routes`.
- Suspected issue: `resources/js/pages/search/Index.vue:52` `hasResults` is a plain constant computed once at setup; with `preserveState: true` it does not update when new results arrive.
- Suspected issue: `app/Http/Controllers/RelationshipHubController.php:66` and `app/Services/GlobalSearchService.php:124` filter questionnaires by `status = 'published'`, but `QuestionnaireStatus` only has `draft`, `active`, `archived`. Seasonal questionnaires on the hub and questionnaire search results are always empty.
- Suspected issue: `app/Http/Controllers/DashboardController.php:62` picks a random challenge on every page load instead of today's `DailyChallenge` row (see [challenges-achievements-streaks.md](challenges-achievements-streaks.md)).
- Suspected issue: `app/Http/Controllers/DashboardController.php:67` sends the whole partner `User` model to the browser (everything except the `#[Hidden]` fields, so including email). The hub sends only `id`, `name`, `avatar`.
- Suspected issue: `InsightsService` `most_completed_month` groups by `created_at` month number across all years, not `completed_at`.
- Suspected issue: hub `recent_moments` are only the user's own while `moments_count` includes the partner's.
- Dead code: `RelationshipHubController` injects `InsightsService` (line 19) but never uses it; `CompatibilityCard` import in `Dashboard.vue`; `ProfileCard.vue`; the Dashboard "Add new" saved-profile button (line 355) and saved-profile rows have no click handler.
- Hub and Dashboard templates use hard-coded URL strings (`'/insights'`, `'/love-notes'`, ...) rather than route helpers; renaming a route will not break the build.

## Related modules
- [events-jobs.md](events-jobs.md) - the cache-invalidation listeners and jobs
- [date-night.md](date-night.md), [questionnaires.md](questionnaires.md), [saved-profiles.md](saved-profiles.md) - data shown on the dashboard
- [challenges-achievements-streaks.md](challenges-achievements-streaks.md) - streak columns, challenges, achievement progress
- [weekly-reflection.md](weekly-reflection.md), [ai.md](ai.md)
- [moments-timeline.md](moments-timeline.md), [calendar.md](calendar.md), [love-notes.md](love-notes.md) - searched content
- [frontend.md](frontend.md) - `MobileLayout`, shared components

## Tests
- `tests/Feature/DashboardTest.php` - guest redirect, onboarding redirect, partner prop: `php artisan test --filter=DashboardTest`
- `tests/Feature/InsightsTest.php` - insights keys and hub page load: `php artisan test --filter=InsightsTest`
- `tests/Feature/SearchTest.php` - short query returns `[]`, finds moments and calendar events: `php artisan test --filter=SearchTest`

## Change log
- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 0: search highlighting escapes HTML before adding `<mark>`; the dashboard sends only `id`, `name`, `display_name`, `avatar` for the partner.
- 2026-11-24 — Roadmap Phase 1: search results update while typing and link to the item; search and hub use `QuestionnaireStatus::Active`; the monthly stat counts this month; achievement progress ignores hidden achievements.
- 2026-11-24 — Roadmap Phase 2 (2.1): dashboard saved profile rows and a "Manage" link open `/saved-profiles`, which now exists (also the target of saved profile search results).
- 2026-11-25 — Roadmap Phase 3 (3.14): "this month" on the dashboard and upcoming events on the hub follow the user's timezone.
- 2026-11-25 — Roadmap Phase 3 (3.6): AI consent card on the dashboard until the user answers.
