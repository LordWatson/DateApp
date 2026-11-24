# Intimacy
> Last verified against code: 2026-11-24

## Purpose
An adult-themed corner of the app: a landing page that lists intimacy questionnaires and a catalog of couple games, plus a game page that shows the rules and draws random prompt cards. It is read-only; nothing the couple does in a game is stored.

## Key files
| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/IntimacyController.php` | `index` (questionnaires + games), `show` (one game) |
| `app/Models/IntimacyGame.php` | Model, casts, `scopeActive()` |
| `app/Enums/IntimacyGameIntensity.php` | `flirty`, `spicy`, `wild` with `label()` and `emoji()` |
| `app/Enums/IntimacyGameCategory.php` | `game`, `dare`, `roleplay`, `truth` with `label()` |
| `database/migrations/2026_07_19_143700_create_intimacy_module_tables.php` | Adds `questionnaires.is_intimacy`, creates `intimacy_games` |
| `database/seeders/IntimacyGameSeeder.php` | 10 games, `firstOrCreate` by `slug` |
| `database/seeders/IntimacyQuestionnaireSeeder.php` | 3 active, public questionnaires with `is_intimacy = true` (slugs `intimacy-tonights-mood`, `intimacy-desires-and-boundaries`, `intimacy-after-dark-preferences`), `firstOrCreate` by `slug` |
| **Frontend** | |
| `resources/js/pages/intimacy/Index.vue` | Questionnaire links, game grid, client-side intensity filter |
| `resources/js/pages/intimacy/Game.vue` | Game details, random prompt draw |
| `resources/js/pages/relationship-hub/Index.vue` | Entry point: "Intimacy" card linking to `/intimacy` |
| **Tests** | |
| `tests/Feature/IntimacyTest.php` | Index, show, inactive 404, seeders |

## Data model
- **`intimacy_games`**: `title`, `slug` (unique, route key), `emoji`, `tagline`, `description`, `how_to_play`, `players` (default 2), `estimated_minutes`, `intensity` (string, default `flirty`), `category` (string, default `game`), `prompts` (json list of strings), `display_order`, `is_active` (default true). Indexes on `is_active`, `display_order`, `intensity`.
- Casts: `intensity` -> `IntimacyGameIntensity`, `category` -> `IntimacyGameCategory`, `prompts` -> array, `is_active` -> boolean. No relationships, no factory.
- **`questionnaires.is_intimacy`** boolean (default false, indexed). Intimacy questionnaires are ordinary questionnaires; answering, pairing and plan generation are handled by the questionnaire module.

## Routes
Under middleware `auth`, `verified`, `onboarding` (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `intimacy.index` | GET `/intimacy` | `IntimacyController@index` | Active intimacy questionnaires + active games, both by `display_order` |
| `intimacy.games.show` | GET `/intimacy/games/{intimacyGame:slug}` | `IntimacyController@show` | 404 if `is_active` is false |

## Flows
**Browse**
1. `index` returns `questionnaires` (`is_intimacy = true`, status `active`) and `games` (`active()` scope) with enum values, labels and emojis flattened into arrays.
2. `Index.vue` filters games by intensity in the browser (All / Flirty / Spicy / Wild). Questionnaire cards link to `/questionnaires/{slug}`; game cards to `/intimacy/games/{slug}`.

**Play a game**
1. `show` returns the full game including `how_to_play` and `prompts` (empty array if null).
2. `Game.vue` picks a random prompt on each draw (`Math.random`, repeats possible). No request is sent and no state is persisted.

No events, listeners, jobs, notifications or mail are involved.

## Rules and gotchas
- **No gating.** There is no feature flag, age check, opt-in or consent setting for this module: any onboarded, verified user (with or without a partner) can open it. `FeatureFlagSeeder` has no intimacy flag. The only consent wording is a static footer line in `Index.vue` (line 192).
- Game content is managed only through `IntimacyGameSeeder`; no admin controller or page references `IntimacyGame`. Because the seeder uses `firstOrCreate`, editing a seeded game's text in the seeder does not update existing rows.
- Intimacy questionnaires also appear in the main questionnaire list (`QuestionnaireController` filter `intimacy`, and under `all`; excluded from `couples`).
- `is_intimacy` is passed to the plan generator and AI summary context (`app/Services/DateNightGeneratorService.php:215`, `app/Actions/SummariseCoupleAnswersAction.php:45`); see [date-night.md](date-night.md) and [ai.md](ai.md).
- `AIUseCase::IntimacyGame` (`intimacy_game`) exists in `app/Enums/AI/AIUseCase.php`, but this controller does not call the AI layer.
- The migration stores `intensity`/`category` as plain strings; a value outside the enum will throw when the model is read.
- Intensity badge classes are duplicated in `Index.vue` and `Game.vue`; adding an intensity means touching the enum and both pages.

## Related modules
- [questionnaires.md](questionnaires.md) - how intimacy questionnaires are answered and listed
- [date-night.md](date-night.md), [ai.md](ai.md) - consumers of `is_intimacy`
- [dashboard-insights-search.md](dashboard-insights-search.md) - Relationship Hub entry card
- [admin.md](admin.md) - feature flags (none apply here)

## Tests
- `tests/Feature/IntimacyTest.php` - index lists intimacy questionnaires and games, game show, inactive game 404, both seeders run.
- Run: `php artisan test --filter=IntimacyTest`

## Change log
- 2026-11-24 — Initial doc.
