# Love Notes

> Last verified against code: 2026-11-24

## Purpose

Short (max 200 characters) one-way messages from a user to their connected partner. The sender picks a preset message or types their own; the partner gets an in-app notification and an email, and reads the note in the app. Notes cannot be edited, deleted or replied to.

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/LoveNoteController.php` | `index`, `store`, `show`, `unreadCount`; preset messages in `defaultMessages()` |
| `app/Models/LoveNote.php` | Model; `sender()`, `recipient()`, `isRead()`, `markAsRead()` |
| `app/Actions/SendLoveNoteAction.php` | Creates the note and dispatches `LoveNoteSent` |
| `app/Http/Requests/LoveNote/SendLoveNoteRequest.php` | Validation |
| `app/Events/LoveNoteSent.php` | Carries sender, recipient, note |
| `app/Listeners/SendLoveNoteNotificationOnLoveNoteSent.php` | Queued (`notifications`); dispatches the job |
| `app/Jobs/SendLoveNoteNotificationJob.php` | Creates `AppNotification` + queues the mail |
| `app/Mail/LoveNoteReceivedMail.php`, `resources/views/emails/love-note-received.blade.php` | Email to recipient |
| `app/Listeners/LogActivityOnLoveNoteSent.php` | Sync; activity log `love_note_sent` |
| `database/migrations/2026_07_13_000003_create_love_notes_table.php` | Schema |
| `database/factories/LoveNoteFactory.php` | Factory (`read()` state) |
| **Frontend** | |
| `resources/js/pages/love-notes/Index.vue` | Tabs: send / received / sent |
| `resources/js/pages/love-notes/Show.vue` | Single note |
| **Tests** | |
| `tests/Feature/LoveNoteTest.php` | Feature tests |

## Data model

`love_notes`: `sender_id` (FK users, cascade), `recipient_id` (FK users, cascade), `message` (string 200), `read_at?` (cast `datetime`), timestamps. Indexes: `(recipient_id, read_at)`, `sender_id`.

There is no relation defined on `User`; code queries `LoveNote` by `sender_id` / `recipient_id` directly.

## Routes

All inside `auth`, `verified`, `onboarding` middleware (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `love-notes.index` | GET `/love-notes` | `LoveNoteController@index` | Latest 50 sent + latest 50 received; marks received as read |
| `love-notes.store` | POST `/love-notes` | `LoveNoteController@store` | `SendLoveNoteRequest` |
| `love-notes.unread-count` | GET `/love-notes/unread-count` | `LoveNoteController@unreadCount` | JSON `{count}`; declared before `/{loveNote}` on purpose |
| `love-notes.show` | GET `/love-notes/{loveNote}` | `LoveNoteController@show` | 403 unless sender or recipient; marks read for recipient |

## Flows

### Send a note

1. `love-notes/Index.vue` posts `{message}` to `/love-notes`.
2. `SendLoveNoteRequest` validates (`message` required, string, max 200).
3. Controller checks `$user->partner`; without one it returns `back()` with error key `partner`.
4. `SendLoveNoteAction::execute($sender, $partner, $message)` creates the row and dispatches `LoveNoteSent`.
5. Listeners:
   - `SendLoveNoteNotificationOnLoveNoteSent` (queued) -> `SendLoveNoteNotificationJob` (`notifications` queue, 3 tries, 30 s) -> `AppNotification` of type `love_note_received` with `data.love_note_id` + queued `LoveNoteReceivedMail`.
   - `LogActivityOnLoveNoteSent` (sync) -> activity log with `recipient_id`.
6. Redirect back with flash `success`; the page switches to the "sent" tab.

### Read

1. Opening `love-notes.index` marks the loaded received notes (latest 50) as read.
2. Opening `love-notes.show` as the recipient marks that note as read. The sender viewing it does not.

## Rules and gotchas

- **Who can see**: only the sender and the recipient of each note.
  - `index` filters by `sender_id` / `recipient_id` = current user.
  - `show` aborts with 403 otherwise (`app/Http/Controllers/LoveNoteController.php:68`). This is an inline check; there is no `LoveNotePolicy`.
  - Nobody can edit or delete a note: there are no update/destroy routes.
- The recipient is always the sender's **current** partner (`$user->partner`); the client cannot choose a recipient.
- Notes are keyed by user ids, not by couple. After a partner disconnect (or reconnect with someone else) old notes remain visible to both original parties.
- The email does not contain the message text, only a link to `love-notes.index` (`LoveNoteReceivedMail` passes names and URL only).
- The email is sent regardless of the recipient's `email_notifications` setting (the job does not check it).
- Notification and email need a queue worker (queued listener -> queued job -> queued mailable). Tests run with `QUEUE_CONNECTION=sync`, which is why `LoveNoteTest` can assert the notification row directly.
- Preset messages are hard-coded in `LoveNoteController::defaultMessages()` (8 strings), not stored in the database.
- `unreadCount` returns JSON, not an Inertia response.
- Suspected issue: sending a note does not trigger achievement evaluation (no `EvaluateAchievements...` listener on `LoveNoteSent`), so `love_notes_sent` achievements unlock only on the next questionnaire completion or moment creation. See challenges-achievements-streaks.md.
- Suspected issue: `index` marks notes read with one `UPDATE` per unread note (`$received->each->markAsRead()`, `LoveNoteController.php:36`), and a GET request mutates state, so any prefetch of the page clears unread status.
- Suspected issue: received notes beyond the latest 50 are never listed and therefore never marked read via `index`, so `unreadCount` could stay above zero.
- The frontend posts to hard-coded URLs (`'/love-notes'`, `'/partner'`) instead of Wayfinder route helpers.

## Related modules

- notifications-activity-mail.md - `AppNotification`, `NotificationType::LoveNoteReceived`, activity log, mail.
- events-jobs.md - listener registration and queues.
- onboarding-partner.md - partner connection required to send.
- challenges-achievements-streaks.md - `love_notes_sent` achievements.
- moments-timeline.md - sent and received notes appear in the timeline.
- dashboard-insights-search.md, weekly-reflection.md - read love note counts.

## Tests

- `tests/Feature/LoveNoteTest.php` - page loads, send, notification created, required / max 200 validation, mark-as-read on index, no-partner error, sent/received tabs.

Run: `php artisan test --filter=LoveNoteTest`

Not covered: `show` authorisation (403 for third parties), `unreadCount`, mail content.

## Change log

- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 1: notes are marked read by `POST /love-notes/mark-read` when the Received tab opens (not on page load); the tab shows the unread count.
