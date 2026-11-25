# Mobile app (Capacitor) and push notifications
> Last verified against code: 2026-11-25

## Purpose
The Android app is a Capacitor shell: a WebView that loads the live Laravel site (`server.url`), plus the push plugin. Every in-app notification is also sent to the recipient's phones through Firebase Cloud Messaging (FCM).

## Key files
| Path | Role |
|---|---|
| `capacitor.config.ts` | App id `com.datenight.app`; `server.url` comes from the `CAP_SERVER_URL` env var and is required |
| `resources/capacitor/index.html` | Placeholder `webDir`; never shown when `server.url` is set |
| `android/` | Native project made by `npx cap add android` (committed). `android/app/google-services.json` is the Firebase client config |
| `resources/js/lib/push.ts` | Inside the app only: asks permission, posts the FCM token, shows a toast for a push received while open, opens `/notifications` on tap |
| `app/Models/AppNotification.php` | `created` hook dispatches `SendPushNotificationJob` |
| `app/Jobs/SendPushNotificationJob.php` | Queue `notifications`; sends to each of the user's tokens, deletes tokens FCM rejects |
| `app/Services/FcmService.php` | FCM HTTP v1 client; signs its own OAuth token request with the service account key (no SDK) |
| `app/Http/Controllers/DeviceTokenController.php` | Stores a token |
| `app/Listeners/DeleteDeviceTokensOnLogout.php` | Deletes all of a user's tokens when they sign out |

## Data model
- **`device_tokens`**: `user_id` (cascade delete), `token` (unique, 512), `platform` (`android` / `ios`). A token belongs to one phone and moves to whoever last signed in on it.
- `users.push_notifications` (default true) switches push off for a user. There is no settings toggle for it yet.

## Routes
| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `device-tokens.store` | POST `/device-tokens` | `DeviceTokenController@store` | `auth` only (works before onboarding), throttled 30/min, returns 204; call with `axios` |

## Flows
**Registering a phone**: `push.ts` waits for an Inertia `navigate` with a signed-in user, requests permission, calls `PushNotifications.register()`, then posts the token. Runs on every app launch.

**Sending**: any `AppNotification::create()` dispatches `SendPushNotificationJob`. It is skipped if FCM is not configured or the user has `push_notifications` off; otherwise one FCM call per token with the notification's `title` and `body`.

## Setup
- **Firebase project**: `datenight-push-7c41` (Android app `com.datenight.app`).
- **Server**: put a service account key (Firebase console, Project settings, Service accounts, Generate new private key) at `storage/app/firebase-credentials.json` and set `FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json`. Without it push is silently skipped.
- **Build the app**: needs JDK 21 and the Android SDK. Set `CAP_SERVER_URL` (the production URL, or `http://10.0.2.2:8000` for the emulator with `php artisan serve`), then `npx cap sync android` and `npx cap run android` (or `npx cap open android`).

- **Run on the emulator while `composer dev` is running**: the emulator cannot reach the Vite dev server, so start a second server that serves the built assets (`npm run build` first): `$env:VITE_HOT_FILE = 'none'; php artisan serve --host 0.0.0.0 --port 8001 --no-reload`, with `CAP_SERVER_URL=http://10.0.2.2:8001`. `VITE_HOT_FILE` is read through `config('app.vite_hot_file')` in `AppServiceProvider::boot()`; `--no-reload` is what lets the variable reach the server.
- **On this machine** the JDK is at `%LOCALAPPDATA%\Programs\jdk\jdk-21.0.13.0.2+1` (set `JAVA_HOME`) and the Android SDK and emulator (AVD `alima`) are under `%LOCALAPPDATA%\Packages\Claude_pzs8sxrjxfjjc\LocalCache\Local\Android\Sdk`. `android/local.properties` (git-ignored) points Gradle at the SDK.

## Rules and gotchas
- Do not add push calls next to `AppNotification::create()`; the model hook already sends one.
- The job runs on the `notifications` queue: see the worker warning in [events-jobs.md](events-jobs.md).
- `CAP_SERVER_URL` is baked into the app at `cap sync`. `android/app/src/main/assets/capacitor.config.json` is generated and git-ignored.
- Tests set `FIREBASE_CREDENTIALS` to empty (`phpunit.xml`) so the suite never calls FCM.
- No iOS project: it needs a Mac, an APNs key uploaded to Firebase, and `npx cap add ios`.
- Tapping a push always opens `/notifications`, not the item itself.
- Signing out on one phone removes the user's tokens for all their phones; the others register again when next opened.

## Related modules
- [notifications-activity-mail.md](notifications-activity-mail.md) - what creates notifications
- [frontend.md](frontend.md), [platform.md](platform.md)

## Tests
- `tests/Feature/PushNotificationTest.php` - `php artisan test --filter=PushNotificationTest`. `FcmService` is mocked; the real FCM call has no test.

## Change log
- 2026-11-25 — Initial doc.
