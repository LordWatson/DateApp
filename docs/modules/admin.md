# Admin panel
> Last verified against code: 2026-11-24

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
| `app/Services/AdminAuditService.php`, `app/Models/AdminAuditLog.php` | Audit trail writer + model |
| `app/Models/FeatureFlag.php`, `SystemSetting.php`, `EmailTemplate.php`, `MediaLibrary.php` | Admin-owned config/content models |
| `database/seeders/FeatureFlagSeeder.php`, `SystemSettingSeeder.php` | Seed data (idempotent `updateOrCreate` by `key`) |
| `config/log-viewer.php` | opcodesio/log-viewer config |
| **Frontend** | |
| `resources/js/layouts/AdminLayout.vue` | Sidebar shell; `navItems` array is the admin menu; renders `flash.success` / `flash.error` |
| `resources/js/components/admin/AdminTable.vue`, `AdminPagination.vue` | Table shell (`columns`, `empty`) and Laravel paginator `links` renderer |
| `resources/js/pages/admin/**` | `Dashboard`, `users/{Index,Show}`, `couples/Index`, `questionnaires/{Index,Create,Edit}`, `challenges/Index`, `achievements/Index`, `feature-flags/Index`, `settings/Index`, `email-templates/{Index,Edit}`, `ai-prompts/Index`, `media/Index`, `analytics/Index`, `audit-logs/Index` |
| **Tests** | |
| `tests/Feature/Admin/*` | `AdminAuthorizationTest`, `AdminChallengeTest`, `AdminFeatureFlagTest`, `AdminUserManagementTest`, `LogViewerAccessTest` |

## Data model
| Table (migration `2026_07_12_1615xx_*`) | Model | Notes |
|---|---|---|
| `roles` | `Role` | `name`, `label`, `description`. `users.role_id` nullable; null = regular user |
| `admin_audit_logs` | `AdminAuditLog` | `user_id`, `action` (dot string, e.g. `challenge.updated`), morph `auditable_type/id`, JSON `before`/`after`, `ip_address`, `user_agent` |
| `feature_flags` | `FeatureFlag` | `key`, `label`, `description`, `enabled`, `group`. `FeatureFlag::isEnabled($key)` (false when missing) |
| `system_settings` | `SystemSetting` | `key`, `label`, `value` (string), `type` (`string/integer/boolean/json/color`), `group`. `get()`, `set()`, `typedValue()` |
| `email_templates` | `EmailTemplate` | `key`, `subject`, `html_body`, `text_body`, `variables` (array), `active` |
| `media_library` | `MediaLibrary` | file metadata + `uploaded_by`; files on the `public` disk |

Seeded feature flags: `ai_generation`, `calendar`, `love_notes`, `achievements`, `moments`, `seasonal_content`, `insights` (group `features`), `beta_features` (`beta`, off), `registration`, `maintenance_mode` (`system`).
Seeded system settings: `app_name`, `app_tagline`, `support_email`, `mail_from_name`, `mail_from_address`, `invitation_expiry_days`, `compatibility_high_threshold`, `compatibility_low_threshold`, `challenge_frequency_days`, `color_primary`, `color_secondary`, `color_background`. `AISettingsService` additionally writes `ai.*` keys (group `ai`) — see `ai.md`.

## Routes
All: middleware `auth`, `verified`, `admin`; URI prefix `/admin`; name prefix `admin.`.

| Controller | Routes (name suffix: method URI) | Manages | Audit |
|---|---|---|---|
| `AdminDashboardController` | `dashboard`: GET `/` | Counts (users, couples, responses, plans, love notes, `jobs`/`failed_jobs`), newest users, 30-day series | – |
| `AdminUserController` | `users.index/show/update/suspend/unsuspend/disconnect/destroy` | Search/filter users, edit name/email/`role_id`, suspend, unlink partner, delete | yes |
| `AdminCoupleController` | `couples.index`, `couples.disconnect` POST `/{user}/disconnect`, `couples.reconnect` POST `/reconnect` | Couples list (one row per pair via `id < partner_id`), link/unlink | yes |
| `AdminQuestionnaireController` | `questionnaires.index/create/store/edit/update/destroy/duplicate/reorder` | Questionnaire CRUD, deep duplicate (questions + options, status `draft`) | yes (not reorder) |
| `AdminQuestionController` | `questions.store` POST `/questionnaires/{questionnaire}/questions`, `questions.update/destroy` `/questions/{question}` | Questions (edited inside questionnaire Edit page) | yes |
| `AdminOptionController` | `options.store` POST `/questions/{question}/options`, `options.update/destroy/duplicate` `/options/{option}` | Question options | yes |
| `AdminChallengeController` | `challenges.index/store/update/destroy/archive/bulk-import` | Challenges; archive sets `archived=true, active=false`; index hides archived unless `?archived=1` | yes |
| `AdminAchievementController` | `achievements.index/store/update/destroy` | Achievements | yes |
| `AdminFeatureFlagController` | `feature-flags.index/store/update/destroy` | Flags grouped by `group`; update only toggles `enabled` | yes |
| `AdminSystemSettingController` | `settings.index/update`, `settings.bulk-update` POST `/settings/bulk` | Settings grouped by `group`; value only | yes |
| `AdminEmailTemplateController` | `email-templates.index/edit/update` | Edit existing templates only (no create/delete) | yes |
| `AdminAiPromptController` | `ai-prompts.index/store/update/destroy` | `AiPromptTemplate`; update bumps `version` | yes |
| `AdminAiSettingController` | `ai-settings.index/update`, `test-connection` (POST, JSON), `preview-prompt` (GET, JSON), `preview-response` (POST, JSON) | Runtime AI settings via `AISettingsService`, provider probe, usage snapshot from `AiGeneration` | update only |
| `AdminMediaController` | `media.index/store/update/destroy` | Upload (max 10 MB) to `public` disk under `folder` (default `uploads`) | upload/delete only |
| `AdminAuditLogController` | `audit-logs.index` | Read-only, 50/page, filters `search`, `action`, `user_id` | – |
| `AdminAnalyticsController` | `analytics.index` | Signup/completion/love-note/moment series, question drop-off, avg compatibility | – |

Gate column intentionally absent: **no controller checks a gate** (see Rules). Log viewer: `/log-viewer` (package routes), middleware `web`, `auth`, `AuthorizeLogViewer` -> gate `viewLogViewer`.

## Flows
- **Access**: `auth` -> `verified` -> `admin` middleware (`User::isAdmin()` = role is any of super_administrator / administrator / support / moderator). Role `user` and `role_id = null` are both non-admin.
- **Mutation pattern**: inline `$request->validate()` -> capture `$before` -> Eloquent write -> `$this->audit->logModel('<entity>.<verb>', $model, $before, $after)` -> `back()->with('success', '...')`. `AdminLayout` shows the flash banner.
- **Index pattern**: query + `filled('search')` LIKE filter -> `paginate(20)->withQueryString()` -> `Inertia::render('admin/<section>/Index', ['<items>' => $paginator, 'filters' => $request->only([...])])`.
- **Page pattern**: `defineOptions({ layout: AdminLayout })`, props typed inline, `router`/`useForm` from Inertia, URLs via `route('admin.x.y')` imported from `ziggy-js` (broken — see below).

### How to add a new admin CRUD section
1. Migration + model (`$fillable`, `casts()`); seeder with `updateOrCreate` if it has defaults, registered in `DatabaseSeeder`.
2. `app/Http/Controllers/Admin/Admin<Thing>Controller extends AdminController`, inject `AdminAuditService`, follow the index/mutation patterns above; audit every write.
3. Add a `Route::prefix('<things>')->name('<things>.')` group inside the existing group in `routes/admin.php`.
4. Page(s) in `resources/js/pages/admin/<things>/Index.vue` with `defineOptions({ layout: AdminLayout })`, `AdminTable` + `AdminPagination`. Prefer Wayfinder imports (`@/routes/admin/<things>`) over `ziggy-js` (see Suspected issue 1).
5. Add an entry to `navItems` in `resources/js/layouts/AdminLayout.vue`.
6. Feature test in `tests/Feature/Admin/Admin<Thing>Test.php` (`RefreshDatabase`, `Role::factory()` + `User::factory()->create(['role_id' => ...])`, assert redirect + `assertDatabaseHas`, and 403 for a role-less user).
7. Run `npm run build` (page tests need the Vite manifest), then the tests.

## Rules and gotchas
- Gates defined: `access-admin` (any admin role), `manage-users` (super admin, administrator), `manage-content` (super admin, administrator, moderator), `view-analytics` (super admin, administrator, support), `super-admin`, `viewLogViewer` (super admin only; nullable user).
- Only `access-admin` (in the three FormRequests' `authorize()`) and `viewLogViewer` (by the package) are ever evaluated.
- `User::adminRole()` loads the `role` relation on each call path that is not already eager-loaded.
- Admin pages hard-code light gray/pink Tailwind classes and emoji icons; they do not use the app theme tokens, `components/ui`, or dark mode.
- AI settings/prompt semantics are documented in `ai.md`; questionnaire/challenge/achievement semantics in their module docs.
- **Suspected issue 1 (admin UI broken at runtime):** 15 admin pages `import { route } from 'ziggy-js'`, but the Ziggy backend (`tightenco/ziggy`) is not installed and there is no `@routes` directive in `resources/views/app.blade.php`, so `route()` throws "Ziggy error: missing configuration". Pages calling it in the template (`email-templates/Index`+`Edit`, `questionnaires/Create`+`Edit`, `users/Show`) fail on render; the rest fail on search/save/delete.
- **Suspected issue 2 (missing page):** `AdminAiSettingController@index` renders `admin/ai-settings/Index` (`AdminAiSettingController.php:40`) but no such page exists; `tests/Feature/AI/AIIntegrationTest::test_admin_can_view_ai_settings_page` fails with a 500. There is also no nav entry for AI settings.
- **Suspected issue 3 (authorization):** `manage-users`, `manage-content`, `view-analytics`, `super-admin` are never checked. Any admin role (including Support/Moderator) can delete users, and can change any user's `role_id` — including their own to super administrator (`AdminUserController.php:59-69`). No guard against deleting or suspending yourself (`:104`).
- **Suspected issue 4:** `is_suspended` is only written and displayed; nothing in auth or middleware blocks a suspended user.
- **Suspected issue 5 (dead config):** `FeatureFlag::isEnabled()` is never called — none of the 10 seeded flags gate anything (incl. `registration`, `maintenance_mode`). `SystemSetting::get()/set()/typedValue()` are never called; only `ai.*` keys are read (by `AISettingsService`). `email_templates` has no seeder and no mail class reads it, so the Email Templates section is empty and inert.
- **Suspected issue 6:** `AdminMediaController@store` accepts any file type and keeps the client extension on the public disk (`AdminMediaController.php:44,51`). `update` is not audited (`:72`).
- **Suspected issue 7:** `AdminAnalyticsController.php:21-23` uses MySQL-only `DATE_FORMAT`; fails on sqlite (default `DB_CONNECTION` and the test DB). Its `dau`/`mau` are signup counts, not active users.
- **Suspected issue 8:** users filter `admins` is `whereNotNull('role_id')` (`AdminUserController.php:36`), which also matches users holding the non-admin `user` role.
- **Suspected issue 9:** `admin.questionnaires.reorder` actually reorders *questions* (`AdminQuestionnaireController.php:131-135`). `store` builds `slug` from the title with no uniqueness handling (`:59`).
- **Suspected issue 10:** `AdminUserController@disconnect` and `AdminCoupleController@disconnect` are duplicates; neither they nor `reconnect` fire `PartnerDisconnected`/`PartnerConnected` events.
- No link to `/log-viewer` or to `/admin` exists anywhere in the UI.

## Related modules
`auth-settings.md` (users, verification), `onboarding-partner.md` (partner links), `questionnaires.md`, `challenges-achievements-streaks.md`, `ai.md` (prompts, settings, providers), `notifications-activity-mail.md`, `frontend.md` (layouts, route helpers), `platform.md` (providers, seeding, tests).

## Tests
`tests/Feature/Admin/*` cover: access per role, user index/show/update/suspend/unsuspend/delete/disconnect/search, challenge CRUD + archive, feature-flag view/toggle + `isEnabled` helper, log-viewer access. Not covered: couples, questionnaires/questions/options, achievements, settings, email templates, media, audit logs, analytics.

```
npm run build                       # once; Inertia page responses need public/build/manifest.json
php artisan test tests/Feature/Admin
```

## Change log
- 2026-11-24 — Initial doc.
