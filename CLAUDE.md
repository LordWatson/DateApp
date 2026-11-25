# DateNight

A couples app: partners answer questionnaires, the app generates date night plans (rules + AI), and tracks moments, love notes, calendar, challenges, achievements and insights. Laravel 13, PHP 8.5, Inertia 3, Vue 3 + TypeScript, Tailwind 4, Fortify auth, PHPUnit.

## Module docs — read first, update after

Module docs live in `docs/modules/`. This is a standing instruction from the project owner and overrides the "only create documentation if asked" rule in the Boost guidelines below.

1. **Before starting a task**, open `docs/modules/README.md`, pick the doc(s) for the module you will touch, and read them. Read `events-jobs.md` too if the task involves events, listeners or jobs, `ai.md` if it calls the AI, and `frontend.md` for any page work.
2. **After completing a task**, update every doc the change affected, following the "Updating a doc after a task" steps in `docs/modules/README.md` (fix the touched sections, remove fixed "Suspected issue" entries, bump the verified date, add a change-log line). A new module gets a new doc from the template plus a row in the index.
3. If a doc and the code disagree, the code is right — fix the doc as part of the task.

## Commands

Run PHP and artisan through PowerShell; `php` is not on the Git Bash PATH on this machine.

| Task | Command |
|------|---------|
| Run everything locally | `composer dev` (server, queue, Vite) |
| Tests (all / one) | `php artisan test` / `php artisan test --filter=NameTest` |
| PHP format | `vendor/bin/pint --dirty` |
| PHP static analysis | `vendor/bin/phpstan analyse` |
| JS lint / format / types | `npm run lint` / `npm run format` / `npm run types:check` |
| Full CI check | `composer ci:check` |

## How the code is organised

- **Request path:** thin controller → FormRequest → Action (single class, `execute()`) → Service for domain logic → Presenter (`app/Http/Presenters`) or a hand-built array for Inertia props. The classes in `app/Http/Resources` are mostly unused; do not assume a page uses them.
- **Side effects:** action dispatches an Event → listener named `<Verb>On<Event>` with a `public string $queue` → Job (`onQueue()`, `$tries = 3`, logging `failed()`) → queued Mailable.
- **Event wiring is manual.** Discovery is off; every listener must be added to `$listen` in `app/Providers/EventServiceProvider.php`.
- **Scheduled commands** are defined in `bootstrap/app.php` (`withSchedule`), not `routes/console.php`.
- **Routes:** `routes/web.php` (user), `routes/settings.php`, `routes/admin.php`. User routes sit under `auth` + `verified` + `onboarding`.
- **Couples:** there is no couple table. A couple is two `users` rows pointing at each other through `partner_id`; always write both sides. Records belong to one `user_id`; the partner can usually view, only the owner edits or deletes. "Solo" is a flag on questionnaires and plans, not on users.
- **Names:** `display_name` is nullable — use `display_name ?? name`.
- **AI is optional everywhere:** build the deterministic result first, wrap the AI call in `try/catch (Throwable)`, check `$response->successful`. Prompts live in the `ai_prompt_templates` table, seeded from `database/seeders/AiPromptTemplateSeeder.php`.
- **Models:** `#[Fillable]` / `#[Hidden]` attributes and a `casts()` method. Dates are `CarbonImmutable` globally. Enums are string-backed; compare against enum cases, not string literals.
- **Frontend:** pages in `resources/js/pages/<feature>/`, most set `defineOptions({ layout: MobileLayout })`. Call routes with the Wayfinder helpers (`@/routes/...`, `@/actions/...`), which are generated and git-ignored. Endpoints that return JSON must be called with `axios`/`fetch`, not Inertia's `router`.
- **Tests:** PHPUnit classes with `test_*` methods and `RefreshDatabase`, not Pest. Tests run with sync queue, in-memory SQLite, and array mail and cache. Run `npm run build` before the suite if page tests fail on a missing Vite manifest. `php artisan test` prints JSON (laravel/pao).

## Traps

- **Queues:** jobs use six queues (`default`, `notifications`, `emails`, `analytics`, `ai`, `exports`). `composer dev` works all of them (the worker is re-registered in `AppServiceProvider`); a worker started by hand or in production needs `--queue=default,notifications,emails,analytics,ai,exports` or only `default` runs. Tests hide this because they use the sync queue.
- **`ziggy-js` `route()` is not set up.** The server package is not installed and there is no `@routes` directive, so do not copy that pattern from the pages that still import it; use Wayfinder.
- **Deploying:** `docs/DEPLOY.md` is the checklist and `.env.production.example` the settings. Browser tests: `npm run test:e2e`.
- **Cached routes and events:** `bootstrap/cache/` can hold a cached route and event list locally. If a new route 404s or a new listener does not fire, run `php artisan optimize:clear`.
- **Windows:** no `pcntl`, so `composer dev` leaves out the Pail log tailer (see `AppServiceProvider`). Use `/log-viewer` (super admins only).
- **Unused code is common:** many events are never dispatched, several jobs are only referenced by tests, and feature flags are not checked anywhere outside admin. Check the module doc's "Rules and gotchas" before building on something.

## Laravel Boost

Boost provides the `laravel-boost` MCP server (`.mcp.json`), skills in `.claude/skills/`, and the guidelines imported below. `AGENTS.md` is git-ignored; regenerate it with `php artisan boost:install --guidelines --skills --mcp --no-interaction`.

@AGENTS.md
