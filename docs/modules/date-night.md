# Date Night Plans
> Last verified against code: 2026-11-25

## Purpose
When both partners finish the same questionnaire (or one user finishes a solo questionnaire), the app generates a "date night plan": a theme plus meal, atmosphere, activity, conversation prompt and romantic challenge. A deterministic rule engine always produces a plan; the AI layer then optionally rewrites the wording. Users can view, favourite, like, print and add a plan to their calendar, compare both partners' answers from the plan, and ask the AI again when a plan was saved without it.

## Key files

| Path | Role |
|------|------|
| **Backend** | |
| `app/Listeners/DispatchDateNightPlanOnQuestionnaireCompleted.php` | Queued listener (`default` queue). Decides whether to dispatch the generation job |
| `app/Jobs/GenerateDateNightPlanJob.php` | Queued job (`default`, tries 3, timeout 120s). Duplicate guard, then calls the action |
| `app/Actions/GenerateDateNightPlanAction.php` | Loads latest completed responses, gets compatibility, calls the generator, fires `DateNightPlanGenerated` |
| `app/Services/DateNightGeneratorService.php` | Builds the deterministic plan, calls AI, validates, persists the `DateNightPlan` |
| `app/Services/RuleEngineService.php` | Context flags from option signals (keyword fallback) and theme scoring |
| `app/Events/DateNightPlanGenerated.php` | Event: `userOne`, nullable `userTwo`, `plan` |
| `app/Listeners/NotifyPlanReadyOnDateNightPlanGenerated.php` | Queued (`notifications`). Creates `AppNotification` rows |
| `app/Listeners/SendPlanEmailsOnDateNightPlanGenerated.php` | Queued (`emails`). Dispatches `SendSummaryEmailJob` per user |
| `app/Jobs/SendSummaryEmailJob.php`, `app/Mail/DateNightReadyMail.php`, `resources/views/emails/date-night-ready.blade.php` | Plan-ready email |
| `app/Http/Controllers/DateNightPlanController.php` | show, history, favourites, toggleFavourite, toggleLike, addToCalendar, answers, compatibility, retryAi |
| `app/Http/Controllers/DateNightPlanPdfController.php`, `resources/views/pdf/date-night-plan.blade.php` | "Print": printable HTML page |
| `app/Actions/RetryDateNightPlanWithAiAction.php` | Re-runs the AI enhancement for a fallback plan |
| `app/Actions/LikeDateNightPlanAction.php` | Toggle like, notify partner |
| `app/Actions/AddDateNightPlanToCalendarAction.php` | Create `CalendarEvent` (type `date_night`), notify partner |
| `app/Http/Requests/DateNightPlan/AddToCalendarRequest.php` | Validation for add-to-calendar |
| `app/Policies/DateNightPlanPolicy.php` | Participant check used by every ability |
| `app/Http/Presenters/DateNightPlanPresenter.php` | `detail()` and `summary()` arrays for Inertia |
| `app/Models/DateNightPlan.php`, `app/Models/DateNightTheme.php` | Models |
| `app/Http/Controllers/QuestionnaireController.php` (`complete`, `planStatus`) | Completion page props and the polling endpoint |
| `database/seeders/DateNightThemeSeeder.php` | 12 themes, `firstOrCreate` by name |
| `database/factories/DateNightPlanFactory.php`, `DateNightThemeFactory.php` | Factories |
| **Frontend** | |
| `resources/js/pages/date-night/Show.vue` | Plan page: favourite, like, print, add-to-calendar modal, "How you matched" links (couple plans), "Try again with AI" (fallback plans) |
| `resources/js/pages/date-night/History.vue` | Paginated list with search, theme and compatibility filters |
| `resources/js/pages/date-night/Favourites.vue` | The viewer's own favourites |
| `resources/js/pages/questionnaires/PartnerAnswers.vue`, `Compatibility.vue` | Rendered by the plan-scoped `answers` and `compatibility` routes (prop `plan_id`) |
| `resources/js/pages/questionnaires/Complete.vue` | "Generating" screen, polls plan status |
| **Tests** | |
| `tests/Feature/DateNightGenerationTest.php` | Pipeline, dedupe, rule engine, notifications, mail |
| `tests/Feature/DateNightPlanTest.php` | View/authorisation, history, favourites, export, likes, calendar |
| `tests/Feature/Jobs/GenerateDateNightPlanJobTest.php` | Job queueing and no-op case |
| `tests/Feature/DateNightEngineTest.php` | Date-out plan without AI, solo wording, signals, romance sliders, seeders re-run, null AI provider |
| `tests/Feature/DateNightPlanFinishesTest.php` | Plan-scoped comparison pages, history theme filter and pagination, print page, retry with AI |
| `tests/Feature/DateNightPlanCompletionTest.php` | "We did it": ratings per person, partner notice, validation, history badge, moment prefill and linked moments |
| `tests/Feature/DateNightPlanRegenerateTest.php` | Regenerate with a steer: rewrite, prompt content, failure, the limit of three, consent |

## Data model

**`date_night_plans`**
- `questionnaire_id`, `partner_one_response_id`, `partner_two_response_id` (FKs, cascade delete). Solo plans store the same response id in both columns.
- `date_night_theme_id` (nullable, null on delete), `partner_user_id` (nullable FK to users, null on delete; set only on solo plans to the filler's partner).
- `compatibility_score` (unsigned tinyint), `is_solo`, `ai_enhanced`, `fallback_used` (booleans). `is_favourite` was dropped by `2026_11_24_215154_create_date_night_plan_favourites_table` (favourites are per person now).
- Text content: `theme`, `theme_emoji`, `summary`, `meal_suggestion`, `atmosphere`, `activity`, `conversation_prompt`, `romantic_challenge` (the suggestion columns were widened from string to text).
- `local_suggestions` (json, cast to array) and `location_label`: set on solo plans and on couple date-out plans.
- Index `dnp_responses_index` on the two response ids. It is no longer unique (dropped so solo plans can reuse one response).
- `drink_suggestion` and `music_vibe` were removed by `2026_08_02_213000_remove_drink_and_music_from_date_night_plans`.
- Relations: `questionnaire`, `partnerOneResponse`, `partnerTwoResponse`, `dateNightTheme`, `partnerUser`, `likedBy`, `favouritedBy` (both belongsToMany). Scopes: `visibleTo($user)` (the participant condition), `favouritedBy($user)`, `withFavouriteFor($user)` (adds a boolean `is_favourite` attribute for that viewer; the presenter reads it).

- `last_steer` (string 200, nullable) and `regeneration_count` (tinyint, default 0): the last change a user asked for and how many have been made.

**`date_night_plan_completions`**: `date_night_plan_id`, `user_id` (both cascade), `rating` (1 to 5), `note` (nullable, 500), `completed_on` (date), timestamps, unique on the pair. Relation `completedBy` (with pivot), scope `withDone()`.

**`date_night_plan_likes`** and **`date_night_plan_favourites`**: `date_night_plan_id`, `user_id`, timestamps, unique on the pair. The favourites migration copied every old shared favourite to each person on the plan.

**`date_night_themes`**: `name`, `emoji`, `description`, `colour` (7 chars), `active`. Scope `active()`.

Migrations that touch `date_night_plans` but are not named `date_night_*`: `2026_07_25_231000_create_ai_generations_table` (adds `ai_enhanced`, `fallback_used`) and `2026_07_26_150000_add_solo_and_location_support` (adds `is_solo`, `local_suggestions`, `location_label`, drops the unique index).

## Routes
All under `auth`, `verified`, `onboarding` middleware (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|------|--------------|-------------------|-------|
| `date-night.history` | GET `/date-night/history` | `DateNightPlanController@history` | Query: `search`, `theme` (exact), `compatibility` (`high`/`medium`/`low`), `page`. 10 per page. Props: `plans` (`data`, `links`, `total`, `from`, `to`, `last_page`), `themes`, `filters` |
| `date-night.favourites` | GET `/date-night/favourites` | `DateNightPlanController@favourites` | |
| `date-night.show` | GET `/date-night/{dateNightPlan}` | `DateNightPlanController@show` | Policy `view` |
| `date-night.toggle-favourite` | POST `/date-night/{dateNightPlan}/favourite` | `DateNightPlanController@toggleFavourite` | Policy `favourite` |
| `date-night.toggle-like` | POST `/date-night/{dateNightPlan}/like` | `DateNightPlanController@toggleLike` | Policy `like` |
| `date-night.add-to-calendar` | POST `/date-night/{dateNightPlan}/add-to-calendar` | `DateNightPlanController@addToCalendar` | Policy `addToCalendar` |
| `date-night.export` | GET `/date-night/{dateNightPlan}/export` | `DateNightPlanPdfController@export` | Policy `view` |
| `date-night.answers` | GET `/date-night/{dateNightPlan}/answers` | `DateNightPlanController@answers` | Policy `view`; 404 for solo plans. Renders `questionnaires/PartnerAnswers` |
| `date-night.compatibility` | GET `/date-night/{dateNightPlan}/compatibility` | `DateNightPlanController@compatibility` | Policy `view`; 404 for solo plans. Renders `questionnaires/Compatibility` |
| `date-night.retry-ai` | POST `/date-night/{dateNightPlan}/retry-ai` | `DateNightPlanController@retryAi` | Policy `update`, `throttle:ai`. Flashes `success` or `error` |
| `date-night.regenerate` | POST `/date-night/{dateNightPlan}/regenerate` | `DateNightPlanController@regenerate` | Policy `update`, `throttle:ai`, `RegenerateDateNightPlanRequest` (`steer` 3 to 200 characters). Flashes `success` or `error` |
| `date-night.complete` | POST `/date-night/{dateNightPlan}/complete` | `DateNightPlanController@complete` | Policy `complete`, `CompleteDateNightPlanRequest` (`rating` 1 to 5, `note` max 500, `completed_on` not after the user's today) |
| `questionnaires.plan-status` | GET `/questionnaires/{questionnaire:slug}/plan-status` | `QuestionnaireController@planStatus` | JSON `{ready, plan_id}` |

## Flows

### 1. Questionnaire completion to finished plan
1. `QuestionnaireService` dispatches `QuestionnaireCompleted` (`app/Services/QuestionnaireService.php`). See `questionnaires.md`.
2. `DispatchDateNightPlanOnQuestionnaireCompleted` (queued):
   - Solo attempt (`$event->response->isSolo()`, see `questionnaires.md`): dispatch `GenerateDateNightPlanJob(user, null, questionnaire)`.
   - Otherwise: stop if the user has no partner, or if `QuestionnaireService::partnerHasFreshCompletedResponse()` is false (partner has no completed response, or it is already paired with one of this user's responses). Else dispatch the job with the partner.
3. `GenerateDateNightPlanJob` re-reads the latest completed response(s), returns silently if any is missing or if a plan already exists for those response ids, then calls `GenerateDateNightPlanAction`.
4. Job and action treat the run as solo when `userTwo` is null or `questionnaire.forcesSolo()`, and load each user's latest completed response in that mode (`Response::scopeInMode`). The action computes compatibility with `CompatibilityService::calculate()` for couples, passing the two exact responses (falls back to percentage 0 if it returns null). Solo plans use a fixed percentage of 100.
5. `DateNightGeneratorService::generate()`:
   1. Rule based (authoritative): `RuleEngineService::selectTheme()` over active themes (in-memory "Cozy Night In" theme if the table has no active themes), `deriveContext()`, then `buildPlan()` picks fixed strings per context flag. A date-out questionnaire (`questionnaire.is_solo`) takes the date-out branch (`buildDateOutPlan`): title, emoji, meal and activity come from `DATE_OUT_STYLES` (first matching flag in the order food, music, outdoors, adventure, movies, games, relaxing; else "A Date Out"), the location label is worked in as "near X" when there is one, and the summary says "for you" on a solo plan and "for you both" otherwise. The home theme is still stored in `date_night_theme_id` but its name and emoji are not used for date-out plans.
   2. AI enhancement: synchronously inside the job. One response: `generateDateNightPlanSolo()`. Two responses on a date-out questionnaire (`questionnaire.is_solo`): use case `date_night_plan_out_couple`, which asks for a compromise between both partners' answers and gets the location (the first partner's who gave one) with the **smaller** of the two travel radii. Two responses otherwise: `generateDateNightPlan()`. Only `theme`, `summary`, `meal_suggestion`, `atmosphere`, `activity`, `conversation_prompt`, `romantic_challenge` may be replaced, and only by non-empty strings. Plans that use a location (solo, and couple date-out plans) also accept `local_suggestions` (needs `name` and `description`, capped at 6 by the code).
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

### 4. Favourite, like, calendar, print
- Favourite: toggles the viewer's row in `date_night_plan_favourites`. Nothing is sent to the partner.
- Like: `LikeDateNightPlanAction` attaches or detaches the user in `date_night_plan_likes`; on attach the partner gets a notification.
- Add to calendar: validates `date` (required, today or later), `time` (`H:i`, optional), `location` (max 255, optional); creates a calendar event owned by the acting user with the plan's theme, summary and emoji, and `date_night_plan_id` so the calendar can link back to the plan; notifies the partner. See `calendar.md`.
- Print: renders `pdf.date-night-plan` as HTML (`Content-Type: text/html`). The page has a Print button; no PDF is generated on the server. It shows the location and local suggestions when the plan has them, and for a solo plan it leaves out the score and the second name.

### 5. How you matched (couple plans)
`answers` and `compatibility` take the plan's two response ids, put the viewer's own response first, and render the existing questionnaire pages with `plan_id` set (back link and cross links then point at the plan). Compatibility is recalculated from those exact responses, so it works after later attempts and after the couple disconnects. The old `questionnaires.partner-answers` and `questionnaires.compatibility` routes redirect here once a couple plan exists for the user's latest completed couple attempt.

### 6. Try again with AI
`Show.vue` shows the offer when `plan.fallback_used` is true. `retryAi` calls `RetryDateNightPlanWithAiAction`, which recalculates compatibility for couples and calls `DateNightGeneratorService::retryEnhancement()`: the stored wording is used as the deterministic plan, the same AI call as generation runs, and on success the plan is updated in place (`fallback_used` false, `ai_enhanced` as usual, `local_suggestions` replaced only if returned). On failure nothing changes. The call is synchronous in the web request.

### 7. Regenerate with a steer ("Not quite right?")
`Show.vue` shows the section while `plan.can_regenerate` is true (the `ai_generation` flag is on, everyone whose answers are in the plan has agreed to AI, and fewer than `DateNightPlan::MAX_REGENERATIONS` = 3 changes have been made). `regenerate` calls `RetryDateNightPlanWithAiAction::execute($plan, $steer)`, which runs the same `retryEnhancement()` as "Try again with AI" with the steer added to the prompt context as `requested_change` (the stored wording is sent as `deterministic_plan`, so the AI changes the plan the user is looking at). On success the plan is updated in place, `last_steer` is saved, `regeneration_count` goes up by one and the partner gets a `date_night_plan_ready` notification quoting the request. On failure nothing changes and nothing is counted. Synchronous in the web request, like retry.

### 8. We did it
`complete` stores one row per person in `date_night_plan_completions` through `CompleteDateNightPlanAction` (insert or update; `completed_on` defaults to the user's own today). The first rating by a person sends the partner a `partner_completed_plan` notification; changing it sends nothing. The plan page shows both ratings (`plan.completion.mine` / `.partner`), the moments linked to the plan (`plan.moments`: the viewer's and their partner's) and "Capture a moment from it", which opens `/moments?plan={id}` with the form prefilled (see `moments-timeline.md`). History and Favourites show a "Done" badge from `is_done` (anyone has rated it; load with `DateNightPlan::withDone()`). Ratings also go into the weekly reflection context and the data export.

## Rules and gotchas
- **AI needs consent.** The generator passes the owner of each response in the prompt to `AIService`; unless all of them agreed to AI processing the call is refused (`ai_consent_missing`) and the plan is saved with the rule-based wording and `fallback_used = true`, exactly as for an AI outage. "Try again with AI" fails the same way until they agree; the fallback card on the plan page links to Settings. See `ai.md`.
- **Who can access a plan**: the `partner_user_id` user, or any user owning one of the two responses, **unless that person has hidden the plan** (`date_night_plan_hidden`: `date_night_plan_id`, `user_id`; written when a connection ends and they choose not to keep shared plans, see `onboarding-partner.md`). All policy abilities delegate to `view`. Listings use `DateNightPlan::visibleTo($user)`, which already leaves hidden plans out; a query that cannot use it adds `->notHiddenFor($user)` (search, timeline, insights, hub and dashboard do). Achievements, the weekly reflection, the data export and the questionnaire pairing logic deliberately still count hidden plans.
- **Solo vs couple**: a plan is solo when the questionnaire is solo. Solo plans reuse one response for both response columns, set `partner_user_id` so the partner sees the plan and gets the notification and email, store compatibility 100, and copy `location_label` from the response. `Show.vue` hides the score and the second partner for solo plans.
- **Favourites vs likes**: both are per user. A favourite is private (the button says "Save to my favourites"); a like notifies the partner. `is_favourite` in the Inertia props is computed per viewer, so load plans with `withFavouriteFor($user)` (or `loadExists`) before passing them to the presenter, otherwise it is false.
- **`partner_one` is not a stable role**: for couples the job is dispatched by whoever completed second, and that user becomes `userOne` / `partner_one_response_id`.
- **The AI can rename the theme**: `theme` is enhanceable, while `theme_emoji` and `date_night_theme_id` stay rule-engine values. The stored `theme` string may not match any `date_night_themes.name`.
- **Rule engine signals**: `question_options.signals` is a JSON list from the fixed vocabulary `RuleEngineService::SIGNALS` (`food`, `outdoors`, `movies`, `games`, `music`, `relaxing`, `adventure`, `romance`), each mapping to one context flag. An answer whose option has signals contributes exactly those; an answer without (no option, or an untagged option) falls back to substring matching on its raw `value`. `romance_high` is true with a `romance` signal, or when the sliders whose question title contains "romance" or "romantic" average 8 or more; no other numeric answer counts. Answers must be loaded with `question` and `questionOption`.
- **Themes are still scored by keywords in the theme name** (`cozy`, `candlelit`, `movie`, `pizza`, `rainy`, `sunset`, `game`, `picnic`, `anniversary`, `coffee`, `music`, `slow`). Renaming or adding a theme without one of these keywords means it never scores. No theme is tied to `prefers_adventure`.
- **Queues**: the pipeline spans the `default`, `notifications` and `emails` queues. `AppServiceProvider` re-registers the dev `queue` command with `--queue=default,notifications,emails,analytics,ai,exports`, so `composer dev` works every queue. Production workers still need the same list.
- **Tests run with the `sync` queue and no AI templates seeded**, so generation tests exercise the fallback path (`fallback_used` true).
- **History** is filtered in SQL and paginated (10). Compatibility buckets are hard-coded (high 75+, medium 50 to 74, low under 50) and leave solo plans out. The theme filter matches the stored `theme` string exactly; the list of themes is the distinct themes of the user's own plans.
- **Retry with AI runs in the web request** and can take as long as one AI call (see `ai.md`, worst case timeout x retries).
- Older code in these pages builds URLs as string literals (`/date-night/${id}`); new links use the Wayfinder helpers from `@/routes/date-night`.
- Suspected issue: `DateNightGeneratorService::secondaryResponseIdFor()` (lines 281-288) picks the most recently completed response for the questionnaire from any user, not the partner. The `partner_two` answers sent to the AI for couple plans can belong to another couple.
- Suspected issue: `GenerateDateNightPlanJob::$timeout` is 120s, but the AI call inside it can take longer with default settings (60s timeout, up to 3 attempts). See `ai.md`.
- Suspected issue: `DateNightPlanController::show()` (line 39) creates a "partner viewed" notification on every page view, with no deduplication.
- Suspected issue: with a synchronous queue, a couple plan is created before the completion page renders, so `awaiting_plan` is false and the page neither polls nor redirects.
- Suspected issue: `Complete.vue` polls forever; there is no attempt cap or error state if generation never produces a plan.
- Suspected issue: solo plans show "100%" compatibility in the plan-ready email.
- Suspected issue: `Show.vue` has the theme emoji commented out and renders a fixed heart.

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
- `php artisan test --filter=DateNightEngineTest`
- `php artisan test --filter=DateNightPlanFinishesTest`
- `php artisan test --filter=DateNightPlanCompletionTest`
- `php artisan test --filter=DateNightPlanRegenerateTest`
- AI fallback is covered in `tests/Feature/AI/AIIntegrationTest.php`.

## Change log
- 2026-11-24 — Initial doc.
- 2026-11-24 — Couple date-out plans: mode comes from the response, new `date_night_plan_out_couple` prompt (compromise), shared location with the smaller travel radius; the generator no longer guesses the partner response.
- 2026-11-24 — Roadmap Phase 1: `summary()` sends `is_solo` and a null score for solo plans; a failed generation job notifies both people.
- 2026-11-24 — Roadmap Phase 2 (2.7, 2.9, 2.10, 2.16): date-out rule-based plans with solo wording; option signals drive the rule engine; plan-scoped answers and compatibility pages; per-person favourites; print page with location, local ideas and solo handling; history theme filter and pagination; "Try again with AI".
- 2026-11-25 — Roadmap Phase 3 (3.6): AI enhancement only runs when everyone whose answers are in the plan has agreed to AI processing.
- 2026-11-26 — Roadmap Phase 4: removed `EnhanceDateNightPlanWithAIJob`, `PlanResource` and `config/datenight.php`.
- 2026-11-26 — Plan page (`date-night/Show.vue`) redesigned: theme emoji and an animated compatibility ring in the hero, the plan sections as a swipeable step-by-step deck with dots and arrows, the romantic challenge sealed behind a reveal button, local ideas as a swipe row. Presentation only; props are unchanged.
- 2026-11-25 — Roadmap Phase 5.2 and 5.3: "We did it" (per-person ratings, linked moments, Done badge) and regenerate with a steer (three changes per plan, `requested_change` in the prompt context).
- 2026-11-25 — Calendar events added from a plan store `date_night_plan_id`; the calendar links back to the plan.
- 2026-11-25 — Roadmap Phase 5.12: plans can be hidden per person (`date_night_plan_hidden`, `notHiddenFor()`); partner notices use `activePartner()` and stop while a connection is paused.
