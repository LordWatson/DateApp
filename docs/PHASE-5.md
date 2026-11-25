# Phase 5: new features, step by step

> Written 2026-11-25 from `docs/ROADMAP.md` (Phase 5 table) and the module docs in `docs/modules/`.
> File and class names come from those docs. Anything marked **check** was not read in the code and must be confirmed before you rely on it.

## How to use this

- The 12 features are grouped into five sub-phases (5A to 5E). Work the sub-phases in order; inside one, the features are independent unless a "Needs" line says otherwise.
- Each feature has: **Decide first** (product choices, with a recommended default), **Steps** (in build order), **Tests**, **Docs**, **Done when**.
- Sizes: **S** under an hour to half a day, **M** about a day, **L** multi-day.
- Tick the box when "Done when" is true and the named tests pass.

| Sub-phase | Theme | Features | Rough effort |
|---|---|---|---|
| 5A | Quick wins on existing data | 5.1, 5.5, 5.6, 5.9 | 2 to 3 days |
| 5B | Close the plan and challenge loops | 5.2, 5.3, 5.4 | 3 to 4 days |
| 5C | Calendar and invitations | 5.7, 5.8 | 2 days |
| 5D | Preferences and support | 5.10, 5.11 | 2 to 3 days |
| 5E | Pause or end a connection | 5.12 | 4 to 5 days |

Dependencies: 5.3 reads the ratings from 5.2 (optional). 5.10 must cover the emails added by 5.1. 5.12 needs 5.9 and touches every "notify the partner" path, so it goes last.

## Rules for every feature

Do these each time; the steps below do not repeat them.

**Before starting**

1. Read the module docs named in the feature's **Docs** line, plus `events-jobs.md` for anything with a listener, job or scheduled command, and `frontend.md` for page work.
2. Branch from `master`.

**While building**

- Request path: thin controller, FormRequest, Action with `execute()`, Service for domain logic, Presenter or hand-built array for props.
- Create files with `php artisan make:... --no-interaction` (run PHP through PowerShell).
- A couple is two `users` rows. Any new per-couple value is stored on **both** rows and written together in one transaction.
- New listener: add it to `$listen` in `app/Providers/EventServiceProvider.php`. New scheduled command: add it to `withSchedule` in `bootstrap/app.php`.
- New `NotificationType` case: also add its link in `destination()` and its emoji in `typeEmoji` in `resources/js/pages/notifications/Index.vue`.
- New email: check the recipient's email preference in the job (see 5.10).
- New table or column holding user data: add it to `app/Jobs/ExportUserDataJob.php`, and check `app/Actions/DeleteAccountAction.php` still cleans up.
- Frontend: Wayfinder helpers only. JSON endpoints are called with `axios`.
- Migrations must run on SQLite (tests) and MySQL.

**Before calling it done**

1. `vendor/bin/pint --dirty --format agent`
2. `vendor/bin/phpstan analyse`
3. `php artisan test --filter=<the tests named>` and then the whole suite once per sub-phase.
4. `npm run lint`, `npm run types:check`, `npm run build`.
5. Update the module docs (steps in `docs/modules/README.md`) and tick the box here.

---

## Sub-phase 5A: quick wins

### - [x] 5.1 Nudge your partner (S)

A "Remind {partner}" button on the waiting screen, and one automatic reminder 24 hours after the first partner finished.

**Decide first**

- How often: recommended one nudge per attempt per 24 hours, and the automatic reminder only if no manual nudge was sent.
- Email as well as in-app: recommended yes, reusing the existing "wants to plan a date with you" email.

**Steps**

1. Migration: add `partner_nudged_at` (nullable datetime) to `responses`.
2. Add `NotificationType::PartnerNudge = 'partner_nudge'`. Link it to `/questionnaires/{slug}` using `data.questionnaire_slug`.
3. Create `app/Actions/NudgePartnerAction.php` with `execute(Response $response): bool`. It returns false and does nothing unless all of these hold:
   - the response is completed and `Response::isSolo()` is false;
   - the owner has a partner;
   - the response is not yet in a plan (use the same check the questionnaire index uses for "Pending Partner": **check** `QuestionnaireService` for the method);
   - `partner_nudged_at` is null or more than 24 hours old.

   When it passes: create the partner's `AppNotification` (`data`: `questionnaire_id`, `questionnaire_slug`), dispatch `SendQuestionnaireCompletedNotificationJob` for the email, set `partner_nudged_at = now()`.
4. In `QuestionnaireService::wipeOverhangingPendingResponses()`, also delete the partner's `partner_nudge` notifications for that questionnaire (it only deletes `partner_completed` today).
5. Route in `routes/web.php`: `POST questionnaires/{questionnaire:slug}/nudge`, name `questionnaires.nudge`, `QuestionnaireController@nudge`. It loads the user's latest completed couple response, calls the action, and flashes `success` ("Reminder sent") or `error` ("You can remind {name} again tomorrow").
6. `QuestionnaireController@complete`: add props `can_nudge` (bool) and `partner_name`.
7. `resources/js/pages/questionnaires/Complete.vue`: in the "waiting for your partner" state show the button when `can_nudge` is true; post with the Wayfinder helper from `@/routes/questionnaires`; hide it after a successful post.
8. Create command `datenight:nudge-partners` (`app/Console/Commands/NudgePartnersCommand.php`): select completed responses with `completed_at` between 72 and 24 hours ago and `partner_nudged_at` null, call the action for each. Schedule it hourly in `bootstrap/app.php`.

**Tests** (`tests/Feature/NudgePartnerTest.php`): nudge creates one notification and one mail for the partner; a second nudge within 24 hours does nothing; refused for a solo attempt, with no partner, and once a plan exists; the command nudges a 25-hour-old response once and skips a 1-hour-old one; the partner finishing removes the nudge notification.

**Docs**: `questionnaires.md`, `notifications-activity-mail.md`, `events-jobs.md` (scheduler table).

**Done when**: a waiting user can send one reminder a day, and an unanswered request is chased exactly once without anyone pressing anything.

---

### - [x] 5.5 "On this day" card on the dashboard (S)

**Decide first**

- Sources: recommended moments (own and partner's) and date night plans. Leave love notes out.
- Empty state: recommended hide the card entirely.

**Steps**

1. `DashboardController@index`: add an `onThisDay` prop, built from the user's own date (`User::today()`):
   - moments: `whereIn('user_id', [me, partner_id])`, `whereMonth('date', m)`, `whereDay('date', d)`, `whereYear('date', '<', y)`, newest first, limit 3;
   - plans: `DateNightPlan::visibleTo($user)` with the same three clauses on `created_at`, limit 3.

   Each item: `type`, `id`, `title`, `emoji`, `years_ago`, `url` (`moments.show` / `date-night.show`). Sort by `years_ago` and keep 3 in total.
2. Create `resources/js/components/OnThisDayCard.vue` (props: `items`). Render "1 year ago today" and a link per item. Render nothing when `items` is empty.
3. Place it in `resources/js/pages/Dashboard.vue` under the challenge card.

Known limits to note in the doc: plan dates use the UTC `created_at`, so a plan made near midnight can show a day early or late for users far from UTC; 29 February only matches in leap years.

**Tests** (add to `tests/Feature/DashboardTest.php`): a moment dated one year ago today appears; a partner's moment appears; one from yesterday's date last year does not; a stranger's does not; the prop is an empty list with nothing to show.

**Docs**: `dashboard-insights-search.md`.

**Done when**: a couple with a moment or plan from this date in an earlier year sees it on the dashboard, and nobody else sees an empty card.

---

### - [x] 5.6 React to a love note (S)

**Decide first**

- Reaction or threaded reply: recommended a single emoji reaction from a fixed set (for example ❤️ 🥰 😂 😘 🔥), plus a "Reply" button that simply opens the Send tab. Threads are a different feature.
- Can a reaction be changed or removed: recommended yes.

**Steps**

1. Migration: add `reaction` (nullable string, 16) and `reacted_at` (nullable datetime) to `love_notes`.
2. Add the allowed list as a constant on `App\Models\LoveNote` (`REACTIONS`), add both columns to `#[Fillable]`, cast `reacted_at`.
3. Add `NotificationType::LoveNoteReaction = 'love_note_reaction'`, linking to `/love-notes/{id}`.
4. Create `app/Http/Requests/LoveNote/ReactToLoveNoteRequest.php`: `reaction` nullable, `Rule::in(LoveNote::REACTIONS)`.
5. Route: `POST love-notes/{loveNote}/react`, name `love-notes.react`, `LoveNoteController@react`. Abort 403 unless the user is the **recipient** (same inline check style as `show`). Save the reaction (null clears it). Notify the sender only when the reaction goes from null to a value; no email.
6. Add `reaction` to the note payload sent by `index` and `show`.
7. `resources/js/pages/love-notes/Index.vue` and `Show.vue`: on received notes show the emoji row and a Reply button; on sent notes show the partner's reaction.
8. Replace the hard-coded `'/love-notes'` URLs in these two pages with Wayfinder helpers while you are there.

**Tests** (add to `tests/Feature/LoveNoteTest.php`): recipient reacts and the sender gets one notification; changing the reaction sends no second notification; clearing works; the sender and a third user get 403; an emoji outside the list is rejected.

**Docs**: `love-notes.md` (the Purpose line says notes cannot be replied to), `notifications-activity-mail.md`.

**Done when**: the recipient can react in one tap and the sender sees it.

---

### - [x] 5.9 Connection anniversary (S)

Today "Since" is read from the newest accepted invitation sent by either person (`PartnerController.php:35-41`). That is wrong after an admin reconnect, and can pick up an invitation from an earlier partner.

**Decide first**

- Reconnecting the same two people: recommended a fresh date (the old one is lost on disconnect).
- Anniversary notice: recommended in-app only, to both, once a year.

**Steps**

1. Migration: add `connected_at` (nullable datetime) to `users`. Backfill in the same migration: for every user with a `partner_id`, set it to the newest `accepted_at` among invitations sent by that user or their partner (the current query), so nothing changes on screen for existing couples.
2. Write it wherever `partner_id` is written, always on both rows:
   - `PartnerInvitationService::acceptInvitation()`: `now()`;
   - `PartnerInvitationService::disconnectPartner()`: null;
   - `Admin\AdminCoupleController@reconnect`: `now()`;
   - `DeleteAccountAction`: null on the surviving partner.
3. Cast `connected_at` in `User::casts()`.
4. `PartnerController@index`: `connectedSince` becomes `$user->connected_at?->toISOString()`. Remove the invitation query.
5. Add `NotificationType::ConnectionAnniversary = 'connection_anniversary'`, linking to `/partner`.
6. Create command `datenight:connection-anniversaries`: users with a partner whose `connected_at` month and day equal today's (UTC) and whose year is earlier; create one notification each ("2 years together today"), then dispatch `AchievementEvaluationJob` for each. Schedule daily at 09:00.
7. Achievement: add a `days_connected` arm to `AchievementService::conditionMet()` (days since `connected_at` >= value, false when null) and seed "One Year Together" (365) in `AchievementSeeder` (it is idempotent).

**Tests** (`tests/Feature/ConnectionAnniversaryTest.php`): accepting sets the date on both users; disconnect clears both; the partner page shows it; the command notifies both on the day and nobody the day after; the achievement unlocks at 365 days.

**Docs**: `onboarding-partner.md` (remove the `connectedSince` suspected issue), `challenges-achievements-streaks.md` (condition table), `events-jobs.md`, `admin.md`.

**Done when**: "Since" is right for every couple however they were connected, and the yearly notice and badge arrive.

---

**Sub-phase 5A was completed on 2026-11-25.** PHPUnit (511 tests), PHPStan, Pint, ESLint, `vue-tsc` and the build pass. Nothing was clicked through by hand. As built:

- **5.1** One manual reminder per attempt per 24 hours; the hourly command sends one automatic reminder to attempts 24 to 72 hours old that were never nudged. A reminder is also refused when the partner has already answered (the plan is on its way). Reminders are removed when the partner answers.
- **5.5** Moments (own and partner's) and date nights; the card is hidden when empty.
- **5.6** Five reactions (❤️ 🥰 😂 😘 🔥), changeable and removable; only the first reaction notifies the sender. "Reply" opens the Send tab. No threads.
- **5.9** `users.connected_at`, backfilled from accepted invitations. The anniversary uses the UTC day. The "One Year Together" achievement needs `AchievementSeeder` re-run. Not done from the step list: the command does not guard against being run twice on the same day.
- **Deploy:** `php artisan migrate`, re-run `AchievementSeeder`, and make sure the scheduler runs (two new commands).

---

## Sub-phase 5B: close the loops

### - [x] 5.2 "We did it" on a plan (M)

Mark a plan as done, rate the night, and capture a moment from it. `moments.date_night_plan_id` exists and is validated, but no screen sets it.

**Decide first**

- One rating per couple or per person: recommended per person, like likes and favourites.
- Who sees the rating: recommended both partners.
- Solo plans: recommended allowed, same flow.

**Steps**

1. Migration: create `date_night_plan_completions`: `date_night_plan_id` and `user_id` (both FK, cascade), `rating` (unsigned tinyint), `note` (nullable string, 500), `completed_on` (date), timestamps, unique on the pair.
2. `DateNightPlan`: add `completedBy()` (`belongsToMany` with pivot `rating`, `note`, `completed_on`), following `likedBy()`.
3. `DateNightPlanPolicy`: add `complete`, delegating to `view` like the others.
4. Create `app/Http/Requests/DateNightPlan/CompleteDateNightPlanRequest.php`: `rating` required integer 1 to 5; `note` nullable max 500; `completed_on` nullable date, not after today.
5. Create `app/Actions/CompleteDateNightPlanAction.php`: insert or update the user's row (`completed_on` defaults to `User::today()`); on first insert notify the partner through `NotificationService` (follow `notifyPartnerLikedPlan`) with a new `NotificationType::PartnerCompletedPlan = 'partner_completed_plan'` linking to the plan.
6. Route: `POST date-night/{dateNightPlan}/complete`, name `date-night.complete`, `DateNightPlanController@complete`.
7. `DateNightPlanPresenter`: `detail()` gains `completion` (`mine`: rating, note, date or null; `partner`: rating and date or null) and `moments` (id, title, photo URL for moments with this `date_night_plan_id` owned by the viewer or their partner). `summary()` gains `is_done`.
8. `resources/js/pages/date-night/Show.vue`: a "We did it" button opening a dialog (`ui/dialog`) with five stars and a note. Once done, show the rating, the partner's rating, the linked moments, and "Capture a moment".
9. "Capture a moment" links to `/moments?plan={id}`. In `MomentController@index`, when `plan` is present and the user can view that plan, send a `prefill` prop (`date_night_plan_id`, `title` = plan theme, `date` = the completion date). In `resources/js/pages/moments/Index.vue`, open the create sheet with those values and submit `date_night_plan_id` as a hidden field.
10. History and Favourites: show a "Done" badge from `is_done`.
11. Weekly reflection: add this week's completed plans and their ratings to the context it sends (read `weekly-reflection.md` first; **check** where the context is built).

**Tests** (`tests/Feature/DateNightPlanCompletionTest.php`): a participant completes and the partner is notified once; re-rating updates the row without a second notification; a stranger gets 403; rating 0 and 6 are rejected; the plan page lists a moment created with the plan id; `/moments?plan=` for someone else's plan sends no prefill.

**Docs**: `date-night.md`, `moments-timeline.md`, `notifications-activity-mail.md`, `weekly-reflection.md`.

**Done when**: a couple can mark a plan done, both ratings show on the plan, and a moment created from it is linked back.

---

### - [x] 5.3 Regenerate a plan with a steer (M)

"Not quite right?" plus one line of direction. Builds on "Try again with AI" (`RetryDateNightPlanWithAiAction`, `DateNightGeneratorService::retryEnhancement()`).

**Needs**: the AI. Without consent from everyone on the plan, or with `ai_generation` off, a steer cannot be honoured.

**Decide first**

- Overwrite the plan or create a new one: recommended overwrite in place, as retry does.
- Limit: recommended 3 regenerations per plan, on top of `throttle:ai`.
- Who can do it: recommended either participant; the partner is notified.

**Steps**

1. Migration: add `last_steer` (nullable string, 200) and `regeneration_count` (unsigned tinyint, default 0) to `date_night_plans`.
2. Prompts: read `ai.md`, then in `database/seeders/AiPromptTemplateSeeder.php` add a new **version** of `date_night_plan`, `date_night_plan_solo` and `date_night_plan_out_couple` that includes a `{{steer}}` line ("The couple asked for this change: ..."). **Check** how an empty variable is rendered so that ordinary generation, which has no steer, does not send a dangling sentence. Deploy with `php artisan datenight:sync-prompts` (it only inserts missing versions).
3. `DateNightGeneratorService::retryEnhancement()`: add an optional `?string $steer = null` parameter and pass it into the prompt context. No second code path.
4. `RetryDateNightPlanWithAiAction::execute()`: add the same optional parameter; when a steer is given and the AI succeeds, save `last_steer` and increment `regeneration_count`.
5. Create `app/Http/Requests/DateNightPlan/RegenerateDateNightPlanRequest.php`: `steer` required string, 3 to 200 characters.
6. Route: `POST date-night/{dateNightPlan}/regenerate`, name `date-night.regenerate`, policy `update`, `throttle:ai`. The controller refuses with an `error` flash when `regeneration_count` is 3. On AI failure nothing changes and the user is told so.
7. Notify the partner on success: reuse `NotificationType::DateNightPlanReady` with a body such as "{name} changed the plan: {steer}".
8. `DateNightPlanPresenter::detail()`: add `can_regenerate` (AI available for this plan and count under 3) and `regenerations_left`.
9. `Show.vue`: a collapsed "Not quite right?" section with a text input and a few quick chips ("Cheaper", "More active", "Stay in", "Less cooking"). Show a spinner while the request runs; it is synchronous and can take as long as one AI call. When `can_regenerate` is false because of consent, show the same link to Settings as the fallback card.
10. Optional, after 5.2: pass the couple's lowest-rated past themes into the context as "avoid".

**Tests** (`tests/Feature/DateNightPlanRegenerateTest.php`; fake the AI the way `tests/Feature/AI/AIIntegrationTest.php` does): a successful call updates the wording and stores the steer; the steer text reaches the prompt; an AI failure leaves the plan unchanged; the fourth attempt is refused; a non-participant gets 403; an empty steer is rejected.

**Docs**: `date-night.md`, `ai.md`.

**Done when**: a participant can type one line and get a changed plan, at most three times, with the partner told.

---

### - [x] 5.4 Shared daily challenge with a couple streak (M)

Each partner can already mark today's challenge done. This adds a streak that grows on days when **both** did.

**Decide first**

- Day boundary: recommended keep the UTC day the daily challenge already uses.
- Unpartnered users: recommended no streak shown.
- Disconnect: recommended current streak resets, longest is kept.

**Steps**

1. Migration: add `challenge_streak` and `longest_challenge_streak` (unsigned int, default 0) and `challenge_streak_date` (nullable date) to `users`.
2. `CompleteDailyChallengeAction`: after the insert, if the partner has also completed today's challenge, update **both** users in one transaction with `lockForUpdate()`:
   - `challenge_streak_date` is today: do nothing (already counted);
   - it is yesterday: streak + 1;
   - otherwise: streak = 1;
   - then set the date to today and `longest = max(longest, streak)`.
3. `User`: add `currentChallengeStreak(): int`, which returns `challenge_streak` when `challenge_streak_date` is today or yesterday, else 0. Always read the streak through it; that way no nightly reset command is needed.
4. `PartnerInvitationService::disconnectPartner()`: set `challenge_streak` to 0 and the date to null on both.
5. When the streak increases, dispatch `AchievementEvaluationJob` for the partner too (`EvaluateAchievementsOnActivity` only covers the person who pressed Done).
6. Achievements: add a `challenge_streak_days` arm to `AchievementService::conditionMet()` using `currentChallengeStreak()`, and seed two rows in `AchievementSeeder` (7 and 30 days).
7. `DashboardController@index`: add `couple_streak` and `longest_couple_streak` to `todayChallenge` (null without a partner).
8. `resources/js/components/ChallengeCard.vue`: show the streak with a flame, and a "Both done" state when `completed_by_me` and `completed_by_partner` are true.

**Tests** (add to `tests/Feature/DailyChallengeTest.php`): one partner alone does not move the streak; both on the same day makes it 1 for both; both again the next day makes it 2; a missed day reads as 0 and the next shared day makes it 1; pressing Done twice does not double count; disconnect resets it; the 7-day achievement unlocks for both.

**Docs**: `challenges-achievements-streaks.md`, `dashboard-insights-search.md`.

**Done when**: the card shows a streak that only grows when both partners complete the day's challenge.

---

**Sub-phase 5B was completed on 2026-11-25.** PHPUnit (533 tests), PHPStan, Pint, ESLint, Prettier, `vue-tsc` and the build pass. Nothing was clicked through by hand, and no real AI call was made: the steer was tested against a stand-in provider. As built:

- **5.2** One rating per person (1 to 5 stars, optional note); the partner is told the first time. The plan page lists both ratings and the moments captured from it; History and Favourites show "Done". Ratings feed the weekly reflection context and the data export. Not built: a "done" filter on History, and a rating-based achievement.
- **5.3** Either participant, three changes per plan, in place, only when the AI is on and everyone in the plan has agreed to it. The steer travels inside the prompt context as `requested_change`, and the three plan prompts gained a rule to honour it (v12, v2, v2). Not built: passing low-rated past themes as "avoid" (step 10, optional).
- **5.4** UTC day, couples only, stored on both users, reset on disconnect with the longest kept. No reset command: the streak is read through `User::currentChallengeStreak()`. Two achievements: 7 and 30 days.
- **Deploy:** `php artisan migrate`, `php artisan datenight:sync-prompts`, re-run `AchievementSeeder`.

---

## Sub-phase 5C: calendar and invitations

### - [x] 5.7 Recurring anniversaries and birthdays with a countdown (M)

**Decide first**

- Recurrence options: recommended yearly only.
- Default: recommended "Repeats every year" ticked for new `anniversary` and `birthday` events, and existing events of those types left as they are.

**Steps**

1. Migration: add `recurrence` (nullable string) and `reminded_for_date` (nullable date) to `calendar_events`.
2. Create `app/Enums/CalendarRecurrence.php` with the single case `Yearly = 'yearly'`. Cast it on `CalendarEvent`.
3. Add `recurrence` (nullable, `Rule::enum`) to `StoreCalendarEventRequest` and `UpdateCalendarEventRequest`.
4. `CalendarEvent`: add `nextOccurrence(CarbonImmutable $today): CarbonImmutable`. One-off events return `date`. Yearly events return this year's date if it is today or later, else next year's; build it with `addYearsNoOverflow` so 29 February falls on 28 February in other years.
5. `CalendarEventController::formatEvent()`: add `recurrence`, `next_date`, `days_until` (from `User::today()`), and `occurrence` (next year minus the original year, for "5th anniversary").
6. `resources/js/pages/calendar/Index.vue`: add the checkbox to the form; split upcoming and past by `next_date`; show "in 12 days" / "today" on upcoming rows and the ordinal on recurring ones. Same badge on `calendar/Show.vue`.
7. `RelationshipHubController`: upcoming events are selected by `date >= today`, which drops every recurring event after its first year. Load recurring events separately, merge, sort by next occurrence, take 3.
8. `SendCalendarRemindersCommand`: for recurring events, work from the next occurrence instead of `date`, and treat the reminder as sent when `reminded_for_date` equals that occurrence. Leave `reminder_sent` in charge of one-off events. Include recurring events in the query regardless of how old `date` is.
9. `CalendarEventController@update`: clear `reminded_for_date` when `date`, `reminder` or `recurrence` changes.

Left out on purpose: timeline and search still show only the original date.

**Tests** (add to `CalendarEventTest.php` and `Commands/SendCalendarRemindersCommandTest.php`): a yearly event from three years ago has a `next_date` in the next 12 months and the right `occurrence`; it is listed as upcoming and on the hub; its reminder is sent once this year and again for next year's occurrence; a 29 February event resolves in a non-leap year; an invalid `recurrence` is rejected.

**Docs**: `calendar.md` (the "single-day, no recurrence" line), `dashboard-insights-search.md`.

**Done when**: an anniversary entered once shows a countdown and reminds the couple every year.

---

### - [x] 5.8 Invite by shareable link or QR code (M)

The confirm screen already exists (Phase 0.4: `GET invite/{token}` shows it, `POST invite/{token}/accept` connects). What is missing is making an invitation without an email address and handing the link to the sender.

**Decide first**

- QR code: drawing one needs a library. Recommended: ship copy and share first; add the QR only after approving one small client-side npm package (for example `qrcode`). **Never** use an online QR image service: the link is a bearer token and would be sent to a third party.
- One pending invitation at a time: recommended keep (creating a link expires a pending email invitation, as today).

**Steps**

1. Migration: make `partner_invitations.email` nullable.
2. `PartnerInvitationService::createInvitation()`: accept `?string $email`.
3. Create `app/Http/Requests/Onboarding/CreateInviteLinkRequest.php` with no fields and the same "sender must not have a partner" rule as `SendInvitationRequest`.
4. Routes, both under `throttle:invites`: `POST partner/invite-link` (`partner.create-link`) and `POST onboarding/invite-link` (`onboarding.create-link`). Each creates the invitation with a null email and sends no mail.
5. `PartnerController@index` and `OnboardingController@index`: send `inviteUrl` for the user's own pending invitation. Phase 0.9 removed the raw token from these props; sending the sender their own link is fine, but send only the URL and only for their own invitation.
6. Go through every reader of `invitation->email` and handle null:
   - `SendInvitationEmailJob`: return early;
   - `PartnerController@resendInvite`: answer "nothing to resend" for a link invitation;
   - `ExpireInvitationsCommand`: notice text ("Your invitation link expired");
   - `DashboardController` `pendingInvitation`, `Partner.vue` pending state: show "Invitation link" in place of the address;
   - admin pages and `ExportUserDataJob`: run `grep -rn "invitation" app resources/js/pages/admin` and fix what prints the email.
7. `resources/js/pages/Partner.vue` and step 3 of `onboarding/Index.vue`: add "Share a link instead". Once created, show the link with **Copy** (`navigator.clipboard`) and **Share** (`navigator.share`, hidden where unsupported), the expiry date, and Revoke.
8. If the QR was approved: render it in the browser from `inviteUrl` in a small `InviteQrCode.vue`.

**Tests** (add to `PartnerInvitationTest.php`): a link invitation is created with a null email and no mail is sent; the sender's page has `inviteUrl` and the partner page of another user does not; a second user opening the link sees the confirm page and can connect; a partnered user cannot create one; creating a link expires the pending email invitation; resend on a link invitation sends nothing; the expiry command handles it.

**Docs**: `onboarding-partner.md`.

**Done when**: two people can connect with no email involved, and nothing connects until the second person presses Connect.

---

**Sub-phase 5C was completed on 2026-11-25.** PHPUnit (552 tests), PHPStan, Pint, ESLint, Prettier, `vue-tsc` and the build pass. Nothing was clicked through by hand; copy and share use browser features that the automated tests do not exercise. As built:

- **5.7** Yearly only. New anniversaries and birthdays default to repeating; existing events were left alone and can be edited to repeat. Countdown and "3rd · every year" badges on the calendar and the event page; the hub shows repeating events; each occurrence gets its own reminder. Timeline and search still use the original date.
- **5.8** Copy and Share only: **the QR code was not built**, because it needs a new npm package and that needs the owner's approval (step 8). One pending invitation at a time, as before. The sender's own link is now sent to the Partner and onboarding pages for email invitations too.
- **Deploy:** `php artisan migrate`.

---

## Sub-phase 5D: preferences and support

### - [x] 5.10 Notification preferences (M)

Today there is one switch, `users.email_notifications`. `push_notifications` exists and nothing uses it.

**Decide first**

- Granularity: recommended five categories, not one toggle per notification type: partner activity, love notes, moments, reminders, challenges.
- In-app notifications: recommended always created (the Phase 2.6 decision); preferences control email only.
- Reminder frequency for the "answer a questionnaire" reminder: weekly (today's behaviour), monthly, or off.

**Steps**

1. Migration: add `notification_preferences` (nullable json) to `users`. Keep `email_notifications` as the master switch.
2. Create `app/Enums/NotificationCategory.php` (string-backed, with `label()`): `PartnerActivity`, `LoveNotes`, `Moments`, `Reminders`, `Challenges`.
3. `User`: cast the column to array; add `wantsEmail(NotificationCategory $category): bool` (master switch on, and the category not set to false) and `reminderFrequency(): string` (default `weekly`).
4. Replace the direct `email_notifications` checks with `wantsEmail()`:
   - `SendQuestionnaireCompletedNotificationJob`, `SendSummaryEmailJob`, `SendPartnerConnectedEmailJob`: `PartnerActivity`;
   - `SendLoveNoteNotificationJob`: `LoveNotes`;
   - `SendMomentNotificationJob`: `Moments`;
   - the nudge email from 5.1: `PartnerActivity`.
5. `SendRemindersCommand`: skip users whose frequency is `off`; use 30 days in place of 7 for `monthly`. The 5.1 automatic nudge and calendar reminders are in-app and stay as they are.
6. `NotificationsUpdateRequest`: add `preferences` (array, keys limited to the enum values, boolean values) and `reminder_frequency` (`Rule::in`).
7. `Settings\NotificationsController`: send the current values and the category labels; save them.
8. `resources/js/pages/settings/Notifications.vue`: the master toggle, the five category toggles (disabled while the master is off) and the frequency select.
9. Recommended for consent: a one-click unsubscribe link in each notification email. Add a signed route (`URL::signedRoute`) `GET notifications/unsubscribe/{user}/{category}` that turns that category off and shows a short confirmation page, and add the link to the five Blade views in `resources/views/emails/`.

**Tests** (add to `tests/Feature/Settings/NotificationSettingsTest.php`): turning love notes off stops that email and still creates the in-app notification; other categories still send; the master switch off stops all; an unknown category key is rejected; `off` and `monthly` change who `SendRemindersCommand` reminds; the unsubscribe link works when signed and is refused when tampered with.

**Docs**: `auth-settings.md`, `notifications-activity-mail.md`, `events-jobs.md`.

**Done when**: a user can keep the emails they want, drop the rest, and leave any email category from the email itself.

---

### - [x] 5.11 Admin "view as user" (M)

Support cannot see what a user sees. This lets a super admin browse the app as a user, read-only, with every use audited.

**Decide first. This one has a privacy cost.**

- While viewing as a user the admin can read love notes, intimate questionnaire answers and private moment notes. Recommended: allow it, but say so in the privacy policy (`legal/Privacy.vue`) before release, keep it to super admins, and time-limit each session to 30 minutes.
- Read-only or full control: recommended read-only.
- Targets: recommended never another admin.

**Steps**

1. Routes:
   - `POST admin/users/{user}/view-as`, name `admin.users.view-as`, in `routes/admin.php` with `can:super-admin`;
   - `POST stop-viewing`, name `impersonation.stop`, in `routes/web.php` under `auth` only. It cannot live in the admin group because the session now belongs to a non-admin.
2. Create `app/Http/Controllers/Admin/AdminImpersonationController.php`:
   - `start`: refuse an admin target or yourself; write the audit entry `user.view_as.started` with `AdminAuditService`; put `impersonator_id` and `impersonation_started_at` in the session; log in as the target on the `web` guard; redirect to `dashboard`.
   - `stop`: read `impersonator_id`, log back in as that user, forget both keys, audit `user.view_as.stopped`, redirect to `admin.users.show`.
3. Create `app/Http/Middleware/RestrictImpersonation.php` and append it to the web group in `bootstrap/app.php`. When the session has `impersonator_id`:
   - older than 30 minutes: run the stop logic;
   - any method other than GET or HEAD, except `impersonation.stop`: redirect back with an `error` flash ("Read-only while viewing as a user");
   - block `settings/security`, the passkey and two-factor routes and `data-export.download` outright.
4. GET requests that write must not fire while viewing. Known one: `DateNightPlanController@show` creates the "partner viewed your plan" notification; skip it when `impersonator_id` is in the session. Before release, run `grep -rn "::create\|->update(\|markAsRead" app/Http/Controllers` and check each hit that sits in a GET action.
5. `HandleInertiaRequests`: share `impersonating` (`{ name }` or null).
6. Banner: a fixed bar "Viewing as {name} (read-only) · Stop" in `MobileLayout.vue` and the settings layout, posting to `impersonation.stop`.
7. `resources/js/pages/admin/users/Show.vue`: a "View as user" button with a confirm dialog, shown only to super admins (`auth.can`) and not on admin accounts.
8. Update the privacy policy text.

**Tests** (`tests/Feature/Admin/AdminImpersonationTest.php`): a super admin starts and lands on the dashboard as the user; administrator, support and moderator roles get 403; an admin target is refused; a POST while viewing changes nothing; opening a plan creates no notification; stop restores the admin session; both audit rows exist; a session past 30 minutes is ended on the next request.

**Docs**: `admin.md`, `auth-settings.md` (shared props), `frontend.md`.

**Done when**: a super admin can see exactly what a user sees, cannot change anything, and the audit log shows who looked at whom and when.

---

**Sub-phase 5D was completed on 2026-11-25.** PHPUnit (575 tests), PHPStan, Pint, ESLint, Prettier, `vue-tsc` and the build pass. Nothing was clicked through by hand. As built:

- **5.10** Three email categories, not five: partner activity, love notes and moments. Reminders and challenges only ever notify in the app, so a toggle for them would have done nothing. `email_notifications` stays as the master switch. Reminder frequency (weekly, monthly, never) applies to the "plan a date night" reminder. Every notification email has a signed "Stop emails like this" link; it asks for confirmation first so that a mail scanner cannot unsubscribe anyone.
- **5.11** The recommended defaults: super administrators only, never an administrator account, read-only, 30 minutes, both ends audited, banner with Stop. Security pages, the data export and the weekly reflection are closed while viewing. The privacy policy has a new section describing this access; like the rest of that page it is an unreviewed draft. **The owner should confirm this access is acceptable before release.**
- **Deploy:** `php artisan migrate`.

---

## Sub-phase 5E: pause or end a connection

### - [x] 5.12 Pause or end a connection, choosing what each person keeps (L)

**Needs**: 5.9 (`connected_at`). Do this last: it touches every place that acts on the partner.

Today there are two blunt options. Disconnect deletes nothing and gives no choice. Deleting an account removes love notes for both people. This feature adds a pause, and an ending in which each person decides about their own copy of the shared history.

Work it in four stages. Stages 1 and 2 can ship without 3 and 4.

#### Stage 1: trace and decide (no code)

1. The audit never traced what an ex-partner sees after a disconnect (ROADMAP, "Not covered"). Do it now: with two test accounts, connect, create one of everything, disconnect, and write down what each person still sees on Dashboard, History, a plan page, Love Notes, Moments, Calendar, Timeline, Search, Insights and Weekly Reflection. Put the table in `onboarding-partner.md`.
2. Decide, with the product owner:
   - **What pause means.** Recommended: the two stay linked and keep their shared history, but nothing new passes between them: no notifications or emails about each other, no love notes, no couple questionnaires, no nudges, no shared challenge state.
   - **Who can resume.** Recommended: only the person who paused.
   - **Is the other person told.** Recommended: yes, once, with neutral wording.
   - **Couple streak during a pause (5.4).** Recommended: frozen, not reset.
   - **What can be chosen at the end.** Recommended, each person for their own side only: keep or hide shared date night plans; keep or hide the love notes. Moments and calendar events already stay with their owner.

#### Stage 2: pause (M)

1. Migration: add `connection_paused_at` (nullable datetime) and `connection_paused_by` (nullable FK to `users`, null on delete) to `users`. Written on both rows together.
2. `PartnerInvitationService`: add `pauseConnection(User $by)` and `resumeConnection(User $by)` (transaction, both rows; resume refuses anyone but `connection_paused_by`). `disconnectPartner()` also clears both columns.
3. `User`: add `isConnectionPaused(): bool` and `activePartner(): ?User` (the partner, or null while paused).
4. Replace `partner` with `activePartner()` in every path that **does something to** the partner. Find them with `grep -rn "->partner\b\|partner_id" app/Listeners app/Jobs app/Actions app/Console app/Services app/Http/Controllers`. Expected list:
   - `NotifyPartnerOnQuestionnaireCompleted`, `DispatchDateNightPlanOnQuestionnaireCompleted`, and the couple option in `StartQuestionnaireRequest`;
   - `SendMomentNotificationOnMomentCreated`, `NotifyPartnerOnChallengeCompleted`;
   - `LoveNoteController@store` (refuse with a clear message);
   - `LikeDateNightPlanAction`, `AddDateNightPlanToCalendarAction`, `DateNightPlanController@show` (partner-viewed notice);
   - `SendRemindersCommand`, `SendCalendarRemindersCommand` (owner only while paused);
   - 5.1 nudge action and command, 5.2 completion notice, 5.4 streak update, 5.9 anniversary command.

   Leave read paths alone (viewing shared plans, moments, calendar): pause does not hide history.
5. Add `NotificationType::ConnectionPaused` and `ConnectionResumed`, linking to `/partner`.
6. Routes: `POST partner/pause` (`partner.pause`) and `POST partner/resume` (`partner.resume`) in `PartnerController`.
7. `resources/js/pages/Partner.vue`: "Take a break" with a `ConfirmDialog` that lists what stops and what stays; a paused state showing who paused and when, with Resume for that person only. Send `paused` (`at`, `by_me`) in the page props.
8. Dashboard and `ChallengeCard`: show a quiet "Connection paused" note in place of partner activity.

**Tests** (`tests/Feature/ConnectionPauseTest.php`): pausing sets both rows and notifies the other person once; while paused a love note is refused, a moment creates no partner notification or email, a finished couple questionnaire does not notify or start a plan, calendar reminders go to the owner only; shared plans are still viewable by both; only the pauser can resume; disconnecting while paused clears the pause.

#### Stage 3: end with choices (M)

1. Migrations:
   - create `date_night_plan_hidden` (`date_night_plan_id`, `user_id`, timestamps, unique pair);
   - add `hidden_for_sender_at` and `hidden_for_recipient_at` (nullable datetimes) to `love_notes`.
2. Apply the hiding where lists are built:
   - `DateNightPlan::scopeVisibleTo()` excludes plans the user has hidden, and `DateNightPlanPolicy::view` refuses them. Then check the places the dashboard doc says do **not** use that scope (hub, insights, search, timeline) and move them onto it;
   - `LoveNoteController` (`index`, `show`, `unreadCount`), `TimelineBuilderService`, `GlobalSearchService`, `InsightsService` counts and `ExportUserDataJob` skip notes hidden for that user.
3. Create `app/Actions/EndConnectionAction.php` with `execute(User $user, bool $keepPlans, bool $keepNotes)`: in one transaction, apply the two choices to **this user's side only**, call `disconnectPartner()`, dispatch `PartnerDisconnected`.
4. Create `app/Http/Requests/Partner/EndConnectionRequest.php`: `keep_plans` and `keep_notes` required booleans, plus `password` (`current_password`), as account deletion asks.
5. `PartnerController@disconnect` calls the new action. Keep the route name `partner.disconnect`.
6. The other person: the `partner_left` notification links to a new page `GET partner/after` (`partner.after`) that offers the same two choices for their side, posted to `POST partner/after` (`ApplyConnectionChoicesAction`, same hiding code, no disconnect). The page stays available afterwards from the Partner page.
7. `Partner.vue`: replace the disconnect dialog with a two-step one: choices (with counts: "14 date night plans", "62 love notes"), then confirmation with the password. Offer "Download my data first" linking to Settings.
8. Nothing is deleted from the database by these choices; hiding is per person. Say so in the privacy policy, and note that deleting the account still deletes love notes for both.

**Tests** (`tests/Feature/EndConnectionTest.php`): ending with "keep" hides nothing; ending with "hide plans" removes shared plans from my history, search, timeline and direct URL (403) while my ex still sees them; the same for notes; my ex can apply their own choices later; a wrong password is refused; solo plans of my own are never hidden.

#### Stage 4: tidy up (S)

1. `resources/js/components/DeleteUser.vue`: bring the wording in line with the new dialog, and link to "End connection" as the gentler option.
2. Admin disconnect (`AdminUserController@disconnect`, `AdminCoupleController@disconnect`) is unchanged: it clears a pause through `disconnectPartner()` and applies no choices. Confirm with a test.
3. Add both flows to the browser tests (`npm run test:e2e`).

**Docs**: `onboarding-partner.md` (flows, data model, the ex-partner table), `love-notes.md`, `date-night.md`, `moments-timeline.md`, `dashboard-insights-search.md`, `auth-settings.md`, `events-jobs.md`, `notifications-activity-mail.md`.

**Done when**: either person can pause without losing anything, and either can end the connection and decide what stays in their own account, without changing what the other person keeps.

---

**Sub-phase 5E was completed on 2026-11-25**, which completes Phase 5. PHPUnit (594 tests), PHPStan, Pint, ESLint, Prettier, `vue-tsc`, the build and the browser tests pass; the pause, resume and end flows are walked in a real browser by `tests/e2e/zz-connection.spec.ts`. As built, with the recommended answer taken for every Stage 1 decision:

- **Stage 1** The table of what an ex-partner still sees is in `docs/modules/onboarding-partner.md`. It was read from the code, not checked screen by screen in a browser.
- **Pause** Either partner can pause; only the person who paused can resume; the other is told once each way. The couple stays linked and keeps all shared history. Notifications and emails about each other, love notes, couple planning, sharing of new solo plans and the couple streak stop until it is resumed (the streak is frozen, not lost). **Not covered: the weekly reflection still describes both partners' week during a pause.**
- **End** One dialog with the two choices, a link to the data export and the password (not two separate steps). Choices hide shared plans and love notes from that person only; nothing is deleted and a choice can be reversed. The other person chooses from the notification or the Partner page. `users.former_partner_id` remembers who that was.
- **Hidden history** is left out of history, favourites, the plan page, love notes, dashboard, hub, insights, search and timeline. It is still counted by achievements, past weekly reflections and the data export of plans.
- **Tidy** The delete-account dialog points to pause and end; admin disconnect clears a pause and applies no choices.
- **Deploy:** `php artisan migrate`.

---

## Deploy notes for Phase 5

- Every feature except 5.5 adds a migration: `php artisan migrate`.
- 5.3 adds prompt versions: `php artisan datenight:sync-prompts`.
- 5.4 and 5.9 add achievements: re-run `AchievementSeeder`.
- 5.1 and 5.9 add scheduled commands; the scheduler cron from `docs/DEPLOY.md` must be running.
- 5.8 with the QR option adds an npm dependency; 5.11 and 5.12 change the privacy policy text.
