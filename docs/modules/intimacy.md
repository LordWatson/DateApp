# Intimacy
> Last verified against code: 2026-11-25

## Purpose
An adult-themed corner of the app: a landing page that lists intimacy questionnaires and a catalog of couple games, plus a game page that shows the rules and deals prompt cards. The cards are written by the AI for the game being played, ten at a time; the cards stored with the game are the fallback. Nothing is saved on the server; the undrawn part of the deck is kept in the browser.

## Key files
| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/IntimacyController.php` | `index` (questionnaires + games), `show` (one game), `cards` (JSON: a fresh AI deck) |
| `app/Actions/GenerateIntimacyCardsAction.php` | One AI call for a deck of `DECK_SIZE` (10) cards from the game's own details; cleans the answer; empty list on any failure |
| `app/Http/Requests/Intimacy/GenerateIntimacyCardsRequest.php` | `seen`: up to 40 strings of 300 characters |
| `app/Models/IntimacyGame.php` | Model, casts, `scopeActive()` |
| `app/Enums/IntimacyGameIntensity.php` | `flirty`, `spicy`, `wild` with `label()` and `emoji()` |
| `app/Enums/IntimacyGameCategory.php` | `game`, `dare`, `roleplay`, `truth` with `label()` |
| `database/migrations/2026_07_19_143700_create_intimacy_module_tables.php` | Adds `questionnaires.is_intimacy`, creates `intimacy_games` |
| `database/seeders/IntimacyGameSeeder.php` | 10 games, `firstOrCreate` by `slug` |
| `database/seeders/IntimacyQuestionnaireSeeder.php` | 3 active, public questionnaires with `is_intimacy = true` (slugs `intimacy-tonights-mood`, `intimacy-desires-and-boundaries`, `intimacy-after-dark-preferences`), `firstOrCreate` by `slug` |
| **Frontend** | |
| `resources/js/pages/intimacy/Index.vue` | Questionnaire links, game grid, client-side intensity filter |
| `resources/js/pages/intimacy/Game.vue` | Game details; fetches and deals the AI deck, falls back to the stored cards |
| `resources/js/pages/relationship-hub/Index.vue` | Entry point: "Intimacy" card linking to `/intimacy` |
| **Tests** | |
| `tests/Feature/IntimacyTest.php` | Index, show, inactive 404, AI cards (context, cleaning, failure, inactive), seeders |

## Data model
- **`intimacy_games`**: `title`, `slug` (unique, route key), `emoji`, `tagline`, `description`, `how_to_play`, `players` (default 2), `estimated_minutes`, `intensity` (string, default `flirty`), `category` (string, default `game`), `prompts` (json list of strings: the fallback deck, and the style examples sent to the AI), `display_order`, `is_active` (default true). Indexes on `is_active`, `display_order`, `intensity`.
- Casts: `intensity` -> `IntimacyGameIntensity`, `category` -> `IntimacyGameCategory`, `prompts` -> array, `is_active` -> boolean. No relationships, no factory.
- **`questionnaires.is_intimacy`** boolean (default false, indexed). Intimacy questionnaires are ordinary questionnaires; answering, pairing and plan generation are handled by the questionnaire module.

## Routes
Under middleware `auth`, `verified`, `onboarding` (`routes/web.php`).

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `intimacy.index` | GET `/intimacy` | `IntimacyController@index` | Active intimacy questionnaires + active games, both by `display_order` |
| `intimacy.games.show` | GET `/intimacy/games/{intimacyGame:slug}` | `IntimacyController@show` | 404 if `is_active` is false |
| `intimacy.games.cards` | POST `/intimacy/games/{intimacyGame:slug}/cards` | `IntimacyController@cards` | JSON `{cards: string[]}`; `throttle:ai`; 404 if inactive; body `seen` (cards already dealt) |

## Flows
**Browse**
1. `index` returns `questionnaires` (`is_intimacy = true`, status `active`) and `games` (`active()` scope) with enum values, labels and emojis flattened into arrays.
2. `Index.vue` filters games by intensity in the browser (All / Flirty / Spicy / Wild). Questionnaire cards link to `/questionnaires/{slug}`; game cards to `/intimacy/games/{slug}`.

**Play a game**
1. `show` returns the full game including `how_to_play` and `prompts` (empty array if null). It does not wait for the AI.
2. When `Game.vue` mounts it posts to `intimacy.games.cards` with `fetch` (the players read the rules meanwhile). `GenerateIntimacyCardsAction` makes **one** AI call (`AIUseCase::IntimacyGame`) whose context is the game itself: `count`, `title`, `tagline`, `description`, `how_to_play`, `category`, `intensity`, `players`, up to five stored cards as `example_cards`, and `already_played`. The answer's `cards` are trimmed, and blanks, non-strings, cards over 300 characters, duplicates, stored cards and already-played cards are dropped.
3. The page deals from that deck. When 3 cards are left it asks for the next deck in the background, sending the cards dealt this visit plus those still undrawn as `seen`, so a draw only waits for the very first deck.
4. Fallback: if the answer is empty, an error, throttled, or takes over 12 seconds, the page plays the stored `prompts` (shuffled, reshuffled when used up) for the rest of the visit and says "Playing the classic deck". A game with no stored cards shows "Try again".
5. The undrawn cards are kept in `localStorage` under `intimacy-deck:{slug}` (a list of card texts), so a reload continues the deck without a new AI call.

No events, listeners, jobs, notifications or mail are involved.

## Rules and gotchas
- **No gating.** There is no feature flag, age check, opt-in or consent setting for this module: any onboarded, verified user (with or without a partner) can open it. `FeatureFlagSeeder` has no intimacy flag. The only consent wording is a static footer line in `Index.vue` (line 192).
- Game content is managed only through `IntimacyGameSeeder`; no admin controller or page references `IntimacyGame`. Because the seeder uses `firstOrCreate`, editing a seeded game's text in the seeder does not update existing rows.
- Intimacy questionnaires also appear in the main questionnaire list (`QuestionnaireController` filter `intimacy`, and under `all`; excluded from `couples`).
- `is_intimacy` is passed to the plan generator and its AI context (`app/Services/DateNightGeneratorService.php`); see [date-night.md](date-night.md) and [ai.md](ai.md).
- **One request for ten cards, not one per card.** Measured on 2026-11-25 with `claude-sonnet-5-5`: ten cards in one call took 3.5 to 4.1 s; one card took 0.9 to 1.1 s, so about 10 s for ten in a row, and separate calls cannot see each other's cards. `ANTHROPIC_EFFORT` low or medium made no difference. A slower model makes the first deck slower; the page waits at most 12 s.
- The `intimacy_game` template is version 3 and answers `{ "cards": [string] }`. A database without it keeps using version 2, whose answer has no `cards`, so every game silently plays the stored deck: run `php artisan datenight:sync-prompts`.
- The call passes no people (`$people = []`): the context is the game, nothing about the players, so it runs whatever their AI consent. It still obeys the `ai_generation` flag.
- Each deck costs one request against the shared `ai` rate limit (20 an hour per user, also used by plans and the weekly reflection). A throttled request falls back to the stored cards.
- Generated cards are not stored or logged apart from the usual `ai_generations` row (subject = the game, `user_id` = the player).
- `seen` is the player's own text placed in the prompt context as data; it can only affect the cards that same player gets back.
- The migration stores `intensity`/`category` as plain strings; a value outside the enum will throw when the model is read.
- Intensity badge classes are duplicated in `Index.vue` and `Game.vue`; adding an intensity means touching the enum and both pages.

## Related modules
- [questionnaires.md](questionnaires.md) - how intimacy questionnaires are answered and listed
- [date-night.md](date-night.md), [ai.md](ai.md) - consumers of `is_intimacy`; `ai.md` for the `intimacy_game` use case
- [dashboard-insights-search.md](dashboard-insights-search.md) - Relationship Hub entry card
- [admin.md](admin.md) - feature flags (none apply here)

## Tests
- `tests/Feature/IntimacyTest.php` - index lists intimacy questionnaires and games, game show, inactive game 404, AI cards come from one request built from the game and are cleaned, an AI failure gives an empty list, no cards for an inactive game, both seeders run.
- Not covered: the page's own fallback, refill and timeout (no JS tests).
- Run: `php artisan test --filter=IntimacyTest`

## Change log
- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 2 (2.15): cards are drawn from a shuffled deck without repeats, with progress, and the deck survives a reload; the `intimacy_game` prompt template (v2) now asks for `{ "prompt": string }`.
- 2026-11-26 — Roadmap Phase 4: `SummariseCoupleAnswersAction` is gone (only a deleted job used it).
- 2026-11-25 — Cards are written by AI: new `intimacy.games.cards` endpoint and `GenerateIntimacyCardsAction` (one request for ten cards), `intimacy_game` template v3, `Game.vue` fetches on open, refills in the background and falls back to the stored cards; the "Peek at all cards" list is gone.
