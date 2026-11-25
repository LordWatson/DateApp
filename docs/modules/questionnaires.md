# Questionnaires

> Last verified against code: 2026-11-24

## Purpose

The questionnaire engine. A user picks a questionnaire, answers it one question per screen (answers autosave), reviews a summary and finishes. For couple questionnaires each partner answers separately; once both have a fresh completed response the pair is compared and a date night plan is generated. Solo questionnaires are answered by one person, capture a location and travel radius, and produce a plan immediately. A questionnaire flagged `partner_optional` ("Plan a Date Out") lets the user choose per attempt: alone, or together with their partner. Attempts can be repeated: each attempt is a new `responses` row.

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/QuestionnaireController.php` | All user-facing actions (see Routes) |
| `app/Services/QuestionnaireService.php` | Response lifecycle, answer storage, pairing/"fresh" logic, completion + event dispatch |
| `app/Services/CompatibilityService.php` | Deterministic match score between the two partners |
| `app/Actions/SummariseCoupleAnswersAction.php` | Prompt-friendly answer summary; used only by `app/Jobs/EnhanceDateNightPlanWithAIJob.php` |
| `app/Http/Presenters/QuestionnairePresenter.php` | Shapes every Inertia prop array for this module |
| `app/Models/{Questionnaire,Question,QuestionOption,Response,Answer}.php` | Models |
| `app/Enums/{QuestionType,QuestionnaireStatus,QuestionnaireVisibility,CompletionStatus}.php` | Enums |
| `app/Http/Requests/Questionnaire/{SaveAnswerRequest,FinishQuestionnaireRequest,StartQuestionnaireRequest}.php` | Validation. `SaveAnswerRequest` resolves the question (`question()`) and validates `value` per question type. `StartQuestionnaireRequest` validates `mode` (`solo`/`couple`) and exposes `solo(): ?bool` |
| `app/Policies/ResponsePolicy.php` | Owner-only view/update/delete (not called anywhere, see gotchas) |
| `app/Http/Resources/{Questionnaire,Question,Response}Resource.php` | JSON resources (not used anywhere) |
| `app/Events/{QuestionnaireStarted,QuestionAnswered,QuestionnaireCompleted,CompatibilityCalculated}.php` | Events (only `QuestionnaireCompleted` is dispatched by app code) |
| `app/Listeners/*OnQuestionnaireCompleted.php`, `app/Listeners/UpdateStreakOnQuestionnairCompleted.php` | The 7 completion listeners (note the typo in the last filename) |
| `app/Jobs/{GenerateCompatibilityJob,GenerateCompatibilitySummaryJob,SendQuestionnaireCompletedNotificationJob}.php` | Jobs |
| `app/Mail/QuestionnaireCompletedMail.php` | "Partner completed" email, view `emails.questionnaire-completed` |
| `database/seeders/{QuestionnaireSeeder,SeasonalQuestionnaireSeeder,IntimacyQuestionnaireSeeder,SoloDatePlannerQuestionnaireSeeder}.php` | Content |
| `database/factories/{Questionnaire,Question,QuestionOption,Response,Answer}Factory.php` | Factories (`draft()`, `archived()`, `singleChoice()`, `slider()`, `completed()` ...) |
| **Frontend** | |
| `resources/js/pages/questionnaires/Index.vue` | Paginated list + filter chips |
| `resources/js/pages/questionnaires/Show.vue` | Intro: start / resume / restart, saved-profile picker, past results |
| `resources/js/pages/questionnaires/Question.vue` | One question per screen, autosave, auto-advance |
| `resources/js/pages/questionnaires/Summary.vue` | Review + edit, solo location/travel card, finish, save-as-profile modal |
| `resources/js/pages/questionnaires/Complete.vue` | Done screen; polls `plan-status` and redirects to the plan |
| `resources/js/pages/questionnaires/PartnerAnswers.vue` | Side-by-side answers (also used for past attempts) |
| `resources/js/pages/questionnaires/Compatibility.vue` | Score + matched/different lists |
| `resources/js/components/questions/{SingleChoice,MultipleChoice,Slider,Text,Textarea}Question.vue` | One input component per question type |
| `resources/js/components/OptionCard.vue` | Option tile used by the two choice components |
| `resources/js/components/ProgressBar.vue` | Used by `Question.vue` (also achievements and `CompatibilityCard`) |
| `resources/js/components/CompatibilityCard.vue` | Used by `pages/Dashboard.vue`, not by this module's pages |
| `resources/js/components/StepIndicator.vue` | Used only by `pages/onboarding/Index.vue` |
| `resources/js/components/QuestionCard.vue` | Not imported anywhere |
| **Tests** | see Tests |

## Data model

| Table | Important columns | Notes |
|---|---|---|
| `questionnaires` | `slug` (unique, route key), `status`, `visibility`, `display_order`, `estimated_minutes`, `active_from`, `active_until`, `artwork`, `is_seasonal`, `is_intimacy`, `is_solo`, `partner_optional` | Casts: `status` -> `QuestionnaireStatus` (draft/active/archived), `visibility` -> `QuestionnaireVisibility` (public/private), three booleans. `questions()` is ordered by `display_order`. |
| `questions` | `questionnaire_id` (cascade), `type`, `required` (default true), `minimum_value`, `maximum_value`, `step_value`, `unit` (max 32), `display_order` | `type` -> `QuestionType`. `display_order` is the `{order}` URL segment. |
| `question_options` | `question_id` (cascade), `title`, `description`, `emoji`, `value`, `signals` (nullable json, cast to array), `display_order` | `value` is what gets stored in `answers.value`. `signals` is a list from `RuleEngineService::SIGNALS` that tells the plan engine what the option means (see `date-night.md`). |
| `responses` | `user_id`, `questionnaire_id` (both cascade), `status`, `is_solo` (nullable: the mode chosen for this attempt), `started_at`, `completed_at`, `compatibility_score`, `location_label/city/region/country`, `location_latitude/longitude` (decimal 10,7), `travel_radius_minutes` | One row per attempt. `status` -> `CompletionStatus` (in_progress/completed). |
| `answers` | `response_id`, `question_id` (cascade), `question_option_id` (nullable, null on delete), `value` (text), `created_at` | `$timestamps = false`; `created_at` is set manually. |

Migrations worth knowing:

- `2026_07_17_233800_allow_multiple_responses_per_questionnaire` drops the unique `(user_id, questionnaire_id)` on `responses` and adds index `(user_id, questionnaire_id, status)`.
- `2026_07_14_000005` adds `artwork`, `is_seasonal`; `2026_07_19_143700_create_intimacy_module_tables` adds `is_intimacy`.
- `2026_07_26_150000_add_solo_and_location_support` adds `questionnaires.is_solo`, the four `responses.location_*` text columns, and drops the unique response pair on `date_night_plans` so a solo plan can reference the same response twice.
- `2026_11_24_192850_add_partner_optional_mode_to_questionnaires` adds `questionnaires.partner_optional` and `responses.is_solo`, and sets `partner_optional` on the `plan-a-date-out` row.
- `2026_07_29_150000` adds latitude/longitude/`travel_radius_minutes`; `2026_07_29_150500` adds `questions.step_value` and `unit`.

### How each question type is stored and rendered

Accepted values (`SaveAnswerRequest`, 422 otherwise): `single_choice` one option value, or `custom_amount:<1 to 7 digits>` when the question has a `custom_amount` option; `multiple_choice` an array of distinct option values (empty clears the answer); `slider` a number within min and max and on a step (defaults 1, 10, 1); `text` a string up to 255; `textarea` a string up to 500 (both may be empty).

| `QuestionType` | Stored as (`QuestionnaireService::saveAnswer`) | Input component | Compatibility score |
|---|---|---|---|
| `single_choice` | Delete existing rows, insert one row: `value` = option value, `question_option_id` = matching option or null | `SingleChoiceQuestion` | 1 if equal, else 0 (0 if either missing) |
| `multiple_choice` | Delete existing rows, insert one row per selected value | `MultipleChoiceQuestion` | Jaccard (intersection / union); both empty = 1 |
| `slider` | One row via `updateOrCreate`, `value` = number as string, no option | `SliderQuestion` (defaults min 1, max 10, step 1) | `1 - distance / (max - min)` |
| `text` | One row via `updateOrCreate` | `TextQuestion` | Not scored; listed under "matched" if either answered |
| `textarea` | Same as `text` | `TextareaQuestion` (`maxlength` 500) | Same as `text` |

- `question` action sends `current_answer` as an array only for `multiple_choice`, otherwise a scalar or null.
- Special value: a single-choice option with value `custom_amount` (solo budget question) makes `SingleChoiceQuestion` show a number input and emit `custom_amount:<digits>`. That string matches no option, so `question_option_id` is null. `Summary.vue` renders it as a pound amount and `Question.vue` suppresses auto-advance for it.
- Slider `unit === 'minutes'` is formatted as hours/minutes in `SliderQuestion` and `Summary.vue`; other units are appended as text.

## Routes

All in `routes/web.php` under `auth`, `verified`, `onboarding`; prefix `questionnaires`, name prefix `questionnaires.`. `{questionnaire}` binds by `slug`.

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `index` | GET `/` | `index` | Active only, 10 per page, `?filter=all\|couples\|solo\|intimacy\|seasonal` |
| `show` | GET `/{slug}` | `show` | May auto-start a new cycle and redirect to question 1 |
| `start` | POST `/{slug}/start` | `start` | Find-or-create in-progress response, redirect to first unanswered question. Optional `mode` (`solo`/`couple`); also updates the mode of an attempt already in progress |
| `restart` | POST `/{slug}/restart` | `restart` | Deletes in-progress responses, creates a new one. Optional `mode` |
| `past-attempt` | GET `/{slug}/past-attempts/{response}` | `pastAttempt` | Renders `PartnerAnswers` with `is_past_attempt` |
| `question` | GET `/{slug}/question/{order}` | `question` | Unknown order redirects to `show` |
| `answer` | POST `/{slug}/question/{order}/answer` | `answer` | JSON `{saved: true}`; `SaveAnswerRequest` validates per type (422) before the in-progress check (409) |
| `summary` | GET `/{slug}/summary` | `summary` | Redirects to `show` if no response |
| `finish` | POST `/{slug}/finish` | `finish` | `FinishQuestionnaireRequest`; redirects to `complete`, or back to `summary` with an `answers` error while a required question has no non-empty answer |
| `complete` | GET `/{slug}/complete` | `complete` | `awaiting_plan` = solo or partner fresh-completed |
| `plan-status` | GET `/{slug}/plan-status` | `planStatus` | JSON `{ready, plan_id}` |
| `partner-answers` | GET `/{slug}/partner-answers` | `partnerAnswers` | Redirects to `date-night.answers` once a couple plan exists for the user's latest completed couple attempt; before that needs partner + both completed + partner "fresh" |
| `compatibility` | GET `/{slug}/compatibility` | `compatibility` | Redirects to `date-night.compatibility` once that plan exists; before that the `compatibility` prop is null unless partner "fresh" |

Admin CRUD for questionnaires and questions lives in `routes/admin.php` (see `admin.md`).

## Flows

**Answering**

1. `Show.vue` posts to `start` (or `restart`). `findOrCreateResponse` returns the latest in-progress response or creates one.
2. `Question.vue` watches the answer, debounces 300 ms, then `axios.post`s `{value}` to `answer`. On failure it retries every 3 s. For `single_choice` it auto-advances 400 ms after a successful save.
3. Last question goes to `summary`. "Edit" links back to `question/{order}`.
4. `Summary.vue` posts to `finish` (solo: with location fields and `travel_radius_minutes`). The controller refuses while required questions are unanswered (the error is shown above the Complete button). `QuestionnaireService::completeWithLocation` saves location only when `is_solo`, then `completeResponse`.
5. `completeResponse` sets status/`completed_at`, runs `wipeOverhangingPendingResponses`, then dispatches `QuestionnaireCompleted`.
6. `Complete.vue`: if `awaiting_plan`, polls `plan-status` every 2 s and visits `/date-night/{plan_id}` when ready.

**`QuestionnaireCompleted` listeners** (registered in `app/Providers/EventServiceProvider.php`)

| Listener | Queue | Effect |
|---|---|---|
| `UpdateStreakOnQuestionnairCompleted` | `default` | `UpdateStreakAction` |
| `NotifyPartnerOnQuestionnaireCompleted` | `notifications` | Skips solo attempts / no partner. Creates `AppNotification` (`PartnerCompleted`): "{name} wants to plan a date with you" when the partner still has to answer, the older "completed the questionnaire" copy when they already have. Then dispatches `SendQuestionnaireCompletedNotificationJob` (queue `notifications`) which queues `QuestionnaireCompletedMail` |
| `DispatchDateNightPlanOnQuestionnaireCompleted` | `default` | Solo attempt: always dispatches `GenerateDateNightPlanJob(user, null, q)`. Couple: only if partner has a fresh completed response. See `date-night.md` |
| `EvaluateAchievementsOnQuestionnaireCompleted` | `analytics` | `AchievementEvaluationJob` |
| `RefreshDashboardOnQuestionnaireCompleted` | `analytics` | `RefreshRelationshipStatisticsJob` |
| `LogActivityOnQuestionnaireCompleted` | sync | Activity log entry `questionnaire_completed` |
| `InvalidateCacheOnQuestionnaireCompleted` | `default` | Forgets `dashboard:{id}` and `compatibility:{id}` for user and partner |

**Couple cycles (the "fresh" rule)**

- Pairing is recorded only by a `date_night_plans` row referencing both responses (`partner_one_response_id` / `partner_two_response_id`).
- `partnerHasFreshCompletedResponse` is true when the partner's latest completed response is not yet paired with any of the user's completed responses.
- `shouldAutoStartNewCycle` (checked in `show`): user has nothing in progress, their latest completed response is already paired, and the partner is fresh. Then a new response is started automatically.
- `wipeOverhangingPendingResponses` (couple only): on completion, deletes the user's older completed-but-unpaired responses and the partner's `PartnerCompleted` notifications for that questionnaire.
- `Index.vue` shows "Pending Partner" when the latest response is completed, not solo and not paired.
- Past results on `Show.vue` list only the user's responses that appear in a plan; `pastAttempt` shows the two responses of that plan.

**Choosing solo or couple per attempt (`partner_optional`)**

- `Show.vue` shows a "Who's planning?" choice when the `mode.selectable` prop is true and posts `mode` to `start` / `restart`. Without a partner only "Just me" is offered, with a link to `/partner`; `couple` is rejected server-side when the user has no partner.
- The choice is stored in `responses.is_solo`. `Response::isSolo()` is the single source of truth for an attempt: the stored value, or the questionnaire's `is_solo` when it is null (every questionnaire without the choice, and attempts made before the choice existed).
- Everything downstream reads the attempt, not the questionnaire: partner notification, plan dispatch, overhang cleanup, `awaiting_plan`, the index status label.
- Couple pairing only ever looks at couple attempts: `Response::scopeInMode($questionnaire, $solo)` filters completed responses by mode (a no-op for questionnaires without the choice), so a solo attempt is never paired with the partner's couple attempt.
- Solo: the plan is generated as soon as the attempt is finished. Couple: the first partner finishes, the other gets a notification asking them to answer, and the plan is generated when both couple attempts are complete.
- When the partner is already waiting, `Show.vue` preselects "With {partner}", and `show` auto-starts a couple attempt under the existing new-cycle rule.
- `Questionnaire::forcesSolo()` (`is_solo` and not `partner_optional`) marks a solo-only questionnaire.

**Solo vs couple**

| | Couple attempt | Solo attempt |
|---|---|---|
| Location + travel radius | Collected and saved only when the questionnaire is `is_solo` (so both modes of "Plan a Date Out") | Collected on `Summary.vue` (browser geolocation, Nominatim reverse/forward geocode, manual entry, Bristol fallback), saved on the response |
| Partner notification/email | Yes | No |
| Plan generation | When both are fresh-completed | Immediately; same response used for both plan refs, compatibility fixed at 100 |
| Overhang cleanup | Yes | No |
| `awaiting_plan` on Complete | Only if partner fresh | Always |

## Rules and gotchas

- **Queues must be running.** Listeners use queues `default`, `notifications`, `analytics` (`.env.example` sets `QUEUE_CONNECTION=database`; `phpunit.xml` uses `sync`). Without a worker no plan is created and `Complete.vue` polls forever (it has no timeout).
- Event discovery is off (`bootstrap/app.php` `withEvents(discover: false)`); a new listener must be added to `EventServiceProvider::$listen`.
- Only `index` filters by `status = active`. Every other action works for any questionnaire reachable by slug. `visibility`, `active_from` and `active_until` are stored and editable in admin but never enforced.
- "couples" filter means not solo, not intimacy, not seasonal. A `partner_optional` questionnaire keeps `is_solo = true`, so it is listed under "solo" even though it can be answered together.
- Authorization is by query scoping (responses are always looked up by `user_id`). `pastAttempt` checks ownership inline. `SaveAnswerRequest::authorize` returns true.
- Validation: `SaveAnswerRequest` checks the value against the question type (see the table above). `required` is not enforced per answer, so autosave can clear a choice or text mid-edit; `finish` enforces it (an answer row with an empty value does not count). `QuestionnaireService::applyProfile` writes answers without this validation.
- `FinishQuestionnaireRequest`: label max 120, city/region/country max 80, lat -90..90, lng -180..180, `travel_radius_minutes` 0..600 (UI slider is 0..240, step 30, default 60).
- `saveAnswer` falls through to the single-row text branch for any type that is not single or multiple choice.
- A required slider has no answer until the user moves it (the component shows the minimum but emits nothing).
- `QuestionnairePresenter::progress` percentage is based on the current position, not on answered count.
- `SummariseCoupleAnswersAction` outputs option titles, or the raw value when there is no option (so `custom_amount:50` reaches the AI prompt as-is; the prompt template in `AiPromptTemplateSeeder` explains the format).
- Seeders: `SoloDatePlannerQuestionnaireSeeder` is idempotent (`updateOrCreate` then deletes and recreates questions, which nulls `answers.question_option_id` and cascades answers). Seasonal and intimacy seeders use `firstOrCreate` (existing rows are never updated, except that an option whose `signals` is still null gets the seeded signals). `QuestionnaireSeeder` does nothing when `tonight` exists apart from the same signals backfill (matched by question title and option value). `SeasonalQuestionnaireSeeder` also seeds non-seasonal ones (`weekend-away`, `staycation`, `rainy-day`).
- Option signals: all four seeders set `signals` where the meaning is clear. A new option without signals still works through the engine's keyword fallback. The admin editor does not expose the column yet.
- `bootstrap/cache/events.php` can exist locally (gitignored). If listeners seem stale, run `php artisan event:clear`.
- Suspected issue: `CompatibilityService::calculate` loads each partner's latest response by id regardless of status, so an in-progress restart is compared instead of the completed one. `app/Services/CompatibilityService.php:21-31`.
- Suspected issue: `getAnswerValues` uses `->filter()`, which drops an option value of `"0"`. `app/Services/CompatibilityService.php:158-165`.
- Dead code: `QuestionnaireStarted`, `QuestionAnswered` are never dispatched. `GenerateCompatibilityJob` (the only dispatcher of `CompatibilityCalculated`) is never dispatched. `GenerateCompatibilitySummaryJob` is dispatched only in `tests/Feature/AI/AIIntegrationTest.php` and its cache key `ai:compat_summary:plan:{id}` is never read. The three `*Resource` classes and `ResponsePolicy` are unused. `responses.compatibility_score` is never written by app code (only read by `AdminAnalyticsController`). The `compatibility:{id}` cache key is only ever forgotten, never set. `resources/js/components/QuestionCard.vue` is not imported.
- Admin: `AdminQuestionController` validates `type` as any string (an invalid value would break the enum cast; the admin form's select offers the five valid ones), and `AdminQuestionnaireController` validates `is_seasonal` but not `is_solo` / `is_intimacy`. See `admin.md`.

### Adding a new question type

1. Add the case to `app/Enums/QuestionType.php`.
2. Storage: if it is not "one text row", add a branch in `QuestionnaireService::saveAnswer` and the matching branch in `applyProfile`.
3. `QuestionnaireController::question`: `current_answer` is an array only for `multiple_choice` (hard-coded string check).
4. Scoring: add an arm to the `match` in `CompatibilityService::calculate` (unhandled types are skipped) and to `formatAnswerLabels`.
5. Frontend: new component in `resources/js/components/questions/`, a `v-else-if` in `Question.vue`, and display handling in `Summary.vue` (`formatAnswer`) and `PartnerAnswers.vue` (`formatAnswers`).
6. Add an arm to the `match` in `SaveAnswerRequest::rules()` (an unhandled type throws).
7. Add a state to `QuestionFactory`, add the option to the admin form (`resources/js/pages/admin/questionnaires/Edit.vue`), and check the date night rule engine handles the answers (`date-night.md`).

### Adding a new questionnaire

- Via a seeder: copy `SoloDatePlannerQuestionnaireSeeder` (idempotent pattern). Set a unique `slug`, `status` active, `display_order`, and the `is_solo` / `is_intimacy` / `is_seasonal` flags; number questions and options with `display_order` starting at 1 and contiguous (the flow navigates by `display_order +/- 1`). Register it in `DatabaseSeeder`.
- Via admin UI: possible, but it cannot set `is_solo` or `is_intimacy`.
- For an AI-tuned plan see `ai.md` / `date-night.md`.

## Related modules

- `date-night.md` — plan generation after completion, `GenerateDateNightPlanJob`, rule engine.
- `saved-profiles.md` — save/apply answer sets (UI lives in this module's pages).
- `ai.md` — `SummariseCoupleAnswersAction` consumer, compatibility summary use case.
- `events-jobs.md`, `notifications-activity-mail.md`, `challenges-achievements-streaks.md`, `dashboard-insights-search.md` — listener side effects.
- `intimacy.md` — lists `is_intimacy` questionnaires. `admin.md` — questionnaire/question CRUD. `onboarding-partner.md` — partner link used by all pairing logic.

## Tests

| File | Covers |
|---|---|
| `tests/Feature/QuestionnaireTest.php` | Models, ordering, cascades |
| `tests/Feature/QuestionnaireFlowTest.php` | HTTP flow, filters, auto-start cycle, answers, summary, finish, compatibility, partner answers, saved profile store/apply |
| `tests/Feature/ResponseTest.php` | Response/answer models, multiple responses allowed |
| `tests/Feature/Events/QuestionnaireCompletedEventTest.php` | Event dispatch |
| `tests/Feature/Listeners/QuestionnaireCompletedListenersTest.php` | Listener registration, plan dispatch rules, partner notification |
| `tests/Feature/AnswerValidationTest.php` | Accepted and rejected values per question type; `finish` refusing unanswered required questions |
| `tests/Feature/DateOutPlanningModeTest.php` | Solo vs couple attempts of a `partner_optional` questionnaire: immediate solo plan, partner request then shared plan, no cross-mode pairing, couple refused without a partner, intro props |

```
php artisan test --filter=QuestionnaireTest
php artisan test --filter=QuestionnaireFlowTest
php artisan test --filter=ResponseTest
php artisan test --filter=QuestionnaireCompleted
```

No dedicated tests for: `restart`, `pastAttempt`, `planStatus`, solo location saving, `wipeOverhangingPendingResponses`.

## Change log

- 2026-11-24 — Initial doc.
- 2026-11-24 — "Plan a Date Out" can be answered alone or with a partner: `partner_optional`, `responses.is_solo`, `Response::isSolo()` / `scopeInMode()`, mode chooser on `Show.vue`.
- 2026-11-24 — `questionnaires/Question.vue`: Back / Continue moved from a fixed block (hidden behind the tab bar) to a sticky pill bar above it.
- 2026-11-24 — Roadmap Phase 1: only `start`/`restart` create an attempt; completed summary is read-only; completion screen redirects to an existing plan, handles no partner and stops polling after 90s; saved profiles apply via axios and only to their own questionnaire; slider emits its starting value; no invented location; `whereNumber('order')`.
- 2026-11-24 — Roadmap Phase 2 (2.7, 2.10, 2.11): `question_options.signals`; per-type answer validation; `finish` refuses unanswered required questions; comparison pages are driven from the plan and the old routes redirect to them.
