# Saved Profiles

> Last verified against code: 2026-11-26

## Purpose

Lets a user save the answers of a questionnaire attempt as a named profile (name, emoji, colour) and later apply that profile to pre-fill a questionnaire. Profiles are private to the user who created them.

## Key files

| Path | Role |
|---|---|
| **Backend** | |
| `app/Http/Controllers/SavedProfileController.php` | `index`, `store`, `update`, `destroy`, `apply` |
| `app/Services/QuestionnaireService.php` | `applyProfile()` writes profile answers into a response; `getResponseWithAnswers()` picks the source response for `store` |
| `app/Models/SavedProfile.php`, `app/Models/SavedProfileAnswer.php` | Models |
| `app/Policies/SavedProfilePolicy.php` | `view` / `update` / `delete`: owner only |
| `app/Http/Requests/SavedProfile/StoreSavedProfileRequest.php` | Validation for `store` |
| `app/Http/Presenters/QuestionnairePresenter.php` | `savedProfiles()` prop shape (id, name, emoji, colour) |
| `database/factories/SavedProfileFactory.php`, `SavedProfileAnswerFactory.php` | Factories |
| **Frontend** | |
| `resources/js/pages/saved-profiles/Index.vue` | Manage page: list, rename and restyle (dialog), delete (`ConfirmDialog`) |
| `resources/js/pages/questionnaires/Summary.vue` | "Save as Favourite Profile" modal -> POST `/saved-profiles` |
| `resources/js/pages/questionnaires/Show.vue` | "Use Saved Profile" picker -> POST `/saved-profiles/{id}/apply/{slug}` |
| `resources/js/pages/Dashboard.vue` | List (`savedProfiles` prop from `DashboardController`); rows and the "Manage" link go to `/saved-profiles` |
| `resources/js/pages/search/Index.vue` | Search results group (`GlobalSearchService::savedProfiles`) |
| **Tests** | `tests/Feature/SavedProfileTest.php`, `tests/Feature/SavedProfilePageTest.php`, plus three tests in `tests/Feature/QuestionnaireFlowTest.php` |

## Data model

| Table | Columns | Notes |
|---|---|---|
| `saved_profiles` | `user_id` (cascade), `name`, `emoji` (nullable), `colour` (nullable), timestamps | `User::savedProfiles()` hasMany |
| `saved_profile_answers` | `saved_profile_id` (cascade), `question_id` (cascade), `question_option_id` (nullable, null on delete), `value` (text, nullable), timestamps | Same shape as `answers`; one row per answer row (multiple choice = several rows) |

A profile has no `questionnaire_id`; the link to a questionnaire exists only through each answer's `question_id`.

## Routes

`routes/web.php`, middleware `auth`, `verified`, `onboarding`; prefix `saved-profiles`, name prefix `saved-profiles.`.

| Name | Method + URI | Controller@method | Notes |
|---|---|---|---|
| `index` | GET `/` | `index` | Inertia page `saved-profiles/Index`; prop `profiles` (`id`, `name`, `emoji`, `colour`, `answer_count`), the user's own only |
| `store` | POST `/` | `store` | JSON; `StoreSavedProfileRequest` |
| `update` | PUT `/{savedProfile}` | `update` | JSON; policy `update` |
| `destroy` | DELETE `/{savedProfile}` | `destroy` | JSON; policy `delete` |
| `apply` | POST `/{savedProfile}/apply/{questionnaireSlug}` | `apply` | JSON; policy `view` |

## Flows

**Save**

1. On `Summary.vue` the user opens the modal and submits name, emoji, colour and `questionnaire_id` with `fetch`.
2. `store` loads the user's latest response for that questionnaire (any status). None: 422 `{error}`.
3. Creates the `SavedProfile` and copies every answer row of that response into `saved_profile_answers`.
4. Returns `{profile: {id, name, emoji, colour}}`. No event is fired.

**Apply**

1. On `Show.vue` the user picks a profile; the page posts to `apply`.
2. `apply` authorizes `view`, resolves the questionnaire by slug, and calls `findOrCreateResponse` (reuses the in-progress response or creates one).
3. `QuestionnaireService::applyProfile` groups profile answers by question and overwrites the response's answers for each of those questions using the normal per-type save logic.
4. Returns `{applied: true}`. No event is fired.

**Manage** (`saved-profiles/Index.vue`)

1. The page keeps a local copy of the `profiles` prop, because `update` and `destroy` answer with JSON and are called with `axios`.
2. Edit opens a dialog (name, one of eight emoji, one of six colours) and PUTs; a 422 shows the name error in the dialog.
3. Delete asks through `ConfirmDialog`, then DELETEs and drops the row locally. Both show a `vue-sonner` toast.

## Rules and gotchas

- Validation (`store` and `update`): `name` required, max 100; `emoji` max 10; `colour` max 20; `store` also requires `questionnaire_id` to exist.
- Authorization: `store` and `index` are scoped to the current user; `update`, `destroy`, `apply` use `Gate::authorize` with `SavedProfilePolicy`. Applying another user's profile returns 403 (tested).
- Deleting a question deletes the saved answers for it; deleting an option nulls `question_option_id` but keeps `value`.
- All endpoints except `index` return JSON, not Inertia responses.
- A saved profile in global search links to the manage page (`GlobalSearchService::savedProfiles`).
- A profile saved without an emoji or colour stores `null` (the manage page shows a default tile).
- Suspected issue: `Show.vue` calls `apply` with Inertia `router.post`, but the endpoint returns plain JSON, so the `onSuccess` callback (close picker, start questionnaire) is not expected to run. `resources/js/pages/questionnaires/Show.vue:107-118`.
- Suspected issue: `applyProfile` does not check that the profile's questions belong to the target questionnaire; applying a profile saved from questionnaire A to questionnaire B writes answers for A's questions onto B's response. `app/Services/QuestionnaireService.php:146-165`.
- Suspected issue: `Summary.vue` `saveProfile` closes the modal without checking the response status, and the page's `saved_profiles` prop is not refreshed. `resources/js/pages/questionnaires/Summary.vue:402-429`.

## Related modules

- `questionnaires.md` — response/answer storage, per-type save rules, and the pages that host the UI.
- `dashboard-insights-search.md` — dashboard list and global search results.

## Tests

- `tests/Feature/SavedProfileTest.php` — model relations and cascade deletes only (no HTTP).
- `tests/Feature/SavedProfilePageTest.php` — the page shows only the user's profiles with answer counts; rename, validation and delete; owner-only (403); saving without emoji or colour stores null.
- `tests/Feature/QuestionnaireFlowTest.php` — `test_user_can_save_a_profile`, `test_applying_saved_profile_populates_answers`, `test_user_cannot_apply_another_users_profile`.

```
php artisan test --filter=SavedProfileTest
php artisan test --filter=SavedProfilePageTest
php artisan test --filter=QuestionnaireFlowTest
```

## Change log

- 2026-11-24 — Initial doc.
- 2026-11-24 — Roadmap Phase 2 (2.1): manage page `saved-profiles/Index` (list, rename, restyle, delete); dashboard rows and a "Manage" link go to it; a missing emoji or colour is stored as null.
- 2026-11-26 — Roadmap Phase 4: removed the never-dispatched `SavedProfileCreated` and `SavedProfileApplied` events.
