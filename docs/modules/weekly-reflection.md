# Weekly Reflection
> Last verified against code: 2026-11-24

## Purpose
A short, warm recap of the user's current week (date nights, love notes, moments, calendar events, questionnaires). It is generated the first time the user opens the page in a week, stored, and can be regenerated on demand. The text comes from the AI when available and from fixed sentences otherwise.

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
| **Tests** | None |

## Data model
**`weekly_reflections`**: `user_id` (cascade delete), `partner_id` (nullable, null on delete), `week_start` and `week_end` (dates), `headline`, `summary`, `highlights` (json array), `gentle_suggestion`, `encouragement`, `metrics` (json), `fallback_used`, `generated_at`. Unique on (`user_id`, `week_start`). Relations: `user`, `partner`.

Reflections are per user, not per couple: each partner gets their own row.

## Routes
Under `auth`, `verified`, `onboarding` middleware.

| Name | Method + URI | Controller@method | Notes |
|------|--------------|-------------------|-------|
| `weekly-reflection.index` | GET `/weekly-reflection` | `WeeklyReflectionController@index` | Generates on first visit of the week |
| `weekly-reflection.regenerate` | POST `/weekly-reflection/regenerate` | `WeeklyReflectionController@regenerate` | Deletes and recreates this week's row, redirects to index |

## Flows

### View
1. `index` calls the action without `force`. The service returns the row for this user and the current week start (`CarbonImmutable::now()->startOfWeek()`), or generates one.
2. Generation: `collectMetrics()` counts this week's date night plans, love notes sent and received, moments, calendar events and completed questionnaires (moments, events and questionnaires include the partner's).
3. `generateContent()` builds the deterministic text, then calls `AIService::generate(AIUseCase::WeeklyReflection, ['metrics' => $metrics], userId: ...)`.
4. On success, non-empty AI strings replace `headline`, `summary`, `gentle_suggestion`, `encouragement`; `highlights` is replaced by up to 5 non-empty strings. On exception or failure the deterministic text is kept and `fallback_used` is true.
5. The row is saved with the metrics snapshot. The page also receives up to 8 past reflections, minus the current one.

### Regenerate
POST deletes this week's row and runs generation again. The Vue page disables the button while the request runs.

## Rules and gotchas
- No events, jobs or mail. The AI call runs synchronously inside the web request, so a slow provider delays the page (see worst-case duration in `ai.md`).
- A GET request has side effects (creates a row and may call the AI).
- No authorisation beyond the route middleware is needed: everything is scoped to `$request->user()`.
- No rate limit on regenerate; each call is one AI request.
- Metrics are frozen at generation time; they only change on regenerate.
- Suspected issue: the prompt template uses `{{context}}` but the service passes the key `metrics` (`WeeklyReflectionService.php` line 194), so the metrics never reach the model. See `ai.md`.
- Suspected issue: date night plans are matched only through the user's own responses (lines 118-124), so a solo plan filled by the partner (linked by `partner_user_id`) is not counted.
- Suspected issue: two concurrent first visits in a week can both try to insert and hit the unique index.
- Suspected issue: the migration adds a plain index on (`user_id`, `week_start`) that duplicates the unique index (lines 29-30).
- With `AI_PROVIDER=null` the null provider throws for this use case and the fallback text is always used.

## Related modules
- `ai.md`: `AIService`, prompt templates, failure handling.
- `dashboard-insights-search.md`: Insights page that links here.
- `date-night.md`, `love-notes.md`, `moments-timeline.md`, `calendar.md`, `questionnaires.md`: data sources for the metrics.

## Tests
None exist for this module. `php artisan test --filter=WeeklyReflection` currently matches nothing.

## Change log
- 2026-11-24 — Initial doc.
