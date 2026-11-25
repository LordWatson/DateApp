# Date Night: audit and roadmap

> Audit date: 2026-11-24. Branch audited: `ui-premium`.

## How to read this

- Every item under Phases 0 to 4 was confirmed by reading the code at the cited `file:line`. **Nothing here was confirmed by running the app in a browser**, except where a line says so. Line numbers will drift; the file and the described code are the anchor.
- Sizes: **S** under an hour, **M** about half a day, **L** multi-day.
- Work the phases in order. Inside a phase, the steps are ordered so that earlier ones unblock later ones.
- Each item is a checkbox. Tick it when the "Done when" line is true and a test covers it where one is named.
- Phase 5 is new features. Those are suggestions, not defects.

| Phase | Theme | Items | Rough effort |
|---|---|---|---|
| 0 | Security and data loss | 12 | 3 to 4 days |
| 1 | Broken things users hit today | 27 | 4 to 5 days |
| 2 | Finish what is half-built | 16 | 2 to 3 weeks |
| 3 | Production readiness | 14 | 1 week |
| 4 | Remove dead code | 6 groups | 1 to 2 days |
| 5 | New features | 12 | pick and choose |

---

## Phase 0: security and data loss

Do these before anything else, and before any public launch. Each one either exposes a user's data, destroys it, or lets the wrong person do something.

- [x] **0.1 Stored XSS in search results** (S)
  - Problem: search highlights matches with `v-html` on unescaped text, and results include love notes the partner sent. A partner or ex-partner can send a note containing HTML that runs in the other person's browser.
  - Where: `resources/js/pages/search/Index.vue` (`highlight()` and the two `v-html` uses); `app/Services/GlobalSearchService.php:102`.
  - Steps: escape the text first, then wrap the match in `<mark>`. Add a test note containing `<img onerror>` and confirm it renders as text.
  - Done when: HTML in any searchable field shows as literal characters.

- [x] **0.2 Any admin role can make itself super admin** (M)
  - Problem: the gates `manage-users`, `manage-content`, `view-analytics` and `super-admin` are defined but never checked. Any admin can change any user's role (their own included), and delete or suspend anyone, including the last super admin.
  - Where: `app/Http/Controllers/Admin/AdminUserController.php:59-110`; `app/Providers/AppServiceProvider.php:37-41`; `routes/admin.php:21`.
  - Steps: 1) add `can:` middleware per route group in `routes/admin.php`. 2) Block changing your own role and deleting or suspending yourself. 3) Refuse to remove the last super admin. 4) Feature tests per role.
  - Done when: a support-role admin gets 403 on role changes and user deletion.

- [x] **0.3 Inviting while already partnered corrupts the couple** (S)
  - Problem: the invite endpoint has no partner check. On acceptance the sender's `partner_id` is overwritten while the old partner still points back, leaving a one-sided link.
  - Where: `app/Http/Requests/Onboarding/SendInvitationRequest.php`; `app/Services/PartnerInvitationService.php:36-47`.
  - Steps: reject in the request when `partner_id` is set; guard again inside `acceptInvitation` for both users.
  - Done when: a partnered user cannot send or accept an invitation, with a test.

- [x] **0.4 Invitation is accepted by a GET with no confirmation** (M)
  - Problem: opening the link while logged in connects the two accounts immediately. The invited email is never compared with the person accepting, and a mail scanner following the link can trigger it.
  - Where: `app/Http/Controllers/OnboardingController.php:48-79`.
  - Steps: 1) make the GET render a "Connect with {name}?" page. 2) Accept on POST. 3) Offer Decline, which marks the invitation declined and tells the sender.
  - Done when: nothing changes until the person presses Connect.

- [x] **0.5 Deleting an account silently destroys the partner's data** (M to L)
  - Problem: account deletion hard-deletes the user. Database cascades then remove their responses, every shared date night plan built on them, and love notes in both directions. The partner is not warned or told. Moment photo files are left on disk.
  - Where: `app/Http/Controllers/Settings/ProfileController.php:49-61`; migrations `2026_07_12_000005`, `2026_07_13_000002`, `2026_07_13_000003`; `resources/js/components/DeleteUser.vue`.
  - Steps: 1) decide the rule: keep the partner's copy of shared plans (null the response reference) or delete and say so. 2) Add `DeleteAccountAction` that removes files, notifies the partner and unlinks them. 3) Spell out in the dialog exactly what both people lose. 4) Test that the partner's own records survive.
  - Done when: the partner keeps what the rule says they keep, and is notified.

- [x] **0.6 Deleting a questionnaire in admin deletes every user's plans for it** (S)
  - Problem: one confirm click cascades through responses, answers and date night plans. Deleting a question removes its answers.
  - Where: `app/Http/Controllers/Admin/AdminQuestionnaireController.php:98-104`.
  - Steps: once a questionnaire has responses, archive it instead of deleting; same for questions with answers.
  - Done when: a questionnaire with responses cannot be hard-deleted from the UI.

- [x] **0.7 Suspended users can still sign in and use the app** (S)
  - Where: `is_suspended` is only read in `AdminUserController.php`.
  - Steps: reject in `Fortify::authenticateUsing`; add middleware that logs out a suspended session.
  - Done when: a suspended account is refused at login and on its next request.

- [x] **0.8 Email verification is on in config but never enforced** (S, plus M to test)
  - Problem: `User` does not implement `MustVerifyEmail`, so no verification email is sent and the `verified` middleware passes everyone. Anyone can register with someone else's address, and item 0.10 lets them send mail from it.
  - Where: `app/Models/User.php:77`; `config/fortify.php:166`.
  - Steps: implement the interface; walk the invite sign-up path to make sure it still lands somewhere sensible.

- [x] **0.9 Partner and user records are sent to the browser whole** (S)
  - Problem: the dashboard and partner page send the full partner row (email, date of birth, role, suspension fields). The partner page also sends the invitation token. `auth.user` shares the whole user row on every page.
  - Where: `app/Http/Controllers/DashboardController.php:67`; `PartnerController.php:28-55`; `app/Http/Middleware/HandleInertiaRequests.php:42-44`; `OnboardingController.php:30`.
  - Steps: map each to an explicit array (id, name, display name, avatar, plus only what the page uses).

- [x] **0.10 No rate limits on invites or on AI endpoints** (S)
  - Problem: invite and resend can send unlimited email to any address. Regenerating the weekly reflection, finishing a questionnaire and the admin AI preview each cost an AI call with no limit.
  - Where: `routes/web.php:36,100-101,145`; `routes/admin.php:127-129`; limiters in `app/Providers/FortifyServiceProvider.php:84-98`.
  - Steps: add named limiters (invites per user per day, AI calls per user per hour) and apply them.

- [x] **0.11 Seeder creates a super admin with the password "password"** (S)
  - Problem: `db:seed` on production creates a known-credential super admin on real-looking addresses, and fails on a second run.
  - Where: `database/seeders/DatabaseSeeder.php:19-67`.
  - Steps: move the two demo users into a local-only seeder; make the reference-data seeders the default and re-runnable.

- [x] **0.12 A moment can be linked to another couple's plan** (S)
  - Where: `app/Http/Requests/Moment/StoreMomentRequest.php:20` checks `exists` only.
  - Steps: scope the rule to plans the user can view.

**Phase 0 was completed on 2026-11-24** (tests in `tests/Feature/Security/`). Decisions made along the way:

- **0.2:** users and couples need `manage-users` to change anything (any admin can view users); content sections need `manage-content`; feature flags, system settings and AI settings need `super-admin`; analytics needs `view-analytics`; audit logs need `manage-users`. Only a super admin assigns roles, never their own. The admin nav hides sections a role cannot open.
- **0.4:** registering through an invitation link still connects the new account straight away; opening the link while logged in now asks first, with Connect and No thanks.
- **0.5:** the partner keeps shared date night plans (re-pointed at their own response and shown as their plan); love notes between the two are deleted for both, and the dialog says so. Keeping notes would need nullable sender and recipient columns.
- **0.6:** an answered questionnaire is archived on delete; an answered question refuses to delete.
- **0.8:** existing accounts with no `email_verified_at` are held at the verification screen until they verify.
- **0.10:** 10 invitations or resends per user per day; 20 AI-costing requests per user per hour (finishing a questionnaire, the admin AI test and preview). Weekly reflection regenerate is still unthrottled: see 2.16.
- **0.1 has no automated test**, because there is no JavaScript test runner yet (3.10).

---

## Phase 1: broken things users hit today

Mostly small fixes. Group A alone revives the whole admin panel.

### A. Dead buttons from the unconfigured `ziggy-js` helper

- [x] **1.1 Replace all 46 `route()` calls with Wayfinder helpers, then remove `ziggy-js`** (M)
  - Problem: 17 pages import `route` from `ziggy-js`, but the server package is not installed and no config is passed, so every call throws.
  - User-facing: deleting or favouriting a moment, and the Back button on a moment (`resources/js/pages/moments/Index.vue:119,123`; `moments/Show.vue:37,41,59`). Helpers are in `@/routes/moments`.
  - Admin pages that crash on render: `questionnaires/Create.vue` (3 calls), `questionnaires/Edit.vue` (7), `users/Show.vue` (3), `email-templates/Edit.vue` (3), `email-templates/Index.vue` (1).
  - Admin pages that render but whose actions throw: `users/Index.vue` (3), `couples/Index.vue` (2), `questionnaires/Index.vue` (3), `challenges/Index.vue` (5), `achievements/Index.vue` (3), `ai-prompts/Index.vue` (3), `feature-flags/Index.vue` (1), `settings/Index.vue` (1), `media/Index.vue` (2), `audit-logs/Index.vue` (1).
  - Steps: 1) fix the five moments calls first. 2) Go page by page through admin, importing from `@/routes/admin/<section>` and calling `.url`, as `users/Index.vue` already does for search. 3) Remove `ziggy-js` from `package.json`. 4) Click every admin action once.
  - Done when: `grep -r "ziggy-js" resources/js` returns nothing and every admin create, edit and delete works.

### B. Feedback and navigation

- [x] **1.2 Success and error messages never appear on app pages** (S)
  - Problem: controllers flash `with('success', ...)`, but only `Inertia::flash('toast')` is turned into a toast. "Moment captured", "Event created", "Love note sent" and the invite-flow errors are never shown.
  - Where: `resources/js/lib/flashToast.ts`; `resources/js/layouts/MobileLayout.vue`.
  - Steps: in `flashToast`, also toast `page.props.flash.success` and `.error` on navigation. Type `flash` in `types/global.d.ts`.
  - Done when: one fix makes messages appear on every `MobileLayout` page.

- [x] **1.3 Search always says "No results found" while typing** (S)
  - Where: `resources/js/pages/search/Index.vue`: `hasResults` is a plain constant evaluated once, combined with `preserveState: true`.
  - Steps: make it a `computed`.

- [x] **1.4 Search and Timeline have no entry point** (S)
  - Steps: add a search icon to the `MobileLayout` header and a Timeline row on the relationship hub.

- [x] **1.5 Deletes have no confirmation** (S)
  - Where: moments (`moments/Index.vue`, `moments/Show.vue`) and calendar (`calendar/Index.vue`, `calendar/Show.vue`).
  - Steps: add a confirm dialog using the `ui/dialog` kit; reuse it in both places.

- [x] **1.6 Some notifications do nothing when tapped, and one has no name** (S)
  - Problem: "partner viewed plan", "added to calendar", partner-connected and reminders only mark as read. The achievement notification reads "Achievement unlocked: !" because it reads `->title` and the model has `name`.
  - Where: `resources/js/pages/notifications/Index.vue:46-94`; `app/Listeners/NotifyOnAchievementUnlocked.php:18-19`.
  - Steps: add an `AchievementUnlocked` type, use `->name`, and add a link target for every notification type.

- [x] **1.7 "Partner viewed your plan" is sent on every page view** (S)
  - Where: `app/Http/Controllers/DateNightPlanController.php:38-40`.
  - Steps: `firstOrCreate` per plan and recipient.

### C. Questionnaire flow

- [x] **1.8 Opening a question after finishing wipes the current attempt** (S)
  - Problem: "View summary" then "Edit" silently creates a new empty in-progress response, which every page then treats as the current one. On Plan a Date Out the new attempt defaults to solo.
  - Where: `app/Http/Controllers/QuestionnaireController.php` (`question` and `answer` call `findOrCreateResponse`).
  - Steps: only create a response in `start` and `restart`; redirect to `show` when none is in progress. Add a test.

- [x] **1.9 Summary of a finished attempt still looks unfinished** (S)
  - Problem: it shows "Review and edit", Edit buttons (which trigger 1.8), a Complete button that does nothing, and asks for location again.
  - Where: `resources/js/pages/questionnaires/Summary.vue` (the `response_status` prop is sent but unused).
  - Steps: render a read-only state with a link to the plan.

- [x] **1.10 Completion screen misleads in three cases** (S)
  - Problem: a) no partner on a couple questionnaire shows "we've let your partner know" and no plan ever comes. b) Revisiting after the plan exists shows the waiting screen with no link to the plan. c) The "planning" screen polls forever with no exit if the worker is down or the job failed.
  - Where: `QuestionnaireController::complete`; `resources/js/pages/questionnaires/Complete.vue:57-114`; `app/Jobs/GenerateDateNightPlanJob.php` (`failed()` only logs).
  - Steps: 1) look up an existing plan in `complete` and redirect to it. 2) Add a no-partner state linking to `/partner`. 3) Stop polling after about 90 seconds with "still working, we'll notify you" and Home and History links. 4) In `failed()`, create a notification for the user.

- [x] **1.11 Couple questionnaires can be started and finished with no partner** (S to M)
  - Steps: on couple-only questionnaires show a "connect your partner" prompt in place of Start, using the same pattern as the mode chooser on `questionnaires/Show.vue`.

- [x] **1.12 "Use saved profile" does not start the questionnaire** (S)
  - Problem: the picker posts with Inertia's router to an endpoint that returns JSON, so the success handler never runs.
  - Where: `resources/js/pages/questionnaires/Show.vue` (`applyProfile`); `app/Http/Controllers/SavedProfileController.php:108`.
  - Steps: call it with `axios`, then start.

- [x] **1.13 A saved profile can be applied to the wrong questionnaire** (S)
  - Where: `app/Services/QuestionnaireService.php` (`applyProfile`).
  - Steps: skip answers whose question belongs to another questionnaire; filter the picker to matching profiles.

- [x] **1.14 Saving a profile gives no feedback and fails silently** (S)
  - Where: `resources/js/pages/questionnaires/Summary.vue:455-482`.

- [x] **1.15 A required slider cannot be accepted at its starting value** (S)
  - Where: `resources/js/components/questions/SliderQuestion.vue`: it shows the minimum but emits nothing until moved.
  - Steps: emit the initial value on mount when there is no answer.

- [x] **1.16 Declined location silently plans the date around Bristol** (S)
  - Where: `resources/js/pages/questionnaires/Summary.vue:94-226`.
  - Steps: leave location empty and ask for manual entry.

- [x] **1.17 `/questionnaires/x/question/abc` is a 500** (S)
  - Steps: add `->whereNumber('order')` to the two routes in `routes/web.php`.

- [x] **1.18 "Past results" lists solo attempts that cannot be opened** (S)
  - Where: `QuestionnaireController::buildPastAttempts`.
  - Steps: exclude solo plans, or link solo rows straight to the plan.

- [x] **1.19 Solo plans show "100% compatible" outside the plan page** (S)
  - Where: `app/Http/Presenters/DateNightPlanPresenter.php` (`summary()` has no `is_solo`); History, Favourites, Dashboard and the export view.
  - Steps: add `is_solo` to `summary()` and hide the score and second person when true.

### D. Moments, calendar, love notes

- [x] **1.20 Edit, Delete and the star show on the partner's moments, then 403** (S)
  - Where: `resources/js/pages/moments/Index.vue`: the controller sends `is_owner`, the page ignores it.

- [x] **1.21 Only the newest 12 moments are reachable** (S to M)
  - Where: `app/Http/Controllers/MomentController.php:26` paginates; the page renders no pager.

- [x] **1.22 Opening Love Notes marks everything read before it is seen** (S to M)
  - Where: `app/Http/Controllers/LoveNoteController.php:36` runs on the GET.
  - Steps: mark read when the Received tab opens; show an unread badge using the existing `love-notes.unread-count` route.

- [x] **1.23 Search and timeline leave out the partner's shared items and link to list pages** (S to M)
  - Where: `app/Services/GlobalSearchService.php`; `app/Services/TimelineBuilderService.php`.
  - Steps: include the partner's ids; link each result to its own page.

- [x] **1.24 Timeline order and dates are unreliable** (S)
  - Problem: future calendar events sit at the top of a history feed, and same-day order is arbitrary because mixed date formats are sorted as strings.
  - Steps: normalise to ISO timestamps and exclude future events.

### E. Dashboard, hub, achievements

- [x] **1.25 Seasonal questionnaires on the hub and questionnaire search are always empty** (S)
  - Where: `app/Http/Controllers/RelationshipHubController.php:66` and `GlobalSearchService.php:124` filter `status = 'published'`, which is not a status. Use `QuestionnaireStatus::Active`.

- [x] **1.26 The monthly stat on the dashboard is a lifetime count** (S)
  - Where: `app/Actions/UpdateStreakAction.php:28-30` only increments.
  - Steps: count this month's completed responses in the controller.

- [x] **1.27 Smaller correctness fixes** (S each)
  - Achievement percentage can exceed 100 and runs one query per unlocked achievement (`app/Services/AchievementService.php:86-94`).
  - Love-note and calendar achievements unlock late: add an evaluation listener to `LoveNoteSent` and dispatch `CalendarEventCreated`.
  - The questionnaire reminder creates a new notification every day for ever, typed as `PartnerCompleted` (`app/Console/Commands/SendRemindersCommand.php`): skip users reminded in the last 7 days and add a reminder type.
  - The invitation email subject is blank for senders without a display name (`app/Mail/PartnerInvitationMail.php:23`).
  - Pasting the emailed invite link into "paste link or token" 404s (`resources/js/pages/onboarding/Index.vue:109-111`): strip everything up to the last `/`.
  - The partner page says "Since {date}" using the partner's sign-up date, and "Invitation resent" even when there is none.
  - The partner-request email still says "your partner completed the questionnaire"; the in-app notification asks them to answer. Bring the email in line (`app/Mail/QuestionnaireCompletedMail.php`).
  - `QuestionnaireSeeder` fails on a second run and does not flag its intimacy content (`database/seeders/QuestionnaireSeeder.php:17-29`).

**Phase 1 was completed on 2026-11-24** (regression tests in `tests/Feature/Phase1FixesTest.php`; every page below was loaded in a headless browser with no script errors). Notes:

- **1.1:** all 46 calls now use the generated helpers (`import admin from '@/routes/admin'`, then `admin.users.suspend(id).url`), and `ziggy-js` is removed from `package.json`. Admin create, edit and delete actions were not clicked through one by one.
- **1.2:** session flashes are toasts on every page with `MobileLayout`. Partner and onboarding no longer draw their own banners.
- **1.8:** `question` and `answer` never create an attempt. Answering with nothing in progress returns 409.
- **1.10:** the completion screen redirects to the plan when one exists, has a no-partner state, and stops waiting after about 90 seconds. A failed plan job creates a `plan_failed` notification for both people.
- **1.11:** done in the interface only. The server still lets a single user start a couple-only questionnaire by posting directly.
- **1.16:** when the browser cannot give a position the manual entry opens; nothing is invented. A plan can still be requested with no location.
- **1.19:** solo plans send `compatibility_score: null`. The printable export still prints a score and two names for a solo plan: see 2.16.
- **1.22:** notes are marked read by `POST /love-notes/mark-read` when the Received tab opens, and the tab shows an unread count.
- **1.27:** all done except the one-query-per-unlocked-achievement on the achievements page, which is a performance item, not a visible bug.
- New notification types: `partner_connected`, `achievement_unlocked`, `questionnaire_reminder`, `plan_failed`. Older rows keep the type they were saved with.

**Still true after Phase 1:** a new couple can sign up, connect, answer, get a plan, save a moment, send a note and search. Uploading a moment photo, calendar reminders and completing the daily challenge are Phase 2.

---

## Phase 2: finish what is half-built

Each of these has real backend work behind it and a missing last step.

- [x] **2.1 Saved profiles page** (S to M)
  - Exists: store, apply, update and delete endpoints, a policy, the dashboard list and search results.
  - Missing: the page itself (`saved-profiles/Index` is rendered by the controller and does not exist, so `/saved-profiles` is a 500), any rename or delete UI, and clickable dashboard rows.
  - Steps: 1) build the page listing name, emoji, colour and answer count. 2) Rename and delete through `axios`. 3) Link it from the dashboard section.

- [x] **2.2 AI settings page in admin** (M)
  - Exists: settings, test connection, prompt preview, live preview and usage stats, all in `AdminAiSettingController`.
  - Missing: the page (`/admin/ai-settings` is a 500) and a nav entry.
  - Steps: form for provider, model, temperature, max tokens, timeout and retries; a Test connection button; a prompt preview by use case; usage cards. The JSON endpoints must use `axios`.

- [x] **2.3 Calendar reminders are saved and shown but never sent** (M)
  - Exists: the columns, validation, form and display.
  - Steps: 1) a scheduled command that finds due events. 2) Notify the owner and partner. 3) Set `reminder_sent`. 4) Add it to `bootstrap/app.php`.

- [x] **2.4 Daily challenges** (S for the daily pick, M to L for completion)
  - Exists: 100 seeded challenges, the `daily_challenges` table, a scheduled selection job, a `ChallengeCompleted` event and a notification type.
  - Problem: the dashboard picks a random challenge on every page load and ignores the daily row. There is no way to complete one.
  - Steps: 1) read today's `DailyChallenge`, creating it lazily. 2) Add a completion table and route. 3) "Done" on the card for each partner. 4) Dispatch `ChallengeCompleted` and wire challenge achievements.

- [x] **2.5 Moment photos** (M)
  - Exists: validation, storage and clean-up on replace and delete.
  - Missing: the file input. Edit also uses `PUT`, which cannot carry files, and the photo URL is built from the default disk while files are stored on `public`.
  - Steps: add the input; post edits with `_method=put`; use the same disk for storing and for the URL (see 3.4).

- [x] **2.6 Profile and notification settings** (M)
  - Problem: display name, avatar, timezone, gender and the email-notification flag can only be set in onboarding, and people who join from an invite skip that step entirely. Love-note and moment emails ignore the flag.
  - Where: `resources/js/pages/settings/Profile.vue` edits name and email only; `SendLoveNoteNotificationJob`, `SendMomentNotificationJob`.
  - Steps: 1) add the fields to settings, reusing `CompleteProfileRequest`. 2) Add notification toggles. 3) Check the flag in every mail job.

- [x] **2.7 Compatibility and partner-answers pages** (M)
  - Problem: nothing links to them, and they only work in the moment between both partners finishing and the plan being saved.
  - Steps: drive both from the plan's two response ids and link them from the plan page as "where you matched, where we compromised".

- [x] **2.8 Admin questionnaire editor** (M)
  - Problem: it cannot set `is_solo`, `is_intimacy` or `partner_optional`, cannot edit or reorder existing questions and options (the routes exist), and a duplicate title is a 500 because the slug is unique. Status and type are validated as plain strings.
  - Where: `AdminQuestionnaireController.php:46-89`; `resources/js/pages/admin/questionnaires/Edit.vue`.

- [x] **2.9 Plans without AI are evening-in plans, even for a date out** (M)
  - Problem: the rule-based fallback ignores the questionnaire, so "Plan a Date Out" gets "a simple but beautiful shared meal at home". Solo plans also say "for you both".
  - Where: `app/Services/DateNightGeneratorService.php` (`buildPlan` and the `select*` methods).
  - Steps: add a date-out branch driven by `questionnaire.is_solo` and the location label; add solo wording.

- [x] **2.10 Rule engine barely matches the seeded content** (L)
  - Problem: themes come from substring matches on raw answer values, which the seeded options do not contain, and `romance_high` averages every numeric answer including travel minutes.
  - Where: `app/Services/RuleEngineService.php`.
  - Steps: tag options with explicit plan signals in the seeders and score on those.

- [x] **2.11 Server-side answer validation** (M)
  - Problem: any value is accepted for any question, and an attempt can be finished with no answers. Required is enforced only in the browser.
  - Steps: validate per question type in `answer`; reject `finish` while required questions are unanswered.

- [x] **2.12 Data export** (M)
  - Exists: `ExportUserDataJob`, an `ExportRequested` event and a nightly clean-up command.
  - Missing: any way to trigger it. The job also leaves out plans, notes and calendar events.
  - Steps: "Download my data" in settings, a complete export, and a link by email. This is also needed for 3.6.

- [x] **2.13 Admin sections that save but change nothing** (M)
  - Problem: feature flags (including `registration` and `maintenance_mode`), system settings and email templates are stored and audited, and nothing reads them.
  - Steps: wire the few that matter (registration, maintenance, AI generation, invitation expiry) and remove the rest from the nav.

- [x] **2.14 Invitation lifecycle and disconnect** (M)
  - Missing: decline, revoke, an "expired" notice to the sender (the expiry command only counts), and a logged-out existing user who opens an invite loses it after logging in. Disconnecting tells the other partner nothing and says nothing about shared records.
  - Steps: consume the invite token after login as well as after registration; add revoke and decline; notify on `PartnerDisconnected`; state what happens to shared plans, notes and moments.

- [x] **2.15 Intimacy games** (M)
  - Problem: the first "Draw a card" always shows card 1, "Draw another" can repeat, nothing is remembered, and the `intimacy_game` prompt asks for a caption (copied from another template) with no caller.
  - Steps: shuffle without repeats; fix or remove the template.

- [x] **2.16 Smaller finishes** (S each)
  - Favourites are one shared flag while the button says "Save to favourites": make it per person, or label it shared.
  - "Export plan" is a print page missing local suggestions and location: add them and call the button "Print".
  - The history page never sends the `theme` filter the controller supports, and has no pagination.
  - `fallback_used` is stored on plans and reflections and never shown: offer "try again with AI" on a fallback plan.
  - Weekly reflection: throttle Regenerate, give a solo user solo wording, and guard against two rows when both partners open the page at the same instant.
  - The null AI provider throws for three use cases (`app/Services/AI/NullAIProvider.php`), so local runs without a key always record a fallback.
  - Prompt changes only reach an environment by reseeding, which overwrites admin edits at the same version: add a command that inserts missing prompt versions only.

**Phase 2 was completed on 2026-11-24.** No route renders a missing page any more. 118 feature tests were added; every new page was loaded in a headless browser with no script errors, but the interactive parts (dialogs, uploads, reordering, the card shuffle) were not exercised by hand.

To deploy it: `php artisan migrate`, then `php artisan datenight:sync-prompts`, then re-run `AchievementSeeder` and the four questionnaire seeders (they add `signals` to options). `SoloDatePlannerQuestionnaireSeeder` still deletes and recreates its questions, which removes existing answers to "Plan a Date Out".

Decisions made, for the product owner to confirm:

- **2.3 Reminders** are in-app only, whole-day (the event time appears only in the text), checked hourly, and timed by the owner's timezone.
- **2.4 Daily challenge** follows the UTC day and is the same for everyone; completion is per person, cannot be undone, and notifies the partner. Two achievements were added (first challenge, ten challenges).
- **2.5 Photos** stay on the `public` disk (see 3.4). Removing a photo takes effect on save without a separate confirmation.
- **2.6 Settings**: display name and gender are optional in settings although onboarding requires them. One email toggle; in-app notifications are always created.
- **2.7** comparison pages live under the plan (`date-night/{plan}/answers` and `/compatibility`) and do not exist for solo plans.
- **2.9** date-out plans get their own title and emoji (for example "Dinner Out") instead of an at-home theme.
- **2.10** options carry `signals` (`food`, `outdoors`, `movies`, `games`, `music`, `relaxing`, `adventure`, `romance`); keyword matching is only the fallback. Romance comes from a `romance` signal or a slider whose question mentions romance averaging 8 or more. The seeded tags are a judgement call worth skimming.
- **2.11** empty answers are accepted on autosave; required is enforced when finishing.
- **2.12** the export is one JSON file on the private disk, announced in-app (not by email), downloadable by its owner for 24 hours, one request an hour. Saved profiles and weekly reflections are not in it.
- **2.13** `registration`, `maintenance_mode`, `ai_generation` and `invitation_expiry_days` now take effect. Closing registration also blocks people arriving from an invitation link. Maintenance lets every admin role through. Email templates is out of the admin nav; other unused flags and settings are labelled "Not in use".
- **2.14** revoking notifies nobody; an expiry notice goes to the sender once; disconnecting deletes nothing and the dialog now says exactly what each person keeps.
- **2.16** favourites are per person (`date_night_plan_favourites`; the old shared flag was copied to everyone on the plan, then dropped). "Try again with AI" runs inside the request. `datenight:sync-prompts` adds missing prompt versions and never updates a row; it treats "same or higher version exists" as already present, so an admin's edit is never overwritten.
- `AI_PROVIDER=null` in `.env` now really selects the no-AI provider (Laravel read it as empty before).

---

## Phase 3: production readiness

- [x] **3.1 Write the deploy checklist and `.env.production.example`** (S)
  - Mail defaults to `log` with the sender `hello@example.com`, so password resets and invitations would go to a log file.
  - Queue workers must list all six queues: `--queue=default,notifications,emails,analytics,ai,exports`. Without it, notifications, emails and achievements never run.
  - The scheduler needs a cron entry; six commands depend on it.
  - `php artisan migrate` then the prompt seeder on every deploy.
- [x] **3.2 Trusted proxies and HTTPS** (S): `bootstrap/app.php` has no `trustProxies`, so behind a load balancer the login limiter keys on the proxy's address and emails may contain `http` links.
- [x] **3.3 Inertia error pages for 403, 404, 500 and 503** (S): today these render Laravel's default pages, inside a modal during in-app navigation.
- [x] **3.4 Uploads use the local public disk** (M): couple photos are readable by anyone with the URL, depend on `storage:link` (not linked locally), and vanish on hosts with ephemeral disks. Use a configurable disk and signed URLs for moments. Restrict admin media to images.
- [x] **3.5 Legal pages** (M): there is no privacy policy, terms or cookie notice, and no terms checkbox on sign-up.
- [x] **3.6 Consent for AI processing** (M): intimate answers, love notes and moment descriptions are sent to a third-party AI provider with no consent step. Add a per-user consent flag checked before any AI call, and say so in the privacy policy.
- [x] **3.7 Remove the starter-kit hook** (S): `composer require` and `composer update` run `install:features`, which can delete auth pages, tests and migrations. Remove the hook, `laravel/chisel`, `InstallFeaturesCommand` and the chisel files.
- [x] **3.8 Fix CI** (S): `lint.yml` runs fixers and throws the result away, so it always passes. `tests.yml` skips Pint, ESLint, Prettier and the type check, and tests PHP 8.3 and 8.4 for an 8.5 app. Switch to `composer lint:check`, `npm run lint:check`, `format:check` and `types:check`.
- [x] **3.9 PHPStan** (M): 10 errors in the AI layer alone when last run (`vendor/bin/phpstan analyse`). Most are relations typed as `Model`; fix the relation return types on `User`.
- [x] **3.10 Frontend tests** (M): there is no JavaScript test runner. Start with a handful of end-to-end tests for the journey in the Phase 1 "done when" line.
- [x] **3.11 Audit log gaps** (S): media updates, questionnaire reordering and AI test calls are not audited, and a deleted user's full row (email, date of birth) is kept in the audit log.
- [x] **3.12 `/health` is public and writes on every call** (S): throttle it or put it behind a token; keep `/up` public.
- [x] **3.13 Decide on server-side rendering** (S): it is enabled in `config/inertia.php` with no production process defined. Either add the build and process to the deploy, or switch it off.
- [x] **3.14 Housekeeping** (S each): pin `opcodesio/log-viewer` (currently `*`); the admin analytics "average compatibility" reads a column nothing writes and is always 0%, and its "active users" are sign-ups; the app runs in UTC, so "today", streak days and calendar defaults are wrong for users in other time zones; add a backup routine for the database and uploads; local development is slow to start because the project lives in OneDrive and is scanned by antivirus on every file read.

**Phase 3 is done when** a fresh environment can be brought up from the checklist alone, and CI fails on a real lint, type or test failure.

**Phase 3 was completed on 2026-11-25.** PHPUnit (492 tests), PHPStan (0 errors, from 542), Pint, ESLint, Prettier, `vue-tsc`, the build and the 17 browser tests all pass locally. The CI workflow itself has not run on GitHub yet. Decisions taken, for the owner to confirm:

- **3.1** `docs/DEPLOY.md` is the checklist and `.env.production.example` the settings. The first super administrator is made with a one-line tinker command; there is no command for it.
- **3.2** `TRUSTED_PROXIES` (`config/trustedproxy.php`). HTTPS links rely on that plus `APP_URL`; nothing forces the scheme.
- **3.3** One `Error.vue` for 403, 404, 500 and 503 (500 keeps the debug page while `APP_DEBUG` is on); a 419 goes back with a "page expired" message.
- **3.4** `UPLOADS_DISK` (default: the private local disk). Moment photos are served through the app to the owner and partner only. Admin media is images only and admin-only. Existing photos move with `php artisan datenight:move-uploads` (run once). S3 needs `league/flysystem-aws-s3-v3`, which is not installed. Avatars are emoji, not files.
- **3.5** `/privacy` and `/terms` are drafts written from the code, with a visible "not reviewed by a lawyer" banner; operator and contact come from `LEGAL_OPERATOR` and `LEGAL_CONTACT_EMAIL`. Sign-up requires the checkbox (`users.terms_accepted_at`); existing users are not asked. No cookie banner: only essential cookies are set. The policy also discloses Google Fonts and the OpenStreetMap place lookup. The terms say 18+, which nothing enforces.
- **3.6** AI needs each person's consent (`users.ai_consent_at` / `ai_consent_declined_at`), checked inside `AIService`; shared plans and reflections need both partners. **Existing users get no AI until they agree** on the dashboard card or in Settings. There is no onboarding step. The user factory defaults to consented.
- **3.7** `laravel/chisel`, its files, the command and the composer hook are gone.
- **3.8** One workflow on PHP 8.5 that runs the checking variants, PHPStan, PHPUnit and the browser tests.
- **3.9** Fixed by typing the relations, with no baseline or ignores. It also fixed two admin endpoints that returned a 500 when given an array.
- **3.10** Playwright with the installed Chrome (`npm run test:e2e`), against a throwaway database: sign-in, registration, the couple journey, a solo plan, and a page-load check of the main pages.
- **3.11** Media updates, reordering and AI test calls are audited; a deleted user's audit row keeps only id, role and dates, and old rows were scrubbed. `user.updated` rows still hold names and emails.
- **3.12** `/health` is limited to 10 calls a minute and, when `HEALTH_TOKEN` is set, needs it as a bearer token.
- **3.13** Server-side rendering is off (`INERTIA_SSR_ENABLED`).
- **3.14** `opcodesio/log-viewer` pinned. Average compatibility comes from couple plans; "active users" are users with an activity-log entry. "Today" follows each user's timezone for streaks (now evaluated hourly), the dashboard, calendar and moments; the daily challenge stays on the UTC day. Backups are documented in `docs/DEPLOY.md`, not automated. The OneDrive slowness is documented, not fixed.
- **Not on the list:** `composer update` and `npm audit fix` were run to clear security advisories (Laravel 13.19 to 13.44, Guzzle 7 to 8 among others). Three moderate npm advisories in dev tooling remain; they need a breaking upgrade.

---

## Phase 4: remove dead code

Do this after Phases 1 and 2, because several of these are things those phases wire up. Delete what is left.

- [x] **4.1 Events nobody dispatches**: `QuestionnaireStarted`, `QuestionAnswered`, `CompatibilityCalculated`, `SavedProfileCreated`, `SavedProfileApplied`, `NotificationCreated`, `PartnerInvitationCreated`, `ProfileCompleted`, `UserRegistered`. (`CalendarEventCreated`, `ChallengeCompleted` and `ExportRequested` are wired in Phases 1 and 2.)
- [x] **4.2 Jobs nobody dispatches**: `GenerateCompatibilityJob`, `EnhanceDateNightPlanWithAIJob`, `GenerateConversationPromptJob`, `GenerateRelationshipInsightJob`, `GenerateCompatibilitySummaryJob`, with their tests, unless a Phase 5 feature adopts them.
- [x] **4.3 Caching that only forgets**: `RefreshStatisticsCommand`, `RefreshRelationshipStatisticsJob` and the `RefreshDashboardOn*` and `InvalidateCacheOn*` listeners clear cache keys nothing writes. Delete them or add the caching.
- [x] **4.4 Unused backend classes**: the `*Resource` classes, `ResponsePolicy`, `config/datenight.php`, the legacy `App\Contracts\AIProvider` with its null provider and binding, `AiPrompt` and the `ai_prompts` table, `TimelineEntry` and the `timeline_entries` table, `ExpireInvitationsCommand` (it only counts), unused `NotificationService` methods, and `AIService::generateChallengeVariation`, `enhanceThemeDescription` and `generateMomentCaption`.
- [x] **4.5 Unused frontend**: the starter layouts under `layouts/app` and `layouts/auth`, `AppSidebar`, `AppHeader`, `NavMain`, `NavFooter`, `NavUser`, `AppShell`, `AppContent`, `AppSidebarHeader`, `AnimatedBackground`, `LoadingSpinner`, `PlaceholderPattern`, `ProfileCard`, `QuestionCard`, `CompatibilityCard`, and the unused "Instrument Sans" font in `vite.config.ts`.
- [x] **4.6 Duplicates**: the calendar event type list is written out three times while `CalendarEventType` sits unused; `AdminUserController@disconnect` duplicates `AdminCoupleController@disconnect`.
- Then update the module docs: every "Suspected issue" and "Dead code" line that this roadmap closes should be removed from `docs/modules/*.md`.

**Phase 4 was completed on 2026-11-26.** PHPUnit (491 tests), PHPStan, Pint, ESLint, Prettier, `vue-tsc`, the build and the 17 browser tests pass. Every item was checked against the code as it is now, not against this list:

- **4.1** Ten events deleted. `ExportRequested` went too: the export controller dispatches the job directly, so the event was never used. `CalendarEventCreated` and `ChallengeCompleted` are live and stay.
- **4.2** The five jobs are deleted with `SummariseCoupleAnswersAction`, six `AIUseCase` cases, their `AIService` methods and seeded prompts. The `ai` queue now has no jobs but stays in the worker list.
- **4.3** Deleted, not replaced: no caching was added. The 03:00 statistics command is off the schedule.
- **4.4** All nine `*Resource` classes, `ResponsePolicy`, `config/datenight.php`, the legacy AI contract, `AiPrompt` and `TimelineEntry` are gone. A migration drops the `ai_prompts` and `timeline_entries` tables and deletes the prompt rows of the removed AI features. `ExpireInvitationsCommand` stays: Phase 2 gave it a real job.
- **4.5** The starter layouts, sidebar and header components, six unused components, the UI kits only they used (avatar, breadcrumb, card, dropdown-menu, navigation-menu, separator, sheet, sidebar, skeleton, tooltip), the `sidebarOpen` prop and the "Instrument Sans" font are gone.
- **4.6** Calendar event types come from `CalendarEventType`; both admin disconnect routes call `PartnerInvitationService::disconnectPartner()`.
- **Left in place, doubtful:** `PartnerInvitationAccepted` (dispatched, no listeners), the `AchievementCategory` enum, the starter `inspire` command, `FeatureFlag::isEnabled()`, `SystemSetting::get/set`, `User::hasRole()`, `Role::toEnum()`, `DateNightPlanPolicy::update`, the `PartnerInvitation` state helpers, and the `CompatibilityReady` and `DailyChallenge` notification types.
- **Deploy:** `php artisan migrate`, then `php artisan optimize`. Queued `RefreshRelationshipStatisticsJob` entries from before the deploy will fail once as "class not found"; ignore them.

---

## Phase 5: new features

Ordered by how much they add for the effort. The first five reuse data and code that already exist.

**All 12 were built on 2026-11-25.** The build steps, what was built and what was left out are in [PHASE-5.md](PHASE-5.md). Not built: the QR code for 5.8 (needs an npm package to be approved).

| # | Feature | Why it earns its place | Builds on | Size |
|---|---|---|---|---|
| 5.1 | **Nudge your partner**: a reminder button on the waiting screen, and one automatic reminder after 24 hours | The couple flow stalls completely on the second partner | Notification and mail pipeline | S |
| 5.2 | **"We did it"** on a plan: rate the night and capture a moment from it | Closes the loop, and feeds the weekly reflection and future plans | `moments.date_night_plan_id`, which no UI sets | M |
| 5.3 | **Regenerate a plan with a steer**: "not quite right?" plus one line of direction | A plan is one-shot today, and fallback plans are generic | The generator and the plan page | M |
| 5.4 | **Shared daily challenge with a couple streak** | Finishes the challenge loop and gives a daily reason to open the app | Phase 2.4 | M |
| 5.5 | **"On this day"** card on the dashboard | Free value from moments and plans already stored | Moments, plans | S |
| 5.6 | **React or reply to a love note** | Notes are one-way; a heart is cheap and closes the loop | Love notes | S |
| 5.7 | **Recurring anniversaries and birthdays with a countdown** | The event types exist but every event is a single day | Calendar, Phase 2.3 reminders | M |
| 5.8 | **Invite by shareable link or QR code with a confirm screen** | Removes the dependence on email delivery | Phase 0.4 | M |
| 5.9 | **Connection anniversary**: store when the couple connected | Fixes "Since" and gives a yearly moment and achievement | Phase 2.14 | S |
| 5.10 | **Notification preferences**: per-type email on or off, reminder frequency | Needed for consent, and the columns exist | Phase 2.6 | M |
| 5.11 | **Admin "view as user"**, super admin only and audited | Support cannot currently see what a user sees | Phase 0.2 | M |
| 5.12 | **Pause or end a connection**, choosing what each person keeps | A humane version of disconnect and account deletion | Phases 0.5 and 2.14 | L |

---

## Not covered by this audit

- Nothing was exercised in a browser for this audit. Items marked as crashes from `ziggy-js` and Inertia receiving a JSON response are reasoned from the code and the libraries' documented behaviour.
- Password reset, two-factor and passkey flows were checked by route and configuration only, not end to end.
- The email Blade templates were not read beyond their link targets.
- What each screen shows an ex-partner after a disconnect was not traced.
- Race conditions (both partners finishing at the same instant creating two plans; two weekly reflections) look plausible from the code and were not proven.
- Seeded content beyond the first questionnaire's options was not checked against the rule engine.
- The four unused AI jobs' prompt inputs were not verified.
