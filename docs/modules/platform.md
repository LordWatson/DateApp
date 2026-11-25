# Platform (bootstrap, config, console, tooling, tests)
> Last verified against code: 2026-11-25

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
| `pint.json`, `phpstan.neon`, `phpunit.xml`, `eslint.config.js`, `.prettierrc` | Quality tooling |
| `.github/workflows/lint.yml`, `tests.yml` | CI |
| `tests/TestCase.php`, `tests/Feature/**`, `tests/Unit/ExampleTest.php` | Tests |
| `playwright.config.ts`, `tests/e2e/**` | Browser tests (Playwright) |

## Bootstrap (`bootstrap/app.php`)
- **Routing**: `web: [routes/web.php, routes/admin.php]`, `commands: routes/console.php`, framework health route `/up`. No `api.php`.
- **Events**: `withEvents(discover: false)` + `EventServiceProvider` via `withProviders` — listeners are registered only through its `$listen` map (see `events-jobs.md`).
- **Middleware**: `web` group appends `HandleAppearance`, `HandleInertiaRequests`, `AddLinkHeadersForPreloadedAssets`. Cookies `appearance` and `sidebar_state` are not encrypted.
- **Aliases**: `onboarding` -> `EnsureOnboardingComplete`, `admin` -> `EnsureUserIsAdmin`.
- **Exceptions**: JSON rendering when the request is `api/*` or `expectsJson()`. `$exceptions->respond()` then turns 403, 404, 500 and 503 responses into the Inertia page `Error` (prop `status`, same status code) through `Inertia\ExceptionResponse`, for guests and signed-in users alike. A 500 keeps Laravel's debug page while `app.debug` is on. A 419 redirects back with the session flash `error` = "The page expired, please try again." (shown as a toast). JSON responses are left alone. The page is rendered without shared props because an unknown URL never reaches the `web` middleware, so there may be no session.
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
- Config defaults when env is unset: DB `sqlite`, queue `database`, cache `database`, session `database`, mail `log`, filesystem `local`, uploads disk `local` (`filesystems.uploads`, env `UPLOADS_DISK`; must be a private disk, `s3` needs `league/flysystem-aws-s3-v3`, which is not installed), log channel `daily`. Mail transports include `resend` (`resend/resend-laravel`).
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
| `datenight:evaluate-streaks` | daily 00:05 | Sets `current_streak = 0` for users whose last completion is before yesterday 00:00 in their own timezone |
| `datenight:move-uploads {--from=public}` | **not scheduled** — run once after deploying Phase 3, and after any change of `UPLOADS_DISK` | Moves moment photos from the old disk to the uploads disk; safe to run again |
| `datenight:expire-invitations` | daily 00:10 | Only counts expired, unaccepted invitations and prints the number — writes nothing |
| `datenight:clean-exports` | daily 02:00 | Deletes files in `exports/` (default disk) older than 24h |
| `datenight:refresh-statistics` | daily 03:00 | Dispatches `RefreshRelationshipStatisticsJob` per partnered user |
| `datenight:send-reminders` | daily 09:00 | Creates an in-app `AppNotification` for onboarded, partnered users with no completion in 7 days |
| `datenight:sync-prompts` | **not scheduled** — run it on every deploy | Inserts AI prompt template versions that are missing; never updates a row (see `ai.md`) |
| `inspire` (closure, `routes/console.php`) | **not scheduled** | Framework default |

The scheduler needs `php artisan schedule:run` every minute (or `schedule:work` locally); nothing in the repo sets that up.

## Health, exports
- `GET /health` (name `health`, no auth, `throttle:10,1`) -> `HealthController`: checks DB PDO, cache put/get, `Queue::size()` and a write/delete on the uploads disk. 200 `healthy` or 503 `degraded` with per-check `status`/`message`. When `HEALTH_TOKEN` (`config('app.health_token')`) is set, it returns 404 unless the request carries it as a bearer token. The framework's `/up` stays open.
- `ExportUserDataJob(User, string $format)`: queue `exports`, 3 tries, 300s timeout; writes `exports/user-{id}-{Y-m-d}.{format}` to the default disk; content is always JSON (name, email, created_at, responses with answers, moments) regardless of `$format`. `datenight:clean-exports` purges after 24h.

## Starter-kit tooling
- The starter-kit feature installer (laravel/chisel, `chisel.php`, `install:features`) was removed in Phase 3; `composer update` no longer runs anything that rewrites project files.
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
| `composer dev` | `php artisan dev` — server, `queue:listen --tries=1 --timeout=0` on all six queues, Pail (`logs`), Vite |
| `composer lint` / `lint:check` | `pint --parallel` / `pint --parallel --test` |
| `composer types:check` | `phpstan analyse` |
| `composer test` | `config:clear`, `lint:check`, `types:check`, `php artisan test` |
| `composer ci:check` | `npm run lint:check`, `format:check`, `types:check`, then `composer test` |

## CI (`.github/workflows`)
One workflow, `tests.yml` (job `ci`), on push/PR to `develop`, `main`, `master`.
- PHP 8.5, Node 22: `npm ci`, `composer install`, copy `.env.example`, `key:generate`, create and migrate the SQLite file, `npm run build` (also generates Wayfinder), then the checking variants, each of which fails the run: `composer lint:check`, `npm run lint:check`, `npm run format:check`, `npm run types:check`, `composer types:check` (PHPStan), `php artisan test`.

## Tests: layout and conventions
- PHPUnit classes (not Pest): `class XTest extends Tests\TestCase`, methods `public function test_snake_case(): void`. 48 test files, all `use RefreshDatabase`.
- `tests/TestCase.php` has one helper: `skipUnlessFortifyHas(string $feature)`.
- Layout: one file per feature at `tests/Feature/<Feature>Test.php`, plus folders `AI`, `ActivityLog`, `Admin`, `Auth`, `Commands`, `Events`, `Health`, `Jobs`, `Listeners`, `Settings`. `tests/Unit` holds only `ExampleTest`.
- Typical test: build users with `User::factory()` (+ `Role::factory()` for admins), `actingAs($user)->get|post(...)` using literal URLs, assert status/redirect and `assertDatabaseHas`.
- Run: `php artisan test` (all), `php artisan test tests/Feature/Health`, `php artisan test --filter=test_name`.

## Browser tests (Playwright)
- Run: `npm run build` once (the tests use the production build in `public/build`), then `npm run test:e2e` from PowerShell (`php` must be on the PATH). One test: `npx playwright test -g "solo questionnaire"`. About 1.5 minutes.
- Files: `playwright.config.ts`, `tests/e2e/*.spec.ts` (auth, couple and solo journey, page smoke), `auth.setup.ts`, `fixtures.ts`, `questionnaire.ts`, `env.ts`, `global-setup.ts`, `server.php`. `@playwright/test` is the only JavaScript test dependency; it drives the installed Chrome (`channel: "chrome"`, headless), so no browser download.
- What it starts: PHP's built-in server on `127.0.0.1:8787` (`E2E_PORT` to change) with the router `tests/e2e/server.php`, stopped when the run ends. The router ignores `public/hot`, so a running Vite dev server does not matter.
- Throwaway database: `database/e2e.sqlite` (git-ignored), emptied and rebuilt with `migrate:fresh --seed` on every run, so `DemoCoupleSeeder` provides the two users. Environment variables in `tests/e2e/env.ts` override `.env`: `APP_ENV=local`, sqlite, session and cache `database` (inside the throwaway file), queue `sync`, mail `array`, `AI_PROVIDER=null`, SSR off. `.env` is only read for what is not overridden (`APP_KEY`).
- Global setup runs `config:clear`, `route:clear` and `event:clear` (they delete files in `bootstrap/cache`), and refuses to migrate unless the resolved database path is the throwaway file and the AI provider is `null`.
- Every test fails on an uncaught page error, and requests to other hosts (fonts, geocoding) are blocked. Each demo user signs in once in `auth.setup.ts` and the tests reuse the saved session (`tests/e2e/.auth`, git-ignored), because login is limited to 5 a minute.
- One worker only: the built-in server handles one request at a time. Output goes to `test-results/` and `playwright-report/` (git-ignored). ESLint ignores `tests/e2e` and `playwright.config.ts`; `vue-tsc` and PHPUnit never scan them.
- Not in CI yet.

## Local environment
- Windows + Laravel Herd; site `datenight.test` on PHP 8.5 (owner's setup, not encoded in the repo). `php` is available in PowerShell but not on the Git Bash PATH.
- `composer dev` on Windows: `pcntl` is missing, so `AppServiceProvider` drops the Pail `logs` process (otherwise its crash kills the whole command). Use `/log-viewer` or `storage/logs` instead.
- Run `npm run build` (or keep `npm run dev` running) before feature tests: Inertia responses resolve the page through the Vite manifest, and Wayfinder output in `resources/js/{actions,routes,wayfinder}` is generated, not committed.

## Rules and gotchas
- **Feature flags and system settings** are read through `App\Services\RuntimeSettings` only (scoped, cached per request, safe defaults when a row or table is missing). `EnsureNotInMaintenance` is appended to the `web` group after `HandleInertiaRequests` so the maintenance page still gets the shared props. Details in `admin.md`.
- **Rate limiters** are registered in `AppServiceProvider::registerRateLimiters()`: `invites` (10 per user per day) and `ai` (20 per user per hour, on questionnaire finish and the admin AI test and preview).
- **Seeding**: `DatabaseSeeder` seeds reference data only and can be run again. `DemoCoupleSeeder` (two connected users with the password `password`, one a super admin) runs only in `local` and `testing`, and throws if run in production.
- New listeners must be added to `EventServiceProvider::$listen`; discovery is off.
- New scheduled work goes in `withSchedule` in `bootstrap/app.php`.
- Dates are `CarbonImmutable` app-wide — reassign results (`$d = $d->addDay()`).
- Jobs use named queues (`ai`, `analytics`, `emails`, `notifications`, `exports`, `default`) hard-coded in each job's constructor. `AppServiceProvider` re-registers the dev `queue` command with `--queue=default,notifications,emails,analytics,ai,exports`, so `composer dev` works every queue. Production workers still need the same list.
- **Suspected issue (dead config):** `config/datenight.php` is never read — no `config('datenight...')` call exists. Queue names, cache TTLs, thresholds etc. are hard-coded elsewhere.
- **Suspected issue (dead code):** `AppServiceProvider.php:23` binds `App\Contracts\AIProvider` to `App\AI\NullAIProvider`; nothing else references that contract (the live one is `App\Contracts\AI\AIProvider`).
- **Suspected issue:** `EvaluateStreaksCommand.php:16` injects `UpdateStreakAction` and never uses it.
- **Proxies**: `config/trustedproxy.php` reads `TRUSTED_PROXIES` (addresses or `*`); the framework's `TrustProxies` middleware picks it up, so nothing is set in `bootstrap/app.php`.
- **SSR** is off by default (`INERTIA_SSR_ENABLED`, `config/inertia.php`). Turning it on needs `npm run build:ssr` and the `inertia:start-ssr` process.
- **Deploying**: `docs/DEPLOY.md` is the checklist; `.env.production.example` lists every production setting.
- `datenight:evaluate-streaks` runs hourly (at :05), because a day ends at a different hour for each user's timezone.
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
- 2026-11-24 — Dev queue worker now listens to all six queues (re-registered in `AppServiceProvider`).
- 2026-11-24 — Roadmap Phase 0: named rate limiters `invites` and `ai`, `EnsureUserIsNotSuspended` in the web stack, `DatabaseSeeder` is reference data only with `DemoCoupleSeeder` for local.
- 2026-11-24 — Roadmap Phase 2 (2.13, 2.16): `RuntimeSettings` reader, `EnsureNotInMaintenance` in the web stack, command `datenight:sync-prompts`.
- 2026-11-25 — Roadmap Phase 3 (3.4, 3.14): `filesystems.uploads` / `UPLOADS_DISK`, command `datenight:move-uploads`, `User::today()` for per-user dates.
- 2026-11-25 — Roadmap Phase 3 (3.3, 3.5): Inertia error page for 403/404/500/503 and a flash redirect for 419 in `bootstrap/app.php`; `config('app.legal.*')` (`LEGAL_OPERATOR`, `LEGAL_CONTACT_EMAIL`); `/privacy` and `/terms` pass `EnsureNotInMaintenance`. Test: `ErrorPageTest`.
- 2026-11-25 — Roadmap Phase 3 (3.1, 3.2, 3.7, 3.8, 3.12, 3.13, 3.14): deploy checklist and production env example, `TRUSTED_PROXIES`, starter-kit installer removed, one checking CI workflow, `/health` throttled with an optional token, SSR off by default, `opcodesio/log-viewer` pinned, dependencies updated, streak evaluation hourly.
- 2026-11-25 — Roadmap Phase 3 (3.9): `vendor/bin/phpstan analyse` is clean at level 7 with no baseline; every model relation has a generic `@return` type.
- 2026-11-25 — Roadmap 3.10: browser tests with Playwright (`npm run test:e2e`), run against a throwaway SQLite database.
