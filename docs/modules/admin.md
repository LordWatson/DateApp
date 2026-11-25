# Admin panel
> Last verified against code: 2026-11-25

## Purpose
Back-office under `/admin` for staff roles: user/couple management, content CRUD (questionnaires, challenges, achievements, AI prompts), runtime config (feature flags, system settings, AI settings), media uploads, audit trail, analytics. Plus the third-party log viewer at `/log-viewer`.

## Key files
| Path | Role |
|---|---|
| **Backend** | |
| `routes/admin.php` | All admin routes. Loaded as a second `web` route file in `bootstrap/app.php` (`web: [web.php, admin.php]`), so it gets the `web` middleware group |
| `app/Http/Middleware/EnsureUserIsAdmin.php` | Alias `admin` (registered in `bootstrap/app.php`). `abort(403)` unless `$user->isAdmin()` |
| `app/Providers/AppServiceProvider.php` (`registerGates`) | Gate definitions (see Rules) |
| `app/Enums/UserRole.php` | Role names + capability methods (`isAdmin`, `canManageUsers`, `canManageContent`, `canViewAnalytics`, `isSuperAdmin`) |
| `app/Models/Role.php`, `database/seeders/RoleSeeder.php`, `database/factories/RoleFactory.php` | `roles` table rows mirror the enum (`name` = enum value). Factory states: default administrator, `superAdmin()`, `support()`, `moderator()` |
| `app/Models/User.php` | `role()`, `adminRole(): ?UserRole`, `isAdmin()`, `isSuperAdmin()`, `hasRole()`; columns `role_id`, `is_suspended`, `suspended_at`, `suspension_reason` |
| `app/Http/Controllers/Admin/*` | One controller per section, all extend the empty `AdminController` |
| `app/Http/Requests/Admin/*` | `StoreAiPromptTemplateRequest`, `UpdateAiPromptTemplateRequest`, `UpdateAiSettingsRequest` — the only FormRequests; all other controllers validate inline |
| `app/Services/RuntimeSettings.php` | The one reader of feature flags and system settings for the rest of the app (see Rules) |
| `app/Http/Middleware/EnsureNotInMaintenance.php`, `resources/js/pages/Maintenance.vue` | Maintenance mode (flag `maintenance_mode`) |
| `app/Services/AdminAuditService.php`, `app/Models/AdminAuditLog.php` | Audit trail writer + model |
| `app/Models/FeatureFlag.php`, `SystemSetting.php`, `EmailTemplate.php`, `MediaLibrary.php` | Admin-owned config/content models |
| `database/seeders/FeatureFlagSeeder.php`, `SystemSettingSeeder.php` | Seed data (idempotent `updateOrCreate` by `key`) |
| `config/log-viewer.php` | opcodesio/log-viewer config |
| **Frontend** | |
| `resources/js/layouts/AdminLayout.vue` | Sidebar shell; `navItems` array is the admin menu; renders `flash.success` / `flash.error` |
| `resources/js/components/admin/AdminTable.vue`, `AdminPagination.vue` | Table shell (`columns`, `empty`) and Laravel paginator `links` renderer |
| `resources/js/components/admin/QuestionForm.vue`, `OptionForm.vue`, `QuestionnaireModeFields.vue` | Questionnaire editor parts: create/edit form for a question and for an option (each owns its `useForm` and emits `done`), and the solo / partner optional / intimacy checkboxes |
| `resources/js/pages/admin/**` | `Dashboard`, `users/{Index,Show}`, `couples/Index`, `questionnaires/{Index,Create,Edit}`, `challenges/Index`, `achievements/Index`, `feature-flags/Index`, `settings/Index`, `email-templates/{Index,Edit}`, `ai-prompts/Index`, `ai-settings/Index`, `media/Index`, `analytics/Index`, `audit-logs/Index` |
| **Tests** | |
| `tests/Feature/Admin/*` | `AdminAuthorizationTest`, `AdminChallengeTest`, `AdminFeatureFlagTest`, `AdminUserManagementTest`, `LogViewerAccessTest`, `AdminAiSettingsPageTest`, `AdminQuestionnaireEditorTest` |
| `tests/Feature/RuntimeSettingsTest.php` | Each wired flag on, off and missing; invitation expiry setting |

## Data model
| Table (migration `2026_07_12_1615xx_*`) | Model | Notes |
|---|---|---|
| `roles` | `Role` | `name`, `label`, `description`. `users.role_id` nullable; null = regular user |
| `admin_audit_logs` | `AdminAuditLog` | `user_id`, `action` (dot string, e.g. `challenge.updated`), morph `auditable_type/id`, JSON `before`/`after`, `ip_address`, `user_agent` |
| `feature_flags` | `FeatureFlag` | `key`, `label`, `description`, `enabled`, `group`. `FeatureFlag::isEnabled($key)` (false when missing) |
| `system_settings` | `SystemSetting` | `key`, `label`, `value` (string), `type` (`string/integer/boolean/json/color`), `group`. `get()`, `set()`, `typedValue()` |
| `email_templates` | `EmailTemplate` | `key`, `subject`, `html_body`, `text_body`, `variables` (array), `active` |
| `media_library` | `MediaLibrary` | file metadata + `uploaded_by` + `disk`; new files go to the uploads disk (`config('filesystems.uploads')`); the `url` attribute is always the `admin.media.show` route (the stored column is unused) |

Seeded feature flags: `ai_generation`, `calendar`, `love_notes`, `achievements`, `moments`, `seasonal_content`, `insights` (group `features`), `beta_features` (`beta`, off), `registration`, `maintenance_mode` (`system`).
Seeded system settings: `app_name`, `app_tagline`, `support_email`, `mail_from_name`, `mail_from_address`, `invitation_expiry_days`, `compatibility_high_threshold`, `compatibility_low_threshold`, `challenge_frequency_days`, `color_primary`, `color_secondary`, `color_background`. `AISettingsService` additionally writes `ai.*` keys (group `ai`) — see `ai.md`.

## Routes
All: middleware `auth`, `verified`, `admin`; URI prefix `/admin`; name prefix `admin.`.

| Controller | Routes (name suffix: method URI) | Manages | Audit |
|---|---|---|---|
| `AdminDashboardController` | `dashboard`: GET `/` | Counts (users, couples, responses, plans, love notes, `jobs`/`failed_jobs`), newest users, 30-day series | – |
| `AdminUserController` | `users.index/show/update/suspend/unsuspend/disconnect/destroy` | Search/filter users, edit name/email/`role_id`, suspend, unlink partner, delete | yes |
| `AdminImpersonationController` | `users.view-as` POST `/users/{user}/view-as` (`can:super-admin`); `impersonation.stop` POST `/stop-viewing` (outside the admin group, `auth` only) | View the app as a user | yes (`user.view_as.started` / `.stopped`) |
| `AdminCoupleController` | `couples.index`, `couples.disconnect` POST `/{user}/disconnect`, `couples.reconnect` POST `/reconnect` | Couples list (one row per pair via `id < partner_id`), link/unlink | yes |
| `AdminQuestionnaireController` | `questionnaires.index/create/store/edit/update/destroy/duplicate/reorder` | Questionnaire CRUD incl. `is_solo`, `partner_optional`, `is_intimacy`; deep duplicate (questions + options, status `draft`); `reorder` saves `display_order` for questions, or for options with `type=options` | yes (reorder logs `questions.reordered` / `options.reordered` with the new order) |
| `AdminQuestionController` | `questions.store` POST `/questionnaires/{questionnaire}/questions`, `questions.update/destroy` `/questions/{question}` | Questions (edited inside questionnaire Edit page) | yes |
| `AdminOptionController` | `options.store` POST `/questions/{question}/options`, `options.update/destroy/duplicate` `/options/{option}` | Question options, incl. `signals` (a list from `RuleEngineService::SIGNALS`) | yes |
| `AdminChallengeController` | `challenges.index/store/update/destroy/archive/bulk-import` | Challenges; archive sets `archived=true, active=false`; index hides archived unless `?archived=1` | yes |
| `AdminAchievementController` | `achievements.index/store/update/destroy` | Achievements | yes |
| `AdminFeatureFlagController` | `feature-flags.index/store/update/destroy` | Flags grouped by `group`; update only toggles `enabled` | yes |
| `AdminSystemSettingController` | `settings.index/update`, `settings.bulk-update` POST `/settings/bulk` | Settings grouped by `group`; value only | yes |
| `AdminEmailTemplateController` | `email-templates.index/edit/update` | Edit existing templates only (no create/delete) | yes |
| `AdminAiPromptController` | `ai-prompts.index/store/update/destroy` | `AiPromptTemplate`; update bumps `version` | yes |
| `AdminAiSettingController` | `ai-settings.index/update`, `test-connection` (POST, JSON), `preview-prompt` (GET, JSON), `preview-response` (POST, JSON) | Runtime AI settings via `AISettingsService`, provider probe, usage snapshot from `AiGeneration` | update, `ai.connection.tested`, `ai.response.previewed` (use case and context keys only, never the context values) |
| `AdminMediaController` | `media.index/store/show/update/destroy` | Images only (`image` + `mimes:jpg,jpeg,png,gif,webp`, max 10 MB, extension taken from the content) to the uploads disk under `folder` (default `uploads`); `show` streams the file to admins | upload/update/delete |
| `AdminAuditLogController` | `audit-logs.index` | Read-only, 50/page, filters `search`, `action`, `user_id` | – |
| `AdminAnalyticsController` | `analytics.index` | Active users (`active_users` last 30 days, `dau` per day, `mau` per month for 12 months), completion/love-note/moment series, question drop-off, avg compatibility | – |

Gate column intentionally absent: **no controller checks a gate** (see Rules). Log viewer: `/log-viewer` (package routes), middleware `web`, `auth`, `AuthorizeLogViewer` -> gate `viewLogViewer`.

## Flows
- **Access**: `auth` -> `verified` -> `admin` middleware (`User::isAdmin()` = role is any of super_administrator / administrator / support / moderator). Role `user` and `role_id = null` are both non-admin.
- **Mutation pattern**: inline `$request->validate()` -> capture `$before` -> Eloquent write -> `$this->audit->logModel('<entity>.<verb>', $model, $before, $after)` -> `back()->with('success', '...')`. `AdminLayout` shows the flash banner.
- **Questionnaire editor** (`questionnaires/Edit.vue`): settings form on the left; each question and option has move up, move down, edit and delete. Move posts the whole list's new order to `questionnaires.reorder`; edit opens `QuestionForm` / `OptionForm` in place (one open at a time); delete goes through `ConfirmDialog`.
- **AI settings page** (`ai-settings/Index.vue`): the settings form is an Inertia `useForm` PUT; Test connection, prompt preview and live preview are JSON endpoints called with `axios`. Live preview sends `{use_case, context: {context: <sample JSON>}}` because the seeded templates read `{{context}}`.
- **Index pattern**: query + `filled('search')` LIKE filter -> `paginate(20)->withQueryString()` -> `Inertia::render('admin/<section>/Index', ['<items>' => $paginator, 'filters' => $request->only([...])])`.

### How to add a new admin CRUD section
1. Migration + model (`$fillable`, `casts()`); seeder with `updateOrCreate` if it has defaults, registered in `DatabaseSeeder`.
2. `app/Http/Controllers/Admin/Admin<Thing>Controller extends AdminController`, inject `AdminAuditService`, follow the index/mutation patterns above; audit every write.
3. Add a `Route::prefix('<things>')->name('<things>.')` group inside the existing group in `routes/admin.php`.
5. Add an entry to `navItems` in `resources/js/layouts/AdminLayout.vue`.
6. Feature test in `tests/Feature/Admin/Admin<Thing>Test.php` (`RefreshDatabase`, `Role::factory()` + `User::factory()->create(['role_id' => ...])`, assert redirect + `assertDatabaseHas`, and 403 for a role-less user).
7. Run `npm run build` (page tests need the Vite manifest), then the tests.

## Rules and gotchas
- **Permissions are enforced on the routes** (`routes/admin.php`, `can:` middleware): `manage-users` for user and couple changes and audit logs, `manage-content` for questionnaires, questions, options, challenges, achievements, email templates, AI prompts and media, `super-admin` for feature flags, system settings and AI settings, `view-analytics` for analytics. Any admin can view the dashboard and the user list. `auth.can` is shared for admins and `AdminLayout` hides nav items the role cannot open.
- **User changes** (`AdminUserController::guardTarget`): nobody suspends or deletes their own account, only a super admin touches a super admin, the last super admin cannot be removed, suspended or demoted, and only a super admin changes a role (never their own). Deleting a user goes through `DeleteAccountAction`.
- **Deleting content that has user data**: a questionnaire with responses is archived instead; a question with answers is refused with an error flash.
- **Suspension is enforced** by `EnsureUserIsNotSuspended` on every web request: the session ends and the login page shows why.
- Gates defined: `access-admin` (any admin role), `manage-users` (super admin, administrator), `manage-content` (super admin, administrator, moderator), `view-analytics` (super admin, administrator, support), `super-admin`, `viewLogViewer` (super admin only; nullable user).
- Only `access-admin` (in the three FormRequests' `authorize()`) and `viewLogViewer` (by the package) are ever evaluated.
- `User::adminRole()` loads the `role` relation on each call path that is not already eager-loaded.
- Admin pages use the app theme tokens and primitives (see "Design system" in `frontend.md`), lucide icons, the `ui/button` `Button`, and support dark mode.
- **Flags and settings that do something** are read only through `App\Services\RuntimeSettings` (`#[Scoped]`: one query per table per request or queued job; a missing row or table gives the default). Wired flags: `registration` (default on; off makes `CreateNewUser` refuse and sets the shared prop `registrationOpen` to false, which hides sign-up on Welcome and Login), `maintenance_mode` (default off; on makes `EnsureNotInMaintenance` answer every web request with the `Maintenance` page and a 503, except for admins and the login, 2FA, passkey, logout, `/up` and `/health` paths), `ai_generation` (default on; off makes `AIService` return the failure `ai_disabled` without calling the provider). Wired setting: `invitation_expiry_days` (default 7, minimum 1). To wire another one, add it to `FLAG_DEFAULTS` or `SETTINGS_IN_USE` in `RuntimeSettings`; the admin pages label everything else "Not in use".
- **Not in use**: the other seven seeded flags, every seeded setting except `invitation_expiry_days`, and the whole Email templates section (no seeder, no mail class reads it; its nav entry is removed but the routes and pages remain at `/admin/email-templates`). The `ai` settings group is hidden from the generic Settings page because the AI settings page edits it with validation.
- **Questionnaire rules**: status, visibility and question type are validated with `Rule::enum`; the slug is generated once on create and made unique with a numeric suffix (`title`, `title-2`, ...), and is not changed by a rename; `partner_optional` is forced to false unless `is_solo` is true; slider questions take `minimum_value`, `maximum_value`, `step_value`, `unit`.
- AI settings/prompt semantics are documented in `ai.md`; questionnaire/challenge/achievement semantics in their module docs.
- **Suspected issue 3 (authorization):** `manage-users`, `manage-content`, `view-analytics`, `super-admin` are never checked. Any admin role (including Support/Moderator) can delete users, and can change any user's `role_id` — including their own to super administrator (`AdminUserController.php:59-69`). No guard against deleting or suspending yourself (`:104`).
- **Suspected issue 4:** `is_suspended` is only written and displayed; nothing in auth or middleware blocks a suspended user.
- **Suspected issue 5 (dead code):** `FeatureFlag::isEnabled()` and `SystemSetting::get()/set()/typedValue()` are never called (the app reads through `RuntimeSettings`).
- **Analytics definitions**: a user is *active* in a period when they have a row in `activity_logs` in it (questionnaire completed, moment created, love note sent, achievement unlocked, partner connected). Logins are not recorded anywhere, so someone who only browses is not counted; the page says so. Average compatibility is `AVG(date_night_plans.compatibility_score)` over couple plans (`is_solo = false`); `responses.compatibility_score` is never written. Sign-up counts are no longer on this page.
- **Audit log and personal data**: `user.deleted` stores only `id`, `role_id`, `created_at`, `email_verified_at`. A migration cleared `before`/`after` on older `user.deleted` rows. `user.updated` rows still hold the name and email as they were at the time, also after the user is deleted.
- **Suspected issue 8:** users filter `admins` is `whereNotNull('role_id')` (`AdminUserController.php:36`), which also matches users holding the non-admin `user` role.
- `AdminUserController@disconnect` and `AdminCoupleController@disconnect` both unpair through `PartnerInvitationService::disconnectPartner()` and differ only in the audit action (`user.disconnected` / `couple.disconnected`) and the flash message. **Suspected issue 10:** neither they nor `reconnect` fire `PartnerDisconnected`/`PartnerConnected` events. `reconnect` does set `users.connected_at` on both users, and the disconnects clear it.
- **View as user** (`App\Services\ImpersonationService`, `App\Http\Middleware\RestrictImpersonation` in the web group):
  - Who: super administrators only, never on an administrator account or their own. The button on `admin/users/Show.vue` follows the `canViewAs` prop.
  - How: the session is switched to the user and keeps `impersonator_id` and `impersonation_started_at`. The stop route sits outside the admin group because the session then belongs to a non-admin.
  - Read-only: every request that is not GET or HEAD is refused (flash `error`, or 403 for JSON), except stop and logout.
  - Closed pages: security, two-factor and passkey routes, password confirmation, the data export download, and the weekly reflection (opening it generates one and can call the AI).
  - Pages that write when opened stay quiet: no "partner viewed your plan" notice, a love note is not marked read, a questionnaire attempt is not auto-started. **A new GET action that writes must check `ImpersonationService::isActive()` or be added to `RestrictImpersonation::CLOSED_ROUTES`.**
  - Ends after 30 minutes (`ImpersonationService::MINUTES`) on the next request, returning to the admin's own session.
  - `components/ImpersonationBanner.vue` (in `MobileLayout`) shows who is being viewed and a Stop button, from the shared prop `impersonating`.
  - The privacy policy (`legal/Privacy.vue`) describes this access. The administrator sees everything the user sees, including love notes, answers and private notes.
- No link to `/log-viewer` or to `/admin` exists anywhere in the UI.

## Related modules
`auth-settings.md` (users, verification), `onboarding-partner.md` (partner links), `questionnaires.md`, `challenges-achievements-streaks.md`, `ai.md` (prompts, settings, providers), `notifications-activity-mail.md`, `frontend.md` (layouts, route helpers), `platform.md` (providers, seeding, tests).

## Tests
`tests/Feature/Admin/*` cover: access per role, user index/show/update/suspend/unsuspend/delete/disconnect/search, challenge CRUD + archive, feature-flag view/toggle + `isEnabled` helper, log-viewer access. `AdminQuestionnaireEditorTest` covers the three mode flags, enum validation, duplicate titles, question and option updates, signals and both reorders; `AdminAiSettingsPageTest` covers the page and its super-admin gate; `tests/Feature/RuntimeSettingsTest.php` covers the wired flags and setting. Couples: only disconnect is covered. `AdminImpersonationTest` covers who may start, the audit entries, read-only enforcement, closed pages, quiet GETs, stop and the 30-minute limit. Not covered: achievements, the settings page, email templates, media, audit logs, analytics.

```
npm run build                       # once; Inertia page responses need public/build/manifest.json
php artisan test tests/Feature/Admin
```

## Change log
- 2026-11-24 — Initial doc.
- 2026-11-24 — Admin layout and all admin pages restyled to the design system (tokens, lucide icons, dark mode). `AdminTable` empty row now shows only when there are no rows; `AdminPagination` renders paginator labels as HTML.
- 2026-11-24 — Roadmap Phase 0: per-section gates on admin routes, role-change and self-action rules, answered questionnaires archive on delete, suspension enforced.
- 2026-11-24 — Roadmap Phase 1: every admin page uses Wayfinder helpers (`import admin from '@/routes/admin'`); the pages that crashed on load render again.
- 2026-11-24 — Roadmap Phase 2 (2.2, 2.8, 2.13): AI settings page and nav entry; questionnaire editor sets solo / partner optional / intimacy, edits and reorders questions and options, edits option signals, validates enums and generates unique slugs; `RuntimeSettings` wires the `registration`, `maintenance_mode` and `ai_generation` flags and the `invitation_expiry_days` setting; everything else is labelled "Not in use" and Email templates left the nav.
- 2026-11-25 — Roadmap Phase 3 (3.4, 3.11, 3.14): media is images only on the uploads disk and served through `admin.media.show` (and `MediaLibrary` now points at the `media_library` table, so uploads work); media updates, reordering and AI test calls are audited; `user.deleted` keeps no personal data; analytics counts active users from the activity log and reads compatibility from couple plans.
- 2026-11-26 — Roadmap Phase 4: (4.6) both admin disconnect routes share `PartnerInvitationService::disconnectPartner()`; routes, audit actions and messages unchanged.
- 2026-11-25 — Roadmap Phase 5.9: admin reconnect and disconnect keep `users.connected_at` in step with `partner_id`.
- 2026-11-25 — Roadmap Phase 5.11: "view as user" for super administrators: read-only, 30 minutes, audited.
