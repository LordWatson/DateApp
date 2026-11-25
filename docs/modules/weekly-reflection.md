# Weekly Reflection
> Last verified against code: 2026-11-25

## Purpose
Insights for a couple drawn from what they actually did in the past seven days (date nights, love notes, moments, calendar, questionnaires, achievements, streaks). One reflection per calendar week is shared by both partners, so they always read the same text. It is generated the first time either of them opens the page that week, stored, and can be regenerated on demand. The text comes from the AI when available and from a plain recap otherwise.

## Key files

| Path | Role |
|------|------|
| **Backend** | |
| `app/Http/Controllers/WeeklyReflectionController.php` | `index` (get or generate, plus history), `regenerate`; formats the payload in `present()` |
| `app/Actions/GenerateWeeklyReflectionAction.php` | `execute($user, $force)`: thin wrapper over the service |
| `app/Services/WeeklyReflectionService.php` | Metrics collection, AI call, deterministic fallback, persistence, history |
| `app/Models/WeeklyReflection.php` | Model (uses the `#[Fillable]` attribute) |
| `database/migrations/2026_07_26_141900_create_weekly_reflections_table.php` | Table |
| `database/seeders/AiPromptTemplateSeeder.php` | `weekly_reflection` prompt template |
| **Frontend** | |
| `resources/js/pages/weekly-reflection/Index.vue` | Current reflection, regenerate button, history list |
| `resources/js/pages/insights/Index.vue` | Entry point: links here and shows a loading state while the page generates |
| **Tests** | `tests/Feature/WeeklyReflectionTest.php` |

## Data model
**`weekly_reflections`**: `user_id` (cascade delete), `partner_id` (nullable, null on delete), `week_start` and `week_end` (dates), `headline`, `summary`, `highlights` (json array), `gentle_suggestion`, `encouragement`, `metrics` (json), `fallback_used`, `generated_at`. Unique on (`user_id`, `week_start`). Relations: `user`, `partner`.

One row serves the couple. It belongs to whoever triggered generation (`user_id`) and records their partner (`partner_id`); the other partner reads the same row through `WeeklyReflectionService::sharedQuery()` (own rows, plus rows the current partner generated while paired with them). `week_start` is the calendar-week key; `week_end` is the day it was generated. `metrics` holds `period_start`, `period_end` and counts only.

## Routes
Under `auth`, `verified`, `onboarding` middleware.

| Name | Method + URI | Controller@method | Notes |
|------|--------------|-------------------|-------|
| `weekly-reflection.index` | GET `/weekly-reflection` | `WeeklyReflectionController@index` | Generates on first visit of the week |
| `weekly-reflection.regenerate` | POST `/weekly-reflection/regenerate` | `WeeklyReflectionController@regenerate` | `throttle:ai` (20 an hour per user); deletes and recreates this week's row, redirects to index |

## Flows

### View
1. `index` calls the action without `force`. The service returns the couple's row for the current calendar week (`whereDate('week_start', startOfWeek)`), or generates one.
2. Generation looks back seven days including today (`PERIOD_DAYS`), not the calendar week, so a Monday visit still has a full week behind it.
3. `collectActivity()` builds one log for the couple, identical whichever partner asks (people sorted by id, first names only): date nights (theme, activity, meal, place, planned together or by whom, compatibility), love notes (total and up to 8 messages, each cut to 140 characters), moments (title, mood, description cut to 200 characters, favourite), calendar events this week and the next seven days, completed questionnaires, achievements unlocked, current streaks, and totals. At most 10 items per list.
4. `generateContent()` builds the plain recap, then calls `AIService::generate(AIUseCase::WeeklyReflection, ['context' => $activity], userId: ...)`.
5. On success, non-empty AI strings replace `headline`, `summary`, `gentle_suggestion`, `encouragement`; `highlights` is replaced by up to 5 non-empty strings. On exception or failure the recap is kept and `fallback_used` is true.
6. The row is saved with the period and totals. The page also receives up to 8 past reflections (one per week), minus the current one. `present()` shows the seven-day period as `week_start` / `week_end`.

### Regenerate
POST deletes the couple's row for this week and runs generation again, so the new text replaces it for both partners. The Vue page disables the button while the request runs, and shows a toast instead of an error page when the limit answers 429.

## Rules and gotchas
- **AI needs consent from both partners** (the recap contains both people's notes and moments); a user without a partner needs only their own. Otherwise the deterministic recap is stored with `fallback_used = true`, and the page shows a one-line note linking to Settings whenever `fallback_used` is set. See `ai.md`.
- **The context key must be `context`.** The prompt template interpolates `{{context}}`; any other key is silently dropped and the model writes generic text. This was the original bug.
- **Prompt:** `weekly_reflection` version 2 in `AiPromptTemplateSeeder` (specific, shared, no per-person tallies, no verbatim notes). Environments must reseed to get it; an older active version still receives the full log but with the older instructions.
- **Privacy:** love note text and moment descriptions are sent to the AI provider. Moment `private_notes`, calendar `notes` and surnames are never sent, and the log itself is not stored (only counts are).
- Date filters use `whereDate`, not `whereBetween`: SQLite stores dates with a midnight time and `whereBetween` on strings drops the last day.
- No events, jobs or mail. The AI call runs synchronously inside the web request (about 5 seconds with Claude), so a slow provider delays the page (see worst-case duration in `ai.md`).
- A GET request has side effects (creates a row and may call the AI).
- No authorisation beyond the route middleware is needed: everything is scoped to `$request->user()` and their current partner.
- Regenerate is limited by `throttle:ai`, the limiter shared with the other AI-costing requests. Either partner can replace the text the other is reading.
- Rows generated before a couple connected stay private to their owner. If a couple disconnects, each keeps only the rows they generated themselves.
- **One row per couple and week** is kept by a cache lock, not by the database (the unique index is per `user_id`). `WeeklyReflectionService::locked()` holds `weekly-reflection:{ids}:{week}` (`lockKey()`, the same for both partners) around "look again, then generate" and around regenerate. A partner arriving mid-generation waits up to 30 seconds and then reads the row the other one made. The cache store must support locks (array, file, database and redis all do).
- **Solo wording:** when the user has no partner (`partner_connected` false in the activity log) the plain recap uses "Your Week" and wording without "together" or "each other", and the page title is "Your week". The AI prompt already receives `partner_connected`.
- Suspected issue: the migration adds a plain index on (`user_id`, `week_start`) that duplicates the unique index (lines 29-30).
- With `AI_PROVIDER=null` the null provider throws for this use case and the recap is always used.

## Related modules
- `ai.md`: `AIService`, prompt templates, failure handling.
- `dashboard-insights-search.md`: Insights page that links here.
- `date-night.md`, `love-notes.md`, `moments-timeline.md`, `calendar.md`, `questionnaires.md`: data sources for the metrics.

## Tests
`php artisan test --filter=WeeklyReflectionTest`: both partners' activity reaches the prompt (and old or private data does not), both partners see the same reflection from one AI call, regenerating replaces it for both, the recap names real activity when the AI is unavailable, a user without a partner gets solo wording, regenerate is throttled, and generation holds the per-couple lock.

## Change log
- 2026-11-24 — Initial doc.
- 2026-11-24 — Reflections are now built from the couple's real activity over the past seven days and shared by both partners; fixed the context key that kept the data from the model; prompt version 2; first tests.
- 2026-11-24 — Roadmap Phase 2 (2.16): regenerate is throttled (`throttle:ai`) and the page handles the 429; solo wording for users without a partner; a cache lock keeps a couple to one row per week.
- 2026-11-25 — Roadmap Phase 3 (3.6): the AI recap needs AI consent from the user and their partner; fallback recaps show a note linking to Settings.
