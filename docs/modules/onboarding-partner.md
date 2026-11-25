# Onboarding and Partner Linking

> Last verified against code: 2026-11-24

## Purpose

After registering, a user walks through a short wizard: fill in a profile (display name, gender, avatar, timezone), then invite a partner by email, enter an invite token, or skip. Two accounts become a couple when one accepts the other's emailed invitation link. The Partner page lets a user invite, resend, revoke, or disconnect later.

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/OnboardingController.php` | Wizard page, profile step, invite step, invite-link acceptance, completion |
| `app/Http/Controllers/PartnerController.php` | Partner page, send/resend/revoke invite, disconnect |
| `app/Http/Middleware/RedirectToPendingInvitation.php` | Web middleware: after login, sends a user whose session holds `invitation_token` to the invitation page, once |
| `app/Listeners/NotifyFormerPartnerOnPartnerDisconnected.php` | Tells the other partner about a disconnect |
| `app/Http/Middleware/EnsureOnboardingComplete.php` | `onboarding` alias; redirects to `onboarding.index` while `onboarding_completed` is false |
| `app/Http/Requests/Onboarding/CompleteProfileRequest.php`, `SendInvitationRequest.php` | Validation (the invite request is shared by both controllers) |
| `app/Models/PartnerInvitation.php` | Invitation model (`isExpired()`, `isAccepted()`, `isPending()`) |
| `app/Models/User.php` | `partner()`, `sentInvitations()`, `pendingInvitation()` |
| `app/Services/PartnerInvitationService.php` | Create / find valid / accept / disconnect (the only place the pairing is written, apart from admin) |
| `app/Actions/SendPartnerInvitationAction.php` | Create invitation + dispatch `SendInvitationEmailJob` |
| `app/Actions/AcceptPartnerInvitationAction.php` | Accept + dispatch `PartnerInvitationAccepted` and `PartnerConnected` |
| `app/Actions/Fortify/CreateNewUser.php` | Accepts a session-stored invitation during registration |
| `app/Events/PartnerConnected.php`, `PartnerDisconnected.php`, `PartnerInvitationAccepted.php`, `PartnerInvitationCreated.php` | Events |
| `app/Listeners/*OnPartnerConnected.php` | Five listeners, see Flows |
| `app/Jobs/SendInvitationEmailJob.php`, `SendPartnerConnectedEmailJob.php` | Mail jobs (queue `emails`, 3 tries, 30s timeout) |
| `app/Mail/PartnerInvitationMail.php`, `PartnerConnectedMail.php` | Mailables; views `resources/views/emails/partner-invitation.blade.php`, `partner-connected.blade.php` |
| `app/Console/Commands/ExpireInvitationsCommand.php` | `datenight:expire-invitations`, scheduled daily 00:10 in `bootstrap/app.php`; tells each sender once that their invitation ran out |
| `app/Http/Resources/PartnerResource.php` | Slim partner payload (currently unused) |
| `app/Providers/EventServiceProvider.php` | Explicit listener map (event discovery is off) |
| **Frontend** | |
| `resources/js/pages/onboarding/Index.vue` | 4-step wizard (client-side step state) |
| `resources/js/pages/onboarding/InvalidInvite.vue` | Shown for unknown/expired/used tokens |
| `resources/js/pages/Partner.vue` | Connected / pending / empty states (uses `MobileLayout`) |
| **Tests** | |
| `tests/Feature/OnboardingTest.php`, `PartnerInvitationTest.php`, `PartnerRelationshipTest.php`, `tests/Feature/Events/PartnerConnectedEventTest.php` | |

## Data model

**How two users are partners:** there is no couples/pivot table. Each `users` row has a nullable self-referencing `partner_id`. A couple is two rows that point at each other (`A.partner_id = B.id` and `B.partner_id = A.id`). Both sides must always be written together; `PartnerInvitationService::acceptInvitation()` and `disconnectPartner()` do this in a DB transaction.

`users` columns used here:

| Column | Notes |
|---|---|
| `partner_id` | nullable FK -> `users.id`, `nullOnDelete` (deleting a user un-partners the other side) |
| `onboarding_completed` | boolean, default `false`; gate for every route under the `onboarding` middleware |
| `display_name` | nullable; set in the profile step (max 50) |
| `gender` | cast `App\Enums\Gender`: `male`, `female`, `other`, `prefer_not_to_say` |
| `date_of_birth`, `timezone` (default `UTC`), `avatar` (string; the wizard stores an emoji) | profile step |
| `email_notifications` | checked by `SendPartnerConnectedEmailJob` |

`partner_invitations`:

| Column | Notes |
|---|---|
| `sender_id` | FK -> `users.id`, cascade on delete |
| `email` | address the invite was sent to (not checked on acceptance) |
| `token` | 64 random chars (`Str::random(64)`), unique, stored in plain text |
| `accepted_at` | nullable datetime |
| `expires_at` | datetime, created as now + the `invitation_expiry_days` system setting (default 7) |

There is no status column: pending = `accepted_at IS NULL AND expires_at > now()`; revoked invitations are simply given `expires_at = now()`.

**"Solo" users:** there is no solo flag on users. A user with `partner_id = null` is just unpartnered and can use the app after choosing "Skip for now". `is_solo` exists on `questionnaires` and `date_night_plans` and is a property of the questionnaire/plan, not the user; see [questionnaires.md](questionnaires.md) and [date-night.md](date-night.md).

## Routes

| Name | Method + URI | Controller@method | Notes/middleware |
|---|---|---|---|
| `onboarding.accept-invite` | GET `invite/{token}` | `OnboardingController@acceptInvite` | Public (no `auth`); state-changing GET |
| `onboarding.index` | GET `onboarding` | `OnboardingController@index` | `auth`, `verified`; redirects to `dashboard` if already completed |
| `onboarding.complete-profile` | POST `onboarding/profile` | `OnboardingController@completeProfile` | `auth`, `verified` |
| `onboarding.send-invite` | POST `onboarding/invite` | `OnboardingController@sendInvite` | `auth`, `verified` |
| `onboarding.complete` | POST `onboarding/complete` | `OnboardingController@complete` | `auth`, `verified` |
| `partner.index` | GET `partner` | `PartnerController@index` | `auth`, `verified`, `onboarding` |
| `partner.send-invite` | POST `partner/invite` | `PartnerController@sendInvite` | same |
| `partner.resend-invite` | POST `partner/resend` | `PartnerController@resendInvite` | same |
| `partner.revoke-invite` | DELETE `partner/invite` | `PartnerController@revokeInvite` | same |
| `partner.disconnect` | DELETE `partner/disconnect` | `PartnerController@disconnect` | same |

## Flows

**Onboarding wizard** (`onboarding/Index.vue`; step is client state, initial step = 4 if `onboarding_completed`, 3 if `display_name` is set, else 2; step 1 is a welcome screen reachable only via the back button)
1. Step 2: POST `onboarding.complete-profile` (`display_name` required max 50, `gender` required enum, `date_of_birth` nullable before today, `timezone` required valid timezone, `avatar` nullable max 500) -> updates the user -> step 3.
2. Step 3, one of:
   - "Invite My Partner": POST `onboarding.send-invite` with `email` -> Send invitation flow -> step 4.
   - "I Have an Invite": browser navigates to `/invite/{token}` -> Accept flow.
   - "Skip for now": step 4 with no server call.
3. Step 4: POST `onboarding.complete` sets `onboarding_completed = true` -> `dashboard`.

**Send invitation** (`SendPartnerInvitationAction`, used by onboarding and Partner page)
1. `SendInvitationRequest`: `email` required, valid, max 255, must differ from the sender's own email.
2. `PartnerInvitationService::createInvitation()` expires all of the sender's still-pending invitations (`expires_at = now()`), then creates a new one valid for 7 days. A sender therefore has at most one pending invitation.
3. `SendInvitationEmailJob` (queue `emails`) is dispatched directly; it skips sending if the invitation is accepted or expired by the time it runs, otherwise sends `PartnerInvitationMail` with a link to `onboarding.accept-invite`.
4. Resend (`partner.resend-invite`) re-dispatches the same job for the current pending invitation; token and expiry are unchanged.

**Accept invitation** (GET `invite/{token}`)
1. `findValidInvitation()` (token matches, not accepted, not expired); otherwise render `onboarding/InvalidInvite`.
2. Guest: token is stored in session key `invitation_token`, redirect to `register`. On registration `CreateNewUser::acceptPendingInvitation()` pulls the token, re-validates it, runs the accept action and sets `onboarding_completed = true`. Logging in instead (password, two-factor or passkey) keeps the token in the session; `RedirectToPendingInvitation` pulls it on the next page load and redirects to `invite/{token}`, where the confirmation page is shown.
3. Logged in with a partner already: redirect to `dashboard` with session `error`.
4. Logged in and is the sender: redirect to `onboarding.index` with session `error`.
5. Otherwise `AcceptPartnerInvitationAction::execute()`:
   - `PartnerInvitationService::acceptInvitation()` in one transaction: `accepted_at = now()`, `sender.partner_id = recipient.id`, `recipient.partner_id = sender.id`.
   - dispatches `PartnerInvitationAccepted` (no listeners) and `PartnerConnected(sender, recipient)`.
6. Recipient gets `onboarding_completed = true`; redirect to `onboarding.index`, which immediately redirects to `dashboard`.

**`PartnerConnected` listeners** (`EventServiceProvider`)

| Listener | Queue | Effect |
|---|---|---|
| `SendPartnerConnectedEmailsOnPartnerConnected` | `emails` | Dispatches `SendPartnerConnectedEmailJob` per user -> `PartnerConnectedMail` (skipped if `email_notifications` is false) |
| `CreatePartnerConnectedNotificationOnPartnerConnected` | `notifications` | One `AppNotification` per user, type `NotificationType::PartnerCompleted` |
| `RefreshDashboardOnPartnerConnected` | `analytics` | Dispatches `RefreshRelationshipStatisticsJob` per user |
| `LogActivityOnPartnerConnected` | sync | Two `ActivityLog` rows, action `partner_connected` |
| `InvalidateCacheOnPartnerConnected` | `default` | Forgets `dashboard:{id}` cache keys |

**Disconnect** (DELETE `partner/disconnect`)
1. `PartnerInvitationService::disconnectPartner()` nulls `partner_id` on both users in a transaction.
2. `PartnerDisconnected(user, formerPartner)` is dispatched if there was a partner; `NotifyFormerPartnerOnPartnerDisconnected` (queue `notifications`) creates a `partner_left` notification for the other partner.
3. Redirect to `partner.index` with session `success`. Either partner can do this alone, after a `ConfirmDialog` on the Partner page that states what each person keeps.
4. What stays after a disconnect (nothing is deleted or reassigned): date night plans made together stay in both histories (access is by response ownership); love notes stay with sender and recipient but no new ones can be sent; moments and calendar events stay with their owner and are no longer visible to the other person (visibility follows `partner_id`, so reconnecting brings them back); weekly reflections stay with whoever generated them. The accepted invitation row stays in place.

**Revoke** (DELETE `partner/invite`): closes the sender's pending invitation (`expires_at = now()`, same as a decline). The link stops working; nobody is notified.

**Expiry notice** (`datenight:expire-invitations`): finds invitations that ran out by themselves in the last week (`accepted_at` null, `expires_at` in the past, `updated_at < expires_at`), creates an `invitation_expired` notification for the sender unless they have since connected or invited again, then touches the row so it is never picked up twice. Revoked, declined and replaced invitations are excluded because closing them sets `expires_at` and `updated_at` together. Anything that edits an expired invitation row without meaning to announce it must keep this in mind.

## Rules and gotchas
- **Accepting an invitation takes two steps** when logged in: GET `invite/{token}` renders `onboarding/AcceptInvite`; POST `invite/{token}/accept` (`onboarding.confirm-invite`) connects; POST `invite/{token}/decline` expires the invitation and notifies the sender (`NotificationType::InvitationDeclined`). A guest is still sent to register, and registering through the link connects straight away.
- **No invitations for partnered users**: `SendInvitationRequest` rejects a sender who has a partner, `findValidInvitation` ignores invitations whose sender has since connected, and `acceptInvitation` throws `DomainException` if either side has a partner.
- Invite and resend are limited to 10 per user per day (`throttle:invites`).
- The Partner page receives the partner as `id`, `name`, `display_name`, `avatar` only, and no longer receives the invitation token. Onboarding receives an explicit list of the user's own fields.

- Always write both `partner_id` sides. Besides the service, only `Admin\AdminCoupleController` and `Admin\AdminUserController` write `partner_id` (see [admin.md](admin.md)); the admin "reconnect" path does not fire `PartnerConnected`.
- The invitation is a bearer token: `email` on the invitation is not compared with the accepting user's email, so anyone with the link can accept it.
- Acceptance happens on a GET request. A logged-in, unpartnered user who merely opens the link is connected immediately, with no confirmation screen.
- Accepting an invite marks onboarding complete and skips the profile step, so users who join via an invite can have `display_name = null`, no gender, and timezone `UTC`. Code must keep using the `display_name ?? name` fallback.
- `onboarding.complete` has no precondition; posting to it completes onboarding even if the profile step was never submitted.
- The onboarding routes are not behind the `onboarding` middleware and `completeProfile` / `sendInvite` remain callable after onboarding is complete.
- Queues: listeners and jobs here use the named queues `emails`, `notifications`, `analytics`, `default`; a worker must consume all of them (see [events-jobs.md](events-jobs.md)). `PartnerConnectedMail` also implements `ShouldQueue`, so the connected email goes listener -> job -> queued mailable. Under tests the queue is `sync`.
- `PartnerController@index` sends the pending invitation `token` and the full partner `User` model (including `email`) to the browser.
- Suspected issue: the sender is never checked for an existing partner. `partner.send-invite` / `onboarding.send-invite` work while connected, and `PartnerInvitationService::acceptInvitation()` (`app/Services/PartnerInvitationService.php:44`) overwrites `sender.partner_id`; the sender's previous partner keeps pointing at the sender, leaving a one-sided link. Only the recipient is guarded (`OnboardingController.php:64`).
- Suspected issue: `PartnerInvitationMail` subject uses `sender->display_name` without the `?? name` fallback (`app/Mail/PartnerInvitationMail.php:23`), producing a subject with an empty name for senders who have no display name; the body does use the fallback.
- Suspected issue: `PartnerController@index` sets `connectedSince` to the partner's `created_at` (`app/Http/Controllers/PartnerController.php:34-36`), i.e. when the partner registered, not when the couple connected. No connection timestamp is stored on users; the accepted invitation's `accepted_at` is the closest record.
- Suspected issue: the `dashboard:{id}` and `compatibility:{id}` cache keys forgotten by `InvalidateCacheOnPartnerConnected` and `RefreshRelationshipStatisticsJob` are never written anywhere in `app/`, so those two listeners currently have no effect.
- Suspected issue: session `error` flashes from `acceptInvite` are effectively never shown. `Dashboard.vue` and `MobileLayout.vue` do not render `flash`, and `onboarding/Index.vue` only renders `flashError` inside the "invite" sub-panel, which is closed on page load (`OnboardingController.php:65,70`).
- Suspected issue: the "I Have an Invite" step says "Paste the invitation link or token" but `submitJoin()` builds `/invite/${token}` from the raw input (`resources/js/pages/onboarding/Index.vue:73-75`), so pasting a full link produces a broken URL; only a bare token works.
- Suspected issue: `partner.resend-invite` always flashes "Invitation resent!" even when there is no pending invitation (`PartnerController.php:47-56`), and has no throttle.
- `avatar` is validated as `max:255` (the column length) in onboarding and settings. Only the emoji picker exists; the wizard's "upload" avatar mode was never built.
- Dead code: `PartnerInvitationCreated` is never dispatched and `SendInvitationEmailOnPartnerInvitationCreated` is not registered in `EventServiceProvider` (registering it without removing the direct dispatch in `SendPartnerInvitationAction` would send two emails). `PartnerInvitationAccepted` has no listeners; `UserRegistered` and `ProfileCompleted` are registered with no listeners and never dispatched. `PartnerResource` and `PartnerInvitation::isExpired()/isAccepted()/isPending()` have no callers in `app/`.

## Related modules

- [auth-settings.md](auth-settings.md) - registration (`CreateNewUser`), `verified` middleware behaviour, account deletion.
- [events-jobs.md](events-jobs.md) - full event/listener/queue map.
- [notifications-activity-mail.md](notifications-activity-mail.md) - `AppNotification`, `ActivityLogService`, mail templates.
- [dashboard-insights-search.md](dashboard-insights-search.md) - dashboard shows `partner` and `pendingInvitation`.
- [questionnaires.md](questionnaires.md), [date-night.md](date-night.md) - partner-dependent behaviour and `is_solo`.
- [admin.md](admin.md) - admin couple disconnect/reconnect.

## Tests

- `tests/Feature/OnboardingTest.php` - wizard access, profile validation, completion, guest redirect
- `tests/Feature/PartnerInvitationTest.php` - send, self-invite rejection, expiry, accept, reuse, own invite, disconnect, guest redirect with session token
- `tests/Feature/PartnerRelationshipTest.php` - `partner()` relation, `nullOnDelete`
- `tests/Feature/Events/PartnerConnectedEventTest.php` - event payload only (listeners are not exercised)

```
php artisan test --filter=OnboardingTest
php artisan test --filter=PartnerInvitationTest
php artisan test --filter=PartnerRelationshipTest
php artisan test --filter=PartnerConnectedEventTest
```

Not covered by tests: acceptance during registration (`CreateNewUser`), resend, the `PartnerConnected` listeners, inviting while already partnered. `UserFactory` defaults to `onboarding_completed = true` and `partner_id = null`; there is no `PartnerInvitation` factory (tests create rows directly).

## Change log

- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 0: invitations are confirmed by POST, can be declined, cannot be sent or used by someone already partnered, and are rate limited; partner props are trimmed.
- 2026-11-24 — Roadmap Phase 1: pasted invitation links work, "Since" uses the accepted invitation date, resend reports when there is nothing to resend, invitation email subject falls back to the name.
- 2026-11-24 — Roadmap Phase 2 (2.14): an invitation opened while logged out survives logging in; senders can revoke and are told once when an invitation expires; the other partner is notified of a disconnect and the confirmation says what each person keeps; avatar limit matches the column.
- 2026-11-24 — Roadmap Phase 2 (2.13): invitation lifetime comes from the system setting `invitation_expiry_days` (default 7, minimum 1) through `RuntimeSettings`.
