# Module docs

One doc per module. Read the relevant doc(s) before changing a module; update them when the task is done. If a doc and the code disagree, the code is right — fix the doc.

## Index

| Doc | Covers |
|-----|--------|
| [auth-settings.md](auth-settings.md) | Register, login, password reset, email verification, 2FA, passkeys, roles and gates, settings pages |
| [onboarding-partner.md](onboarding-partner.md) | Onboarding steps, partner invitations, connecting and disconnecting partners |
| [questionnaires.md](questionnaires.md) | Questionnaires, questions, options, responses, answers, compatibility, summaries |
| [saved-profiles.md](saved-profiles.md) | Saved answer profiles and applying them to a questionnaire |
| [date-night.md](date-night.md) | Date night plan generation (rules + AI), themes, favourites, likes, PDF export |
| [ai.md](ai.md) | AI providers, AIService, prompt templates, generation logging, settings |
| [weekly-reflection.md](weekly-reflection.md) | AI weekly recap |
| [challenges-achievements-streaks.md](challenges-achievements-streaks.md) | Daily challenges, achievements, streaks |
| [moments-timeline.md](moments-timeline.md) | Moments (memories) and the relationship timeline |
| [calendar.md](calendar.md) | Calendar events |
| [love-notes.md](love-notes.md) | Love notes between partners |
| [notifications-activity-mail.md](notifications-activity-mail.md) | In-app notifications, activity log, outbound email |
| [events-jobs.md](events-jobs.md) | Catalog of every event, listener and queued job |
| [dashboard-insights-search.md](dashboard-insights-search.md) | Dashboard, relationship hub, insights, global search |
| [intimacy.md](intimacy.md) | Intimacy games and questionnaires |
| [admin.md](admin.md) | Admin panel, feature flags, system settings, audit log, log viewer |
| [mobile-push.md](mobile-push.md) | Capacitor Android app, device tokens, FCM push notifications |
| [frontend.md](frontend.md) | Vue/Inertia structure, layouts, shared props, UI kit, Wayfinder, styling |
| [platform.md](platform.md) | Bootstrap, providers, config, console commands and schedule, tooling, tests, CI, local setup |

## Which doc do I need?

- Working on a page in `resources/js/pages/<dir>/` → the doc for that feature, plus `frontend.md`.
- Adding or changing an event, listener or job → the feature doc, plus `events-jobs.md`.
- Anything that calls the AI → the feature doc, plus `ai.md`.
- Anything under `app/Http/Controllers/Admin` or `routes/admin.php` → `admin.md`.

## Updating a doc after a task

1. Fix every section the change touched: Key files, Data model, Routes, Flows, Rules and gotchas, Tests.
2. Remove a "Suspected issue" once it is fixed.
3. Set `> Last verified against code:` to today's date.
4. Add one line to the Change log: `- YYYY-MM-DD — what changed`.
5. New module → copy the template below into a new file and add a row to the index above.

Keep docs short: tables and bullets, facts checked against the code, no history outside the Change log.

## Template

```markdown
# <Module name>
> Last verified against code: YYYY-MM-DD
## Purpose
## Key files
| Path | Role |
## Data model
## Routes
| Name | Method + URI | Controller@method | Notes |
## Flows
## Rules and gotchas
## Related modules
## Tests
## Change log
- YYYY-MM-DD — Initial doc.
```
