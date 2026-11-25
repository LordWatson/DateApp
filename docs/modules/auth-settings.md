# Auth and Settings

> Last verified against code: 2026-11-25

## Purpose

Lets a person create an account, sign in (password, TOTP two-factor, or passkey), recover a password, and manage their own account under `/settings` (profile, notification emails, data export, password, 2FA, passkeys, light/dark appearance, account deletion). Almost all auth endpoints are provided by Laravel Fortify; the app only supplies the Inertia pages, two Fortify actions, rate limiters, and the settings controllers. Admin roles and gates are defined here but consumed by [admin.md](admin.md).

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Providers/FortifyServiceProvider.php` | Binds Fortify actions, maps Fortify views to `auth/*` Inertia pages, defines `login`, `two-factor`, `passkeys` rate limiters |
| `config/fortify.php` | Enabled features, `home` = `/dashboard`, passkey relying-party config |
| `app/Actions/Fortify/CreateNewUser.php` | Registration: validates, creates user, auto-accepts a pending partner invitation from the session |
| `app/Actions/Fortify/ResetUserPassword.php` | Password reset validation + save |
| `app/Concerns/PasswordValidationRules.php`, `app/Concerns/ProfileValidationRules.php` | Shared rule traits used by Fortify actions and settings requests |
| `routes/settings.php` | All `/settings/*` routes + `.well-known/passkey-endpoints` (required from `routes/web.php`) |
| `app/Http/Controllers/Settings/ProfileController.php` | Profile page, profile update (name, email, display name, avatar, timezone, gender), account deletion |
| `app/Http/Controllers/Settings/NotificationsController.php` | Notifications page and the `email_notifications` toggle |
| `app/Http/Controllers/Settings/DataExportController.php` | "Download my data": queue the export, download the file |
| `app/Jobs/ExportUserDataJob.php`, `app/Console/Commands/CleanTemporaryExportsCommand.php` | Build the export file; remove files older than 24 hours (nightly 02:00) |
| `app/Http/Controllers/Settings/SecurityController.php` | Security page (password, 2FA, passkeys props), password update |
| `app/Http/Requests/Settings/*` | `ProfileUpdateRequest`, `NotificationsUpdateRequest`, `ProfileDeleteRequest`, `PasswordUpdateRequest`, `TwoFactorAuthenticationRequest` |
| `app/Models/User.php` | `TwoFactorAuthenticatable`, `PasskeyAuthenticatable`, role helpers (`adminRole()`, `isAdmin()`, `isSuperAdmin()`, `hasRole()`) |
| `app/Enums/UserRole.php`, `app/Models/Role.php`, `database/seeders/RoleSeeder.php` | Role enum (capability methods), `roles` table model, seeder that creates one row per enum case |
| `app/Providers/AppServiceProvider.php` | Gates, `Password::defaults()`, `CarbonImmutable` dates |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared props: `auth.user`, `unreadNotificationsCount`, `flash.success/error`, `sidebarOpen`, `name` |
| `app/Http/Middleware/HandleAppearance.php` | Shares the `appearance` cookie value with the Blade root view |
| `app/Http/Middleware/EnsureUserIsAdmin.php` | `admin` alias; 403 unless `User::isAdmin()` |
| `bootstrap/app.php` | Middleware aliases (`onboarding`, `admin`), web middleware stack, unencrypted cookies `appearance` + `sidebar_state` |
| **Frontend** | |
| `resources/js/pages/auth/*.vue` | `Login`, `Register`, `ForgotPassword`, `ResetPassword`, `VerifyEmail`, `TwoFactorChallenge`, `ConfirmPassword` |
| `resources/js/pages/settings/*.vue` | `Profile`, `Notifications`, `Security`, `Appearance` |
| `resources/js/components/DeleteUser.vue` | Delete-account dialog (password confirm) |
| `resources/js/components/ManageTwoFactor.vue`, `TwoFactorSetupModal.vue`, `TwoFactorRecoveryCodes.vue`, `resources/js/composables/useTwoFactorAuth.ts` | 2FA enable/confirm/disable UI |
| `resources/js/components/ManagePasskeys.vue`, `PasskeyRegister.vue`, `PasskeyItem.vue`, `PasskeyVerify.vue` | Passkey management and passkey sign-in/confirm button |
| `resources/js/components/AppearanceTabs.vue`, `resources/js/composables/useAppearance.ts` | Light/dark/system switch (localStorage + cookie) |
| `resources/js/lib/flashToast.ts` | Shows `Inertia::flash('toast', ...)` payloads via vue-sonner |
| `resources/js/app.ts` | Layout resolver: `auth/*` -> `AuthLayout`, `settings/*` -> `[AppLayout, settings/Layout]` |
| `resources/js/types/auth.ts` | `User`, `Auth`, `Passkey` types |
| **Tests** | |
| `tests/Feature/Auth/*` | Login, registration, reset, verification, 2FA challenge, password confirm |
| `tests/Feature/Settings/ProfileUpdateTest.php`, `NotificationSettingsTest.php`, `DataExportTest.php`, `SecurityTest.php` | Settings pages |
| `tests/TestCase.php` | `skipUnlessFortifyHas()` helper used by the auth tests |

## Data model

`users` (auth-relevant columns only; partner/streak columns are in [onboarding-partner.md](onboarding-partner.md) and other docs):

| Column | Notes |
|---|---|
| `name`, `email` (unique), `password` | `password` cast `hashed`; never hash manually before assigning |
| `email_verified_at` | datetime; reset to `null` when email changes in settings |
| `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` | Fortify 2FA; first two are in `#[Hidden]` |
| `remember_token` | Hidden |
| `role_id` | nullable FK -> `roles.id`, `nullOnDelete`. `null` = ordinary user |
| `is_suspended`, `suspended_at`, `suspension_reason` | Written only by admin controllers (see Rules) |
| `email_notifications` | Written by `settings/notifications`; read by every notification mail job |
| `dark_mode`, `push_notifications` | Columns exist; nothing reads or writes them |

Other tables: `roles` (`name` unique = `UserRole` value, `label`, `description`), `passkeys` (`user_id` cascade, `name`, `credential_id` unique, `credential` json, `last_used_at`), `password_reset_tokens`, `sessions`.

`App\Enums\UserRole`: `super_administrator`, `administrator`, `support`, `moderator`, `user`. `isAdmin()` is true for the first four. `User::adminRole()` resolves the enum from `role->name` (returns `null` when no role).

Gates (`AppServiceProvider::registerGates`): `access-admin`, `manage-users` (super admin, admin), `manage-content` (super admin, admin, moderator), `view-analytics` (super admin, admin, support), `super-admin`, `viewLogViewer` (super admin only; used by opcodesio/log-viewer).

## Routes

App-defined (`routes/settings.php`):

| Name | Method + URI | Controller@method | Notes/middleware |
|---|---|---|---|
| - | GET `settings` | redirect -> `/settings/profile` | `auth` |
| `profile.edit` | GET `settings/profile` | `Settings\ProfileController@edit` | `auth` |
| `profile.update` | PATCH `settings/profile` | `Settings\ProfileController@update` | `auth` |
| `profile.destroy` | DELETE `settings/profile` | `Settings\ProfileController@destroy` | `auth`, `verified` |
| `security.edit` | GET `settings/security` | `Settings\SecurityController@edit` | `auth`, `verified`, `RequirePassword` (password confirmation) |
| `user-password.update` | PUT `settings/password` | `Settings\SecurityController@update` | `auth`, `verified`, `throttle:6,1` |
| `appearance.edit` | GET `settings/appearance` | `Route::inertia` -> `settings/Appearance` | `auth`, `verified` |
| `notification-settings.edit` / `notification-settings.update` | GET, PATCH `settings/notifications` | `Settings\NotificationsController@edit` / `@update` | `auth`, `verified` |
| `email-unsubscribe.show` / `email-unsubscribe.store` | GET, POST `email-preferences/{user}/{category}` | `Settings\EmailUnsubscribeController@show` / `@store` | `signed` only (no login): the "Stop emails like this" link in an email |
| `data-export.store` | POST `settings/data-export` | `Settings\DataExportController@store` | `auth`, `verified`; one an hour per user, enforced in the controller so the page gets a toast instead of a 429 |
| `data-export.download` | GET `settings/data-export` | `Settings\DataExportController@download` | `auth`, `verified`; serves only the signed-in user's own file, 404 when there is none or it is older than 24 hours |
| `well-known.passkeys` | GET `.well-known/passkey-endpoints` | closure (JSON `enroll`/`manage` -> `security.edit`) | public |

Fortify / passkeys (vendor-registered, `web` middleware; names matter because the frontend imports Wayfinder helpers such as `@/routes/login`):

| Name | Method + URI |
|---|---|
| `login` / `login.store` / `logout` | GET, POST `login`; POST `logout` |
| `register` / `register.store` | GET, POST `register` |
| `password.request` / `password.email` / `password.reset` / `password.update` | GET, POST `forgot-password`; GET `reset-password/{token}`; POST `reset-password` |
| `verification.notice` / `verification.verify` / `verification.send` | GET `email/verify`; GET `email/verify/{id}/{hash}`; POST `email/verification-notification` |
| `two-factor.login` / `two-factor.login.store` | GET, POST `two-factor-challenge` |
| `password.confirm` / `password.confirm.store` / `password.confirmation` | GET, POST `user/confirm-password`; GET `user/confirmed-password-status` |
| `two-factor.enable` / `two-factor.disable` / `two-factor.confirm` | POST, DELETE `user/two-factor-authentication`; POST `user/confirmed-two-factor-authentication` |
| `two-factor.qr-code` / `two-factor.secret-key` / `two-factor.recovery-codes` / `two-factor.regenerate-recovery-codes` | GET `user/two-factor-qr-code`; GET `user/two-factor-secret-key`; GET, POST `user/two-factor-recovery-codes` |
| `passkey.login-options` / `passkey.login` | GET `passkeys/login/options`; POST `passkeys/login` |
| `passkey.confirm-options` / `passkey.confirm` | GET `passkeys/confirm/options`; POST `passkeys/confirm` |
| `passkey.registration-options` / `passkey.store` / `passkey.destroy` | GET `user/passkeys/options`; POST `user/passkeys`; DELETE `user/passkeys/{passkey}` |

## Flows

**Registration**
1. GET `register` renders `auth/Register` with `passwordRules`.
2. POST `register` -> `CreateNewUser::create()`: validates `name`, `email` (unique), `password` (confirmed, `Password::default()`), and `terms` (`accepted`: the "I agree to the Terms and Privacy Policy" checkbox), creates the user with `name`, `email`, `password` and `terms_accepted_at` = now. Users from before the checkbox have a null `terms_accepted_at`.
3. `acceptPendingInvitation()` pulls `invitation_token` from the session; if it resolves to a valid invitation not sent by this user, it runs `AcceptPartnerInvitationAction` and sets `onboarding_completed = true` (details in [onboarding-partner.md](onboarding-partner.md)).
4. Fortify logs the user in and redirects to `/dashboard`; the `onboarding` middleware then sends users with `onboarding_completed = false` to `onboarding.index`.

**Login**
1. `auth/Login` posts to `login.store` (limiter `login`: 5/min per lowercased email + IP). `PasskeyVerify` offers passkey sign-in (limiter `passkeys`: 10/min).
2. If the user has confirmed 2FA, Fortify redirects to `two-factor.login` (`auth/TwoFactorChallenge`, code or recovery code; limiter `two-factor`: 5/min per `login.id`).
3. Success redirects to `/dashboard`.

**Password reset**: `auth/ForgotPassword` -> `password.email` -> emailed link -> `auth/ResetPassword` -> `password.update` -> `ResetUserPassword::reset()`.

**Profile update**: PATCH `settings/profile` validates `name` + `email` (unique ignoring self) and, when sent, `display_name` (max 50), `avatar` (max 255, an emoji from the picker), `timezone` (valid zone) and `gender` (enum); these four are optional here because people who join from an invitation never see the onboarding profile step; if `email` is dirty, `email_verified_at` is nulled; flashes a `toast` via `Inertia::flash`.

**Notification settings**: PATCH `settings/notifications` sets `email_notifications` (required boolean, the master switch: off means no notification emails at all) and, when sent, `emails` (a map of `NotificationCategory` value to boolean; unknown keys are rejected) and `reminder_frequency` (`weekly`, `monthly` or `off`). The last two are stored in `users.notification_preferences` (json: `{emails: {...}, reminder_frequency}`; null means all defaults). In-app notifications are unaffected by any of it.

- `App\Enums\NotificationCategory` lists only kinds that send an email: `partner_activity` (waiting for your answers, plan ready, partner connected), `love_notes`, `moments`. Reminders and challenges are in-app only, so they have no email toggle.
- Ask through the model: `User::wantsEmail($category)` (master switch on and the category not switched off), `setEmailPreference()`, `reminderFrequency()` (default `weekly`).
- **Unsubscribe from an email**: each notification email's footer includes `emails.partials.unsubscribe`, a signed link to `email-unsubscribe.show`. Opening it shows a confirmation page (`resources/views/unsubscribe.blade.php`, a plain Blade page) and changes nothing, because mail scanners follow links; the button posts to the same signed URL and switches that one category off.

**Data export**: POST `settings/data-export` dispatches `ExportUserDataJob` (queue `exports`). The job writes one JSON file per user, `exports/user-{id}.json`, to the private `local` disk (a new export replaces the old one) and creates a `data_export_ready` notification that links to the profile page, where a "Download file" link appears while the file exists and is under 24 hours old. The file holds the user's profile, their responses and answers, plans they took part in, their own moments (with private notes) and calendar events, love notes sent and received, achievements and notifications; never the partner's own moments, events, answers or solo plans. `datenight:clean-exports` deletes files past 24 hours.

**Password change**: PUT `settings/password` requires `current_password` and a confirmed new `password`.

**Security page**: GET `settings/security` requires a recent password confirmation (redirects to `password.confirm`, where a passkey can also confirm). Props: `canManageTwoFactor`, `canManagePasskeys`, `passkeys[]`, `passwordRules`, and when 2FA is enabled as a feature `twoFactorEnabled`, `requiresConfirmation`. `TwoFactorAuthenticationRequest::ensureStateIsValid()` clears half-finished 2FA setup state.

**Account deletion**: `DeleteUser.vue` -> DELETE `settings/profile` with `password` (`current_password` rule) -> logout, `$user->delete()` (hard delete), session invalidated, redirect `/`.

**Appearance**: `useAppearance` stores the choice in `localStorage` and in an `appearance` cookie; `HandleAppearance` passes the cookie to `resources/views/app.blade.php` so the `dark` class is set server-side.

## Rules and gotchas
- **AI consent** is three-state on `users`: `ai_consent_at` set = agreed, `ai_consent_declined_at` set = declined, both null = not asked yet (`User::aiConsent()` returns true / false / null; `hasAiConsent()` is what the AI layer checks; `recordAiConsent(bool)` writes it). `PUT settings/ai-consent` (`ai-consent.update`, `Settings\AiConsentController`, `AiConsentUpdateRequest`: `agreed` required boolean, `auth` only) is used by `components/AiConsentCard.vue`, shown on the dashboard until the user answers and always on Settings, Profile. The shared prop `auth.user.ai_consent` carries the state. New accounts start unasked. See `ai.md` for the gate.
- **Legal pages:** `GET /privacy` and `/terms` (`LegalController`, pages `legal/Privacy`, `legal/Terms`, shell `components/LegalDocument.vue`) are public, outside the auth and onboarding groups, and allowed through maintenance mode. Both are marked as an unreviewed draft. The operator name and contact email come from `config('app.legal.operator')` / `contact_email` (`LEGAL_OPERATOR`, `LEGAL_CONTACT_EMAIL`; placeholders until set). `components/LegalLinks.vue` links them from `GuestLayout` (all auth pages), About and the settings layout.
- **Email verification is enforced**: `User implements MustVerifyEmail`, so registration sends the verification email and `verified` routes redirect unverified users to the notice.
- **Account deletion** (`app/Actions/DeleteAccountAction.php`, used by settings and admin): shared date night plans are re-pointed at the partner's own response before the cascade so the partner keeps them, the partner is unlinked and notified (`NotificationType::PartnerLeft`), and the leaving user's moment photos are removed from the `public` disk. Love notes between the two are deleted for both by the database cascade.
- **`impersonating`** (shared prop): `{name}` while an administrator is viewing the app as this user, otherwise null. See `admin.md`.
- The delete-account dialog points to pausing or ending the connection on the Partner page as the gentler option. Deleting the account still deletes love notes for both people.
- **Shared props**: `auth.user` is `id`, `name`, `display_name`, `email`, `avatar`, `email_verified_at`, `timezone`, `partner_id`, `onboarding_completed`. Add a field to `HandleInertiaRequests` before reading it on a page.

- Password policy (`AppServiceProvider::configureDefaults`): in production min 12, mixed case, letters, numbers, symbols, uncompromised; in every other environment there are no extra rules (`Password::defaults` returns `null`).
- Fortify features enabled: registration, reset passwords, email verification, 2FA (`confirm` + `confirmPassword`), passkeys (`confirmPassword`). Emails are lowercased (`lowercase_usernames`).
- Auth tests call `skipUnlessFortifyHas()`; disabling a feature in `config/fortify.php` silently skips its tests.
- Two different flash mechanisms exist: settings controllers use `Inertia::flash('toast', [...])` (rendered by `flashToast.ts`); most other controllers use session `success` / `error`, exposed as the shared `flash` prop and rendered per page/layout.
- `HandleInertiaRequests` shares the whole `User` model as `auth.user` on every request (everything not in `#[Hidden]`, including `role_id`, `partner_id`, `is_suspended`, `suspension_reason`). Add fields to `#[Hidden]` or map explicitly if something must not reach the browser.
- `unreadNotificationsCount` runs a count query on every authenticated request.
- The cookies `appearance` and `sidebar_state` are excluded from encryption because JavaScript writes them.
- `resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder` are generated by Wayfinder and git-ignored; do not edit them.
- Role checks: `User::isAdmin()` is false for `role_id = null` and for the `user` role. The `admin` middleware uses `isAdmin()`; finer checks use the gates above.
- Deleting an account relies on DB foreign keys for cleanup (`passkeys` cascade, partner's `partner_id` set null). No event is fired and the partner is not notified.
- Suspected issue: email verification is not enforced. `App\Models\User` (line 77) does not implement `Illuminate\Contracts\Auth\MustVerifyEmail`, so the `verified` middleware lets unverified users through, no verification email is sent on registration, and `settings/Profile.vue` never shows the "unverified" notice (`mustVerifyEmail` is always `false`, `ProfileController.php:23`). The Fortify verification routes still work if called directly, which is why the tests pass.
- Suspected issue: suspension is not enforced at login. `is_suspended` is only read/written in `app/Http/Controllers/Admin/AdminUserController.php`; there is no check in `FortifyServiceProvider` or any middleware, so a suspended user can still sign in and use the app.
- Suspected issue: `profile.edit` / `profile.update` are behind `auth` only while the rest of settings needs `verified` (`routes/settings.php:8-13`); harmless today because `verified` is inert (see above).
- `push_notifications` and `dark_mode` have no UI (appearance is cookie/localStorage based; there is no push channel). `date_of_birth` can only be set in onboarding.
- A data export file is not removed when the account is deleted; the nightly clean-up removes it within 24 hours.
- Dead code: `User::hasRole()` (`app/Models/User.php:120`) and `Role::toEnum()` (`app/Models/Role.php:29`) have no callers in `app/`.

## Related modules

- [onboarding-partner.md](onboarding-partner.md) - what happens right after registration; invitation acceptance inside `CreateNewUser`.
- [admin.md](admin.md) - consumers of the roles, gates, `admin` middleware and suspension fields.
- [notifications-activity-mail.md](notifications-activity-mail.md) - `unreadNotificationsCount` shared prop.
- [frontend.md](frontend.md) - layouts, Wayfinder, toast handling.
- [platform.md](platform.md) - middleware stack, providers, config.

## Tests

- `tests/Feature/Auth/AuthenticationTest.php`, `RegistrationTest.php`, `PasswordResetTest.php`, `PasswordConfirmationTest.php`, `EmailVerificationTest.php`, `VerificationNotificationTest.php`, `TwoFactorChallengeTest.php`
- `tests/Feature/Settings/ProfileUpdateTest.php`, `SecurityTest.php`, `NotificationSettingsTest.php` (master switch, per-category emails, reminder frequency), `EmailUnsubscribeTest.php` (signed link, confirm first, one category only)

```
php artisan test tests/Feature/Auth tests/Feature/Settings
php artisan test --filter=SecurityTest
```

Tests use sqlite `:memory:`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array` (`phpunit.xml`). `UserFactory` defaults to a verified user with `onboarding_completed = true`; states: `unverified()`, `withTwoFactor()`.

## Change log

- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 0: `User` implements `MustVerifyEmail`; account deletion goes through `DeleteAccountAction`; suspended users are signed out; `auth.user` is an explicit field list.
- 2026-11-24 — Roadmap Phase 2 (2.6, 2.12): profile settings edit display name, avatar, timezone and gender; new Notifications section (`email_notifications`); "Download my data" export with an owner-only download.
- 2026-11-24 — Roadmap Phase 2 (2.13): feature flag `registration` off makes `CreateNewUser` refuse with a validation error on `email`, and the shared prop `registrationOpen` hides sign-up on Welcome and Login (see `admin.md`).
- 2026-11-25 — Roadmap Phase 3 (3.14): `User::today()` gives the start of the current day in `users.timezone`; streaks, the dashboard month, calendar and moment defaults use it. Avatars are emoji strings, not files, so the uploads disk does not apply to them.
- 2026-11-25 — Roadmap Phase 3 (3.5, 3.6): registration requires the terms checkbox (`users.terms_accepted_at`); public `/privacy` and `/terms`; AI consent columns, `PUT settings/ai-consent` and the consent card. Tests: `LegalPagesTest`, `AI/AiConsentTest`.
- 2026-11-26 — Roadmap Phase 4: removed the unused `UserResource` (all of `app/Http/Resources` is gone).
- 2026-11-25 — Roadmap Phase 5.10: per-category email preferences and reminder frequency (`users.notification_preferences`, `NotificationCategory`, `User::wantsEmail()`), signed unsubscribe link in notification emails. Phase 5.11: shared prop `impersonating`.
- 2026-11-25 — Roadmap Phase 5.12: delete-account dialog mentions pause and end connection.
