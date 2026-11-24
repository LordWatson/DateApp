# Date Night Plans
> Last verified against code: 2026-11-24

## Purpose
When both partners finish the same questionnaire (or one user finishes a solo questionnaire), the app generates a "date night plan": a theme plus meal, atmosphere, activity, conversation prompt and romantic challenge. A deterministic rule engine always produces a plan; the AI layer then optionally rewrites the wording. Users can view, favourite, like, export (print view) and add a plan to their calendar.

## Key files

| Path | Role |
|------|------|
| **Backend** | |
| `app/Listeners/DispatchDateNightPlanOnQuestionnaireCompleted.php` | Queued listener (`default` queue). Decides whether to dispatch the generation job |
| `app/Jobs/GenerateDateNightPlanJob.php` | Queued job (`default`, tries 3, timeout 120s). Duplicate guard, then calls the action |
| `app/Actions/GenerateDateNightPlanAction.php` | Loads latest completed responses, gets compatibility, calls the generator, fires `DateNightPlanGenerated` |
| `app/Services/DateNightGeneratorService.php` | Builds the deterministic plan, calls AI, validates, persists the `DateNightPlan` |
| `app/Services/RuleEngineService.php` | Keyword-based context flags and theme scoring |
| `app/Events/DateNightPlanGenerated.php` | Event: `userOne`, nullable `userTwo`, `plan` |
| `app/Listeners/NotifyPlanReadyOnDateNightPlanGenerated.php` | Queued (`notifications`). Creates `AppNotification` rows |
| `app/Listeners/SendPlanEmailsOnDateNightPlanGenerated.php` | Queued (`emails`). Dispatches `SendSummaryEmailJob` per user |
| `app/Jobs/SendSummaryEmailJob.php`, `app/Mail/DateNightReadyMail.php`, `resources/views/emails/date-night-ready.blade.php` | Plan-ready email |
| `app/Http/Controllers/DateNightPlanController.php` | show, history, favourites, toggleFavourite, toggleLike, addToCalendar |
| `app/Http/Controllers/DateNightPlanPdfController.php`, `resources/views/pdf/date-night-plan.blade.php` | "Export": printable HTML page |
| `app/Actions/LikeDateNightPlanAction.php` | Toggle like, notify partner |
| `app/Actions/AddDateNightPlanToCalendarAction.php` | Create `CalendarEvent` (type `date_night`), notify partner |
| `app/Http/Requests/DateNightPlan/AddToCalendarRequest.php` | Validation for add-to-calendar |
| `app/Policies/DateNightPlanPolicy.php` | Participant check used by every ability |
| `app/Http/Presenters/DateNightPlanPresenter.php` | `detail()` and `summary()` arrays for Inertia |
| `app/Models/DateNightPlan.php`, `app/Models/DateNightTheme.php` | Models |
| `app/Http/Controllers/QuestionnaireController.php` (`complete`, `planStatus`) | Completion page props and the polling endpoint |
| `database/seeders/DateNightThemeSeeder.php` | 12 themes, `firstOrCreate` by name |
| `database/factories/DateNightPlanFactory.php`, `DateNightThemeFactory.php` | Factories |
| `app/Jobs/EnhanceDateNightPlanWithAIJob.php` | Not dispatched anywhere (see gotchas) |
| `app/Http/Resources/PlanResource.php` | Not used anywhere (see gotchas) |
| `config/datenight.php` | Not read anywhere (see gotchas) |
| **Frontend** | |
| `resources/js/pages/date-night/Show.vue` | Plan page: favourite, like, export, add-to-calendar modal |
| `resources/js/pages/date-night/History.vue` | List with search and compatibility filter |
| `resources/js/pages/date-night/Favourites.vue` | Favourited plans |
| `resources/js/pages/questionnaires/Complete.vue` | "Generating" screen, polls plan status |
| **Tests** | |
| `tests/Feature/DateNightGenerationTest.php` | Pipeline, dedupe, rule engine, notifications, mail |
| `tests/Feature/DateNightPlanTest.php` | View/authorisation, history, favourites, export, likes, calendar |
| `tests/Feature/Jobs/GenerateDateNightPlanJobTest.php` | Job queueing and no-op case |

## Data model

**`date_night_plans`**
- `questionnaire_id`, `partner_one_response_id`, `partner_two_response_id` (FKs, cascade delete). Solo plans store the same response id in both columns.
- `date_night_theme_id` (nullable, null on delete), `partner_user_id` (nullable FK to users, null on delete; set only on solo plans to the filler's partner).
- `compatibility_score` (unsigned tinyint), `is_solo`, `is_favourite`, `ai_enhanced`, `fallback_used` (booleans).
- Text content: `theme`, `theme_emoji`, `summary`, `meal_suggestion`, `atmosphere`, `activity`, `conversation_prompt`, `romantic_challenge` (the suggestion columns were widened from string to text).
- `local_suggestions` (json, cast to array; solo only), `location_label` (solo only).
- Index `dnp_responses_index` on the two response ids. It is no longer unique (dropped so solo plans can reuse one response).
- `drink_suggestion` and `music_vibe` were removed by `2026_08_02_213000_remove_drink_and_music_from_date_night_plans`.
- Relations: `questionnaire`, `partnerOneResponse`, `partnerTwoResponse`, `dateNightTheme`, `partnerUser`, `likedBy` (belongsToMany). Scope `favourites()`.

**`date_night_plan_likes`**: `date_night_plan_id`, `user_id`, timestamps, unique on the pair.

**`date_night_themes`**: `name`, `emoji`, `description`, `colour` (7 chars), `active`. Scope `active()`.

Migrations that touch `date_night_plans` but are not named `date_night_*`: `2026_07_25_231000_create_ai_generations_table` (adds `ai_enhanced`, `fallback_used`) and `2026_07_26_150000_add_solo_and_location_support` (adds `is_solo`, `local_suggestions`, `location_label`, drops the unique index).

## Routes
All under `auth`, `verified`, `onboarding` middleware (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|------|--------------|-------------------|-------|
| `date-night.history` | GET `/date-night/history` | `DateNightPlanController@history` | Query: `search`, `theme`, `compatibility` (`high`/`medium`/`low`) |
| `date-night.favourites` | GET `/date-night/favourites` | `DateNightPlanController@favourites` | |
| `date-night.show` | GET `/date-night/{dateNightPlan}` | `DateNightPlanController@show` | Policy `view` |
| `date-night.toggle-favourite` | POST `/date-night/{dateNightPlan}/favourite` | `DateNightPlanController@toggleFavourite` | Policy `favourite` |
| `date-night.toggle-like` | POST `/date-night/{dateNightPlan}/like` | `DateNightPlanController@toggleLike` | Policy `like` |
| `date-night.add-to-calendar` | POST `/date-night/{dateNightPlan}/add-to-calendar` | `DateNightPlanController@addToCalendar` | Policy `addToCalendar` |
| `date-night.export` | GET `/date-night/{dateNightPlan}/export` | `DateNightPlanPdfController@export` | Inline participant check, 403 otherwise |
| `questionnaires.plan-status` | GET `/questionnaires/{questionnaire:slug}/plan-status` | `QuestionnaireController@planStatus` | JSON `{ready, plan_id}` |

## Flows

### 1. Questionnaire completion to finished plan
1. `QuestionnaireService` dispatches `QuestionnaireCompleted` (`app/Services/QuestionnaireService.php`). See `questionnaires.md`.
2. `DispatchDateNightPlanOnQuestionnaireCompleted` (queued):
   - Solo questionnaire (`questionnaire.is_solo`): dispatch `GenerateDateNightPlanJob(user, null, questionnaire)`.
   - Otherwise: stop if the user has no partner, or if `QuestionnaireService::partnerHasFreshCompletedResponse()` is false (partner has no completed response, or it is already paired with one of this user's responses). Else dispatch the job with the partner.
3. `GenerateDateNightPlanJob` re-reads the latest completed response(s), returns silently if any is missing or if a plan already exists for those response ids, then calls `GenerateDateNightPlanAction`.
4. The action computes compatibility with `CompatibilityService::calculate()` for couples (falls back to percentage 0 if it returns null). Solo plans use a fixed percentage of 100.
5. `DateNightGeneratorService::generate()`:
   1. Rule based (authoritative): `RuleEngineService::selectTheme()` over active themes (in-memory "Cozy Night In" theme if the table has no active themes), `deriveContext()`, then `buildPlan()` picks fixed strings per context flag.
   2. AI enhancement: calls `AIService::generateDateNightPlan()` or `generateDateNightPlanSolo()` synchronously inside the job. Only `theme`, `summary`, `meal_suggestion`, `atmosphere`, `activity`, `conversation_prompt`, `romantic_challenge` may be replaced, and only by non-empty strings. Solo plans also accept `local_suggestions` (needs `name` and `description`, capped at 6 by the code).
   3. Validation: any non-enhanceable key (currently `theme_emoji`) is reset to the deterministic value.
   4. Persist the plan with the flags below.
6. The action dispatches `DateNightPlanGenerated(userOne, userTwo, plan)`. For solo plans `userTwo` is the filler's partner or null.
7. Listeners: `SendPlanEmailsOnDateNightPlanGenerated` dispatches `SendSummaryEmailJob` per user (skips users with `email_notifications` off, sends `DateNightReadyMail`); `NotifyPlanReadyOnDateNightPlanGenerated` creates a `DateNightPlanReady` notification per user.

**Flags written on the plan** (a plan has no status column):

| AI outcome | `ai_enhanced` | `fallback_used` |
|------------|---------------|-----------------|
| AI succeeded and at least one field was replaced | true | false |
| AI succeeded but returned no usable field | false | false |
| AI threw (for example no active prompt template) or returned a failure | false | true |

AI failures are logged as warnings and never fail the job. Timeouts and retries of the AI call itself are described in `ai.md`.

### 2. Plan-status polling (frontend)
1. `QuestionnaireController@complete` passes `awaiting_plan` = solo questionnaire, or partner has a fresh completed response.
2. If `awaiting_plan`, `Complete.vue` calls `/questionnaires/{slug}/plan-status` immediately and then every 2s.
3. `planStatus` looks for the newest plan of that questionnaire whose response ids include the user's latest completed response. When `ready`, the page visits `/date-night/{plan_id}`.

### 3. Viewing
`show` authorises, then creates a `PartnerViewedPlan` notification for the viewer's partner if the partner can also view the plan, and renders `date-night/Show` with `presenter->detail()`.

### 4. Favourite, like, calendar, export
- Favourite: flips `date_night_plans.is_favourite`.
- Like: `LikeDateNightPlanAction` attaches or detaches the user in `date_night_plan_likes`; on attach the partner gets a notification.
- Add to calendar: validates `date` (required, today or later), `time` (`H:i`, optional), `location` (max 255, optional); creates a calendar event owned by the acting user with the plan's theme, summary and emoji; notifies the partner. See `calendar.md`.
- Export: renders `pdf.date-night-plan` as HTML (`Content-Type: text/html`). The page has a print button; no PDF is generated on the server.

## Rules and gotchas
- **Who can access a plan**: the `partner_user_id` user, or any user owning one of the two responses. All policy abilities delegate to `view`. History and favourites use the same three-way condition in their queries; copy it when adding new listings.
- **Solo vs couple**: a plan is solo when the questionnaire is solo. Solo plans reuse one response for both response columns, set `partner_user_id` so the partner sees the plan and gets the notification and email, store compatibility 100, and copy `location_label` from the response. `Show.vue` hides the score and the second partner for solo plans.
- **Favourites vs likes**: `is_favourite` is one column on the plan, so it is shared by both partners (either can toggle it for both). Likes are per user and notify the partner.
- **`partner_one` is not a stable role**: for couples the job is dispatched by whoever completed second, and that user becomes `userOne` / `partner_one_response_id`.
- **The AI can rename the theme**: `theme` is enhanceable, while `theme_emoji` and `date_night_theme_id` stay rule-engine values. The stored `theme` string may not match any `date_night_themes.name`.
- **Rule engine is keyword matching**: context flags come from substring matches on answer `value`s; themes are scored by keywords in the theme name (`cozy`, `candlelit`, `movie`, `pizza`, `rainy`, `sunset`, `game`, `picnic`, `anniversary`, `coffee`, `music`, `slow`). Renaming or adding a theme without one of these keywords means it never scores. `romance_high` is the average of all numeric answers (8 or more), not a specific question.
- **Queues**: the pipeline spans the `default`, `notifications` and `emails` queues. `php artisan dev` starts `queue:listen` without `--queue`, so it only consumes `default`; notifications and emails need a worker that also listens on the other queues. See `events-jobs.md`.
- **Tests run with the `sync` queue and no AI templates seeded**, so generation tests exercise the fallback path (`fallback_used` true).
- **History filtering is done in PHP** after loading every plan for the user; there is no pagination. Compatibility buckets are hard-coded (high 75+, medium 50 to 74, low under 50).
- `presenter->detail()` does not expose `ai_enhanced` or `fallback_used`, so the frontend cannot show them.
- Pages build URLs as string literals (`/date-night/${id}`), not Wayfinder helpers.
- Suspected issue: `DateNightGeneratorService::secondaryResponseIdFor()` (lines 281-288) picks the most recently completed response for the questionnaire from any user, not the partner. The `partner_two` answers sent to the AI for couple plans can belong to another couple.
- Suspected issue: `GenerateDateNightPlanJob::$timeout` is 120s, but the AI call inside it can take longer with default settings (60s timeout, up to 3 attempts). See `ai.md`.
- Suspected issue: `DateNightPlanController::show()` (line 39) creates a "partner viewed" notification on every page view, with no deduplication.
- Suspected issue: with a synchronous queue, a couple plan is created before the completion page renders, so `awaiting_plan` is false and the page neither polls nor redirects.
- Suspected issue: `Complete.vue` polls forever; there is no attempt cap or error state if generation never produces a plan.
- Suspected issue: solo plans show "100%" compatibility in History, Favourites, the export view and the email; the export view also shows the same user twice.
- Suspected issue: `DateNightPlanPdfController` duplicates the policy logic inline instead of calling `authorize('view', ...)`.
- Suspected issue: `History.vue` never sends the `theme` filter that the controller supports.
- Suspected issue: `Show.vue` has the theme emoji commented out and renders a fixed heart.
- Dead code: `EnhanceDateNightPlanWithAIJob` is only referenced by tests. `PlanResource` is unused and reads a non-existent `activities` attribute. `config/datenight.php` is not read by any code. `DateNightPlanPolicy::update` is not called.

## Related modules
- `questionnaires.md`: completion, responses, compatibility, the completion page.
- `ai.md`: provider, prompt templates (`date_night_plan`, `date_night_plan_solo`), timeouts, failure handling.
- `notifications-activity-mail.md`: notification types and the plan-ready email.
- `events-jobs.md`: full event, listener and queue catalogue.
- `calendar.md`: calendar events created from a plan.
- `weekly-reflection.md` and `dashboard-insights-search.md`: read plan data for summaries.

## Tests
- `php artisan test --filter=DateNightGenerationTest`
- `php artisan test --filter=DateNightPlanTest`
- `php artisan test --filter=GenerateDateNightPlanJobTest`
- AI fallback and the unused enhance job are covered in `tests/Feature/AI/AIIntegrationTest.php`.

## Change log
- 2026-11-24 — Initial doc.
