# Deploying Date Night

Everything a fresh environment needs, in order. `.env.production.example` in the project root lists every setting with a note; copy it to `.env` on the server and fill it in.

## 1. What the server needs

- PHP 8.5 with the usual Laravel extensions, Composer 2, Node 22 (build step only).
- A database. Development and the test suite use SQLite; **the migrations have not been run against MySQL or PostgreSQL yet**, so run them on a copy first.
- A process manager for the queue worker (Supervisor, systemd, or the host's own) and one cron entry.
- HTTPS. Behind a load balancer or proxy, set `TRUSTED_PROXIES` (see below).

## 2. First deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.production.example .env      # then fill it in
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force          # reference data: roles, questionnaires, challenges, prompts
php artisan optimize
```

Make the first super administrator after registering that account through the site:

```bash
php artisan tinker --execute 'App\Models\User::where("email", "you@example.com")->firstOrFail()->forceFill(["role_id" => App\Models\Role::where("name", App\Enums\UserRole::SuperAdministrator->value)->value("id")])->save();'
```

## 3. Every later deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan datenight:sync-prompts   # adds new AI prompt versions, never overwrites an edited one
php artisan optimize
php artisan queue:restart
```

Upgrading an installation from before November 2026: run `php artisan datenight:move-uploads` once. It moves moment photos off the public disk; it is safe to run again. After changing `UPLOADS_DISK` later, run it with `--from=<old disk>`.

Do **not** re-run `db:seed` on a live database: the "Plan a Date Out" seeder recreates its questions, which removes existing answers to that questionnaire.

## 4. Processes that must be running

**Queue worker.** Jobs use six queues. A worker started without the list only works `default`, and then notifications, emails, achievements and AI plans never run:

```bash
php artisan queue:work --queue=default,notifications,emails,analytics,ai,exports --tries=3 --timeout=120
```

**Scheduler.** One cron entry; six commands depend on it (daily challenge, streaks, invitation expiry, reminders, calendar reminders, export clean-up):

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

**Server-side rendering** is off (`INERTIA_SSR_ENABLED=false`). The app sits behind a login, so it gains little. To turn it on, build with `npm run build:ssr`, run `php artisan inertia:start-ssr` under the process manager, and set the flag to `true`.

## 5. Settings that bite if missed

| Setting | Why |
|---|---|
| `APP_ENV=production`, `APP_DEBUG=false` | Debug pages show code and settings. Production also enforces the strong password rule. |
| `APP_URL=https://…` | Links in queued emails are built from it. |
| `TRUSTED_PROXIES` | Behind a proxy, without it every visitor shares one IP address (login and invite limits) and links come out as `http`. Use the proxy's address, or `*` when only the proxy can reach the app. |
| `MAIL_MAILER`, `MAIL_FROM_ADDRESS` | The default mailer is `log`: password resets and invitations would go to a log file. Resend is installed (`MAIL_MAILER=resend`, `RESEND_API_KEY`). The sending domain must be verified with the provider. |
| `SESSION_SECURE_COOKIE=true` | Keeps the session cookie on HTTPS. |
| `ANTHROPIC_API_KEY` | Without a key the app still works and uses its built-in (non-AI) plans. `AI_PROVIDER=null` switches AI off deliberately. |
| `UPLOADS_DISK` | Where moment photos and admin media go. The default `local` is the private disk (`storage/app/private`); photos are served through the app to the owner and their partner only, so `storage:link` is not needed. On hosts that wipe the disk on deploy, point it at S3-compatible storage (needs `composer require league/flysystem-aws-s3-v3`). |
| `HEALTH_TOKEN` | When set, `GET /health` answers only to `Authorization: Bearer <token>`. `/up` stays open for load balancers. |
| `LEGAL_OPERATOR`, `LEGAL_CONTACT_EMAIL` | Shown in the privacy policy and terms. The texts are drafts: have them reviewed before launch. |

## 6. Backups

Nothing in the app takes backups; set these up on the host.

- **Database:** a nightly dump (`mysqldump --single-transaction`, `pg_dump`, or the managed database's snapshots), kept off the server, for at least 14 days. Test a restore once.
- **Uploads:** moment photos and admin media live on `UPLOADS_DISK`. On S3-compatible storage turn on bucket versioning; on a local disk include `storage/app` in the server backup.
- **`.env`:** keep a copy of `APP_KEY` somewhere safe. Without it, encrypted data (two-factor secrets) and existing sessions cannot be read.

## 7. After deploying, check

- `GET /up` returns 200, and `GET /health` (with the token) reports every check `ok`.
- Register an account: the verification email arrives and its link is `https`.
- Finish a questionnaire: the plan appears, which proves the queue worker is on the right queues.
- `/log-viewer` opens for the super administrator only.

## Local development

`composer dev` runs the server, queue worker and Vite together. The project folder sits inside OneDrive, which syncs and virus-scans every file read, so the first page load after starting is slow; moving the project outside OneDrive (for example `C:\Dev`) removes that.
