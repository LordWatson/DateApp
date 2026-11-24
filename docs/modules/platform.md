# Platform (bootstrap, config, console, tooling, tests)
> Last verified against code: 2026-11-24

## Purpose
Everything cross-cutting on the backend: how the app boots, providers, config, database/seeding, scheduled commands, health check, starter-kit tooling, quality tooling, CI, and test conventions.

## Key files
| Path | Role |
|---|---|
| `bootstrap/app.php` | Routing, middleware, **schedule**, exception rendering, registers `EventServiceProvider` |
| `bootstrap/providers.php` | `AppServiceProvider`, `AIServiceProvider`, `FortifyServiceProvider` |
| `app/Providers/*` | See Providers |
| `routes/web.php`, `routes/admin.php`, `routes/settings.php`, `routes/console.php` | `settings.php` is `require`d at the end of `web.php`; `console.php` only defines `inspire` |
| `config/datenight.php`, `config/ai.php`, `config/log-viewer.php`, `config/inertia.php`, `config/fortify.php` | Non-framework config |
| `app/Console/Commands/*` | Artisan commands |
| `app/Http/Controllers/HealthController.php` | `GET /health` |
| `app/Jobs/ExportUserDataJob.php` | User data export job |
| `database/seeders/DatabaseSeeder.php`, `database/factories/*` | Seeding and factories |
| `chisel.php`, `chisel-paths.php`, `app/Console/Commands/InstallFeaturesCommand.php` | Starter-kit feature installer (laravel/chisel) |
| `pint.json`, `phpstan.neon`, `phpunit.xml`, `eslint.config.js`, `.prettierrc` | Quality tooling |
| `.github/workflows/lint.yml`, `tests.yml` | CI |
| `tests/TestCase.php`, `tests/Feature/**`, `tests/Unit/ExampleTest.php` | Tests |

## Bootstrap (`bootstrap/app.php`)
- **Routing**: `web: [routes/web.php, routes/admin.php]`, `commands: routes/console.php`, framework health route `/up`. No `api.php`.
- **Events**: `withEvents(discover: false)` + `EventServiceProvider` via `withProviders` — listeners are registered only through its `$listen` map (see `events-jobs.md`).
- **Middleware**: `web` group appends `HandleAppearance`, `HandleInertiaRequests`, `AddLinkHeadersForPreloadedAssets`. Cookies `appearance` and `sidebar_state` are not encrypted.
- **Aliases**: `onboarding` -> `EnsureOnboardingComplete`, `admin` -> `EnsureUserIsAdmin`.
- **Exceptions**: JSON rendering when the request is `api/*` or `expectsJson()`; nothing else customised.
- **Schedule**: defined here with `withSchedule`, not in `routes/console.php`.

## Providers
| Provider | Does |
|---|---|
| `AppServiceProvider` | Gates (`access-admin`, `manage-users`, `manage-content`, `view-analytics`, `super-admin`, `viewLogViewer` — see `admin.md`); `Date::use(CarbonImmutable)`; `DevCommands::except('logs')` when `pcntl` is missing; `DB::prohibitDestructiveCommands()` in production; `Password::defaults()` = min 12, mixed case, letters, numbers, symbols, uncompromised in production, `null` (no default rule) otherwise; binds legacy `App\Contracts\AIProvider` |
| `AIServiceProvider` | Singleton `AISettingsService`; singleton `App\Contracts\AI\AIProvider` chosen by `config('ai.provider')` (`anthropic` by default, `deepseek`, `null`); on boot overlays admin-saved `ai.*` system settings onto config. Details in `ai.md` |
| `FortifyServiceProvider` | Fortify actions (`CreateNewUser`, `ResetUserPassword`), Inertia auth views, rate limiters `login` (5/min), `two-factor` (5/min), `passkeys` (10/min). Details in `auth-settings.md` |
| `EventServiceProvider` | Explicit event -> listener map; discovery off |

## Configuration
- `config/datenight.php` keys: `challenge_frequency`, `invitation_expiry_days`, `compatibility.{high,medium,low}`, `default_questionnaire_slug`, `love_note_max_length`, `reminder_hours`, `cache.{dashboard,compatibility,challenge,questionnaire}_ttl`, `queues.{emails,notifications,exports,analytics,default}`.
- `config/ai.php`: `provider`, `defaults` (temperature, max_tokens, timeout, retry_attempts, retry_delay_ms, response_format), `providers.anthropic`, `providers.deepseek`, `safety.system_rules`, `logging`.
- Config defaults when env is unset: DB `sqlite`, queue `database`, cache `database`, session `database`, mail `log`, filesystem `local`, log channel `daily`. Mail transports include `resend` (`resend/resend-laravel`).
- `.env.example` variable groups (names only): **App** `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `APP_MAINTENANCE_DRIVER`, `BCRYPT_ROUNDS`; **Logging** `LOG_CHANNEL`, `LOG_STACK`, `LOG_DEPRECATIONS_CHANNEL`, `LOG_LEVEL`; **Database** `DB_CONNECTION` (+ commented `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`); **Session** `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_DOMAIN`; **Infra** `BROADCAST_CONNECTION`, `FILESYSTEM_DISK`, `QUEUE_CONNECTION`, `CACHE_STORE`, `MEMCACHED_HOST`, `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`; **Mail** `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`; **AWS** `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_USE_PATH_STYLE_ENDPOINT`; **Frontend** `VITE_APP_NAME`; **AI** `DEEPSEEK_API_KEY`, `DEEPSEEK_BASE_URL`, `DEEPSEEK_MODEL`, `AI_PROVIDER`, `AI_TEMPERATURE`, `AI_MAX_TOKENS`, `AI_TIMEOUT`, `AI_RETRY_ATTEMPTS`.
- Read by config but absent from `.env.example`: `RESEND_API_KEY`, `AI_RETRY_DELAY_MS`, `AI_LOG_CHANNEL`, `AI_LOG_PROMPTS`, `LOG_VIEWER_*`, and every env key in `config/datenight.php`.

## Database
- Default connection `sqlite` (`config/database.php`; `.env.example` also sets sqlite). Tests force sqlite `:memory:`.
- Migrate/seed: `php artisan migrate`, `php artisan db:seed`, or `php artisan migrate:fresh --seed`.
- `DatabaseSeeder` order: creates two hard-coded, partnered, verified users -> `RoleSeeder` -> `FeatureFlagSeeder` -> `SystemSettingSeeder` -> `SoloDatePlannerQuestionnaireSeeder` -> `QuestionnaireSeeder` -> `SeasonalQuestionnaireSeeder` -> `IntimacyQuestionnaireSeeder` -> `IntimacyGameSeeder` -> `ChallengeSeeder` -> `DateNightThemeSeeder` -> `AchievementSeeder` -> `AiPromptTemplateSeeder` -> gives the first user the super administrator role and marks both as onboarded. Uses `WithoutModelEvents`.
- Factories (`database/factories`): Achievement, AiPromptTemplate, Answer, CalendarEvent, Challenge, DailyChallenge, DateNightPlan, DateNightTheme, LoveNote, Moment, Question, QuestionOption, Questionnaire, Response, Role, SavedProfileAnswer, SavedProfile, User. `UserFactory` defaults: verified, `onboarding_completed = true`, no partner, no role; states `unverified()`, `withTwoFactor()`, `withStreak(n)`.

## Console commands and schedule
| Command | Schedule (`bootstrap/app.php`) | What it does |
|---|---|---|
| `datenight:select-daily-challenge` | daily 00:01 | Dispatches `DailyChallengeSelectionJob` |
| `datenight:evaluate-streaks` | daily 00:05 | Sets `current_streak = 0` for users whose last completion is before yesterday 00:00 |
| `datenight:expire-invitations` | daily 00:10 | Only counts expired, unaccepted invitations and prints the number — writes nothing |
| `datenight:clean-exports` | daily 02:00 | Deletes files in `exports/` (default disk) older than 24h |
| `datenight:refresh-statistics` | daily 03:00 | Dispatches `RefreshRelationshipStatisticsJob` per partnered user |
| `datenight:send-reminders` | daily 09:00 | Creates an in-app `AppNotification` for onboarded, partnered users with no completion in 7 days |
| `install:features` | **not scheduled** — run by composer `post-update-cmd` and the Laravel installer | Starter-kit feature chooser (below) |
| `inspire` (closure, `routes/console.php`) | **not scheduled** | Framework default |

The scheduler needs `php artisan schedule:run` every minute (or `schedule:work` locally); nothing in the repo sets that up.

## Health, exports
- `GET /health` (name `health`, public, no auth) -> `HealthController`: checks DB PDO, cache put/get, `Queue::size()`, default-disk write/delete. 200 `healthy` or 503 `degraded` with per-check `status`/`message`. Separate from the framework's `/up`.
- `ExportUserDataJob(User, string $format)`: queue `exports`, 3 tries, 300s timeout; writes `exports/user-{id}-{Y-m-d}.{format}` to the default disk; content is always JSON (name, email, created_at, responses with answers, moments) regardless of `$format`. `datenight:clean-exports` purges after 24h.

## Starter-kit tooling
- **laravel/chisel** (`chisel.php`, `chisel-paths.php`): leftover from the Laravel Vue starter kit. `php artisan install:features` asks which auth features to keep (email verification, registration, 2FA, passkeys, password confirmation) and then *deletes or strips* the matching files/sections listed in `chisel-paths.php` (pages, components, tests, migrations), runs Pint, `wayfinder:generate --with-form`, ESLint/Prettier, and finally deletes `chisel.php`, `chisel-paths.php` and `InstallFeaturesCommand` itself. Both files still exist, so it has never been completed here. The `/* @chisel-passkeys */` markers in `resources/js/types/auth.ts` belong to it.
- **laravel/pao** (dev): "agent-optimized output for PHP testing tools" — `php artisan test` prints a compact JSON summary when run by an AI agent.
- **laravel/boost** (dev), **laravel/pail** (dev log tail), **opcodesio/log-viewer** (`/log-viewer`, super admin only — `admin.md`), **laravel/wayfinder** (typed route helpers — `frontend.md`).

## Tooling and scripts
| Tool | Config | Notes |
|---|---|---|
| Pint | `pint.json` | preset `laravel` |
| PHPStan/Larastan | `phpstan.neon` | **level 7**; paths `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/` |
| PHPUnit 12 | `phpunit.xml` | suites `Unit`, `Feature`; sqlite `:memory:`, cache/session `array`, mail `array`, queue `sync`, `BCRYPT_ROUNDS=4` |
| ESLint / Prettier / vue-tsc | `eslint.config.js`, `.prettierrc` | see `frontend.md` |

| Composer script | Runs |
|---|---|
| `composer setup` | install, copy `.env`, `key:generate`, `migrate --force`, `npm install`, `npm run build` |
| `composer dev` | `php artisan dev` — server, `queue:listen --tries=1 --timeout=0`, Pail (`logs`), Vite |
| `composer lint` / `lint:check` | `pint --parallel` / `pint --parallel --test` |
| `composer types:check` | `phpstan analyse` |
| `composer test` | `config:clear`, `lint:check`, `types:check`, `php artisan test` |
| `composer ci:check` | `npm run lint:check`, `format:check`, `types:check`, then `composer test` |

## CI (`.github/workflows`)
Both run on push/PR to `develop`, `main`, `master`, `workos`.
- `tests.yml`: matrix PHP 8.3 / 8.4 / 8.5, Node 22; `npm i`, `composer install`, copy `.env.example`, `key:generate`, `npm run build`, `composer types:check`, `php artisan test`.
- `lint.yml`: PHP 8.4; runs the *fixing* variants (`composer lint`, `npm run format`, `npm run lint`); the auto-commit step is commented out.

## Tests: layout and conventions
- PHPUnit classes (not Pest): `class XTest extends Tests\TestCase`, methods `public function test_snake_case(): void`. 48 test files, all `use RefreshDatabase`.
- `tests/TestCase.php` has one helper: `skipUnlessFortifyHas(string $feature)`.
- Layout: one file per feature at `tests/Feature/<Feature>Test.php`, plus folders `AI`, `ActivityLog`, `Admin`, `Auth`, `Commands`, `Events`, `Health`, `Jobs`, `Listeners`, `Settings`. `tests/Unit` holds only `ExampleTest`.
- Typical test: build users with `User::factory()` (+ `Role::factory()` for admins), `actingAs($user)->get|post(...)` using literal URLs, assert status/redirect and `assertDatabaseHas`.
- Run: `php artisan test` (all), `php artisan test tests/Feature/Health`, `php artisan test --filter=test_name`.
- State on 2026-11-24: 294 tests, 293 pass; `AIIntegrationTest::test_admin_can_view_ai_settings_page` fails (missing page, see `admin.md`).

## Local environment
- Windows + Laravel Herd; site `datenight.test` on PHP 8.5 (owner's setup, not encoded in the repo). `php` is available in PowerShell but not on the Git Bash PATH.
- `composer dev` on Windows: `pcntl` is missing, so `AppServiceProvider` drops the Pail `logs` process (otherwise its crash kills the whole command). Use `/log-viewer` or `storage/logs` instead.
- Run `npm run build` (or keep `npm run dev` running) before feature tests: Inertia responses resolve the page through the Vite manifest, and Wayfinder output in `resources/js/{actions,routes,wayfinder}` is generated, not committed.

## Rules and gotchas
- New listeners must be added to `EventServiceProvider::$listen`; discovery is off.
- New scheduled work goes in `withSchedule` in `bootstrap/app.php`.
- Dates are `CarbonImmutable` app-wide — reassign results (`$d = $d->addDay()`).
- Jobs use named queues (`ai`, `analytics`, `emails`, `notifications`, `exports`, `default`) hard-coded in each job's constructor. `php artisan dev` runs `queue:listen` without `--queue`, so with the `database` connection only `default` is consumed; run `php artisan queue:work --queue=default,ai,emails,notifications,analytics,exports` to process the rest. Tests use `sync`.
- **Suspected issue (dead config):** `config/datenight.php` is never read — no `config('datenight...')` call exists. Queue names, cache TTLs, thresholds etc. are hard-coded elsewhere.
- **Suspected issue (dead code):** `AppServiceProvider.php:23` binds `App\Contracts\AIProvider` to `App\AI\NullAIProvider`; nothing else references that contract (the live one is `App\Contracts\AI\AIProvider`).
- **Suspected issue:** `ExportUserDataJob` is never dispatched and `ExportRequested` has no listeners (`EventServiceProvider.php:107`) — the export feature has no entry point; `datenight:clean-exports` cleans a folder nothing writes to.
- **Suspected issue:** `ExpireInvitationsCommand.php:16-20` is a no-op (counts only).
- **Suspected issue:** `EvaluateStreaksCommand.php:16` injects `UpdateStreakAction` and never uses it.
- **Suspected issue:** `SendRemindersCommand.php:27-33` has no de-duplication (an inactive user gets a new notification every day) and labels the reminder with `NotificationType::PartnerCompleted`. It sends no email despite "send".
- **Suspected issue:** `DatabaseSeeder.php:19-46` hard-codes real-looking personal accounts with the password `password` and is not idempotent (`User::create` — a second `db:seed` hits the unique email index). One of them becomes super administrator.
- **Suspected issue:** `/health` is public and writes to cache and disk on every call.
- **Suspected issue:** `lint.yml` cannot fail on style (it runs fixers and discards the result); `tests.yml` does not run Pint, ESLint, Prettier or `vue-tsc`. Only `composer ci:check` locally covers all checks.
- **Suspected issue:** `composer.json` requires `opcodesio/log-viewer` and `laravel/boost` at `*`.
- `routes/console.php` is effectively empty — do not look for the schedule there.

## Related modules
`events-jobs.md` (event map, jobs, queues), `ai.md` (AI provider/config), `auth-settings.md` (Fortify), `admin.md` (gates, roles, seeders for flags/settings, log viewer), `frontend.md` (Vite, Wayfinder, JS tooling), `challenges-achievements-streaks.md` and `onboarding-partner.md` (what the scheduled commands act on).

## Tests
`tests/Feature/Health/HealthCheckTest.php`, `tests/Feature/Commands/EvaluateStreaksCommandTest.php`, `tests/Feature/Commands/SelectDailyChallengeCommandTest.php`, `tests/Feature/FactoryTest.php`.

```
php artisan test tests/Feature/Health tests/Feature/Commands tests/Feature/FactoryTest.php
```

## Change log
- 2026-11-24 — Initial doc.
