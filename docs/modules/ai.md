# AI Layer
> Last verified against code: 2026-11-26

## Purpose
A thin, provider-agnostic layer for text generation. Features call `AIService` with a use case and a context array; the service loads the prompt template from the database, calls the configured provider (Anthropic Claude by default; DeepSeek and a no-op provider are kept and selectable), returns a typed `AIResponse` and records one analytics row per call. AI is always optional: every caller has a deterministic fallback.

## Key files

| Path | Role |
|------|------|
| **Backend (in use)** | |
| `app/Contracts/AI/AIProvider.php` | The provider contract in use: `complete(AIRequest): AIResponse`, `name(): string` |
| `app/Services/AI/AIService.php` | Orchestrator: template lookup, placeholder interpolation, request building, logging, analytics |
| `app/Services/AI/AnthropicProvider.php` | HTTP adapter for Claude's Messages API (`/v1/messages`). The default provider |
| `app/Services/AI/DeepSeekProvider.php` | HTTP adapter for DeepSeek's OpenAI-compatible `/chat/completions`. Kept as an alternative (`AI_PROVIDER=deepseek`) |
| `app/Services/AI/NullAIProvider.php` | No-network provider returning canned data (`AI_PROVIDER=null`) |
| `app/Services/AI/AISettingsService.php` | Reads and writes admin overrides in `system_settings`, overlays them on config at boot |
| `app/Services/AI/AIAnalyticsRecorder.php` | Writes `ai_generations` rows; swallows and logs its own failures |
| `app/DTOs/AI/AIRequest.php`, `app/DTOs/AI/AIResponse.php` | Immutable DTOs. `AIResponse::success()` / `::failure()` |
| `app/Enums/AI/AIUseCase.php` | Use cases; each value must equal an `ai_prompt_templates.name` |
| `app/Providers/AIServiceProvider.php` | Binds the contract from `config('ai.provider')`; calls `hydrateConfig()` on boot |
| `config/ai.php` | Provider, defaults, Anthropic and DeepSeek settings, safety rules |
| `app/Models/AiPromptTemplate.php`, `app/Models/AiGeneration.php` | Models |
| `database/seeders/AiPromptTemplateSeeder.php` | One template per use case |
| `database/factories/AiPromptTemplateFactory.php` | Factory (`forUseCase()`, `inactive()`) |
| **Frontend** | None in this module. Admin screens are in `admin.md` |
| **Tests** | |
| `tests/Feature/AI/AIInfrastructureTest.php` | Config, bindings, seeder, service, Anthropic and DeepSeek payloads and error codes, settings overlay, DTOs |
| `tests/Feature/AI/AIIntegrationTest.php` | Analytics rows, AI jobs, admin settings endpoints, generator fallback |


## Data model

**`ai_prompt_templates`**: `name` (use case value), `system_prompt`, `user_prompt_template`, `description`, `version` (unsigned int), `active`, `max_tokens` (nullable per-template override). Unique on (`name`, `version`).

**`ai_generations`** (one row per provider call): `provider`, `model`, `feature` (use case value), `prompt_template` (`<use_case>:v<version>`), `prompt_tokens`, `completion_tokens`, `total_tokens`, `duration_ms`, `successful`, `fallback_used` (always the inverse of `successful`), `error_code`, `error_message`, `user_id` (nullable), `subject_type` / `subject_id` (nullable morph). The same migration also adds `ai_enhanced` and `fallback_used` to `date_night_plans`.

**`system_settings`** rows with keys `ai.provider`, `ai.model`, `ai.temperature`, `ai.max_tokens`, `ai.timeout`, `ai.retry_attempts` hold admin overrides (table owned by `admin.md`).

**`AIUseCase` values**: `date_night_plan`, `date_night_plan_solo`, `date_night_plan_out_couple`, `intimacy_game`, `weekly_reflection`. The contract is `App\Contracts\AI\AIProvider`, bound as a singleton in `AIServiceProvider`.

## Routes
None in this module. The admin routes `admin.ai-prompts.*` and `admin.ai-settings.*` (`routes/admin.php`) are documented in `admin.md`.

## Flows

### 1. One AI call (`AIService::run`)
0. If the feature flag `ai_generation` is off (`RuntimeSettings`; on by default and when there is no row), return `AIResponse::failure` with code `ai_disabled` (`AIService::DISABLED_ERROR_CODE`). No template is loaded, no provider call is made and no analytics row is written; callers fall back as for any other failure.
0b. Consent gate: every public `AIService` method takes a required `array $people` (the `User`s whose answers, notes or moments are in the context). If any of them has not agreed to AI processing (`User::hasAiConsent()`), return `AIResponse::failure` with code `ai_consent_missing` (`AIService::NO_CONSENT_ERROR_CODE`): no template, no provider call, no analytics row. Callers fall back as for any other failure.
1. Load the template: `name` = use case value, `active` = true, highest `version`. If none exists, a `RuntimeException` is thrown (no analytics row is written).
2. Interpolate: every key in the context array replaces `{{key}}` in `user_prompt_template`. Scalars are cast to string, anything else is JSON encoded. Unknown placeholders are left as literal text.
3. Build `AIRequest` with `temperature`, `timeout`, `retry_attempts`, `response_format` from `config('ai.defaults.*')` and `max_tokens` from the template, or the config default when the template value is null.
4. Call `provider->complete()`, time it, log a warning on failure, record an `ai_generations` row, return the `AIResponse`.

### 2. Anthropic request (default)
1. Missing API key returns failure `missing_api_key` without a network call.
2. POST to `base_url` + `messages_endpoint` with headers `x-api-key`, `anthropic-version` and (when `fallbacks` is on) `anthropic-beta: server-side-fallback-2026-07-01`. Body: `model`, `max_tokens`, `system` (the system prompt), one user message, `output_config.effort`, and `fallbacks: "default"`.
3. `temperature` is **not** sent: current Claude models reject non-default sampling parameters with a 400. `ai.defaults.temperature` only affects DeepSeek. Use `ANTHROPIC_EFFORT` to trade depth for speed and cost.
4. `max_tokens` sent is the larger of the request value and `min_max_tokens` (16000). Claude always thinks and thinking is billed against `max_tokens`, so the template/admin value acts as a minimum here, not a cap.
5. Retries: same policy as DeepSeek, plus HTTP 429.
6. Outcomes: `transport_error`, `http_error`, `invalid_payload`, `refused` (`stop_reason` = `refusal`, after the server-side fallback also declined), `response_truncated` (`stop_reason` = `max_tokens`), `invalid_json`. Text blocks are joined, a Markdown code fence is stripped, and the JSON object becomes `AIResponse::data`. `usage` is mapped to `prompt_tokens` / `completion_tokens` / `total_tokens` so analytics rows match DeepSeek's.
7. `AIResponse::model` is the model that actually served the call, which differs from the configured one when a fallback ran.

### 2b. DeepSeek request
1. Missing API key returns failure `missing_api_key` without a network call.
2. POST to `base_url` + `chat_endpoint` with bearer token, `model`, `temperature`, `max_tokens`, `response_format: {type: json_object}`, one system and one user message.
3. Retries: `retry_attempts` tries (minimum 1), `retry_delay_ms` apart, only for connection errors and 5xx responses.
4. Outcomes map to stable error codes: `transport_error`, `http_error`, `invalid_payload`, `response_truncated` (`finish_reason` = `length`), `invalid_json`. Success returns the decoded JSON object in `AIResponse::data`.

### 3. Settings overlay
`AIServiceProvider::boot()` calls `AISettingsService::hydrateConfig()`, which copies stored `ai.*` system settings onto `ai.provider`, `ai.providers.<provider>.model` (the provider is applied first, so the model lands on the selected provider) and `ai.defaults.{temperature,max_tokens,timeout,retry_attempts}`. Database values therefore override `.env` on every request. If the table does not exist yet, the overlay is skipped.

### Callers

| Caller | Use case | Dispatched / invoked from |
|--------|----------|---------------------------|
| `app/Services/DateNightGeneratorService.php` | `date_night_plan`, `date_night_plan_solo`, `date_night_plan_out_couple` | Inside `GenerateDateNightPlanJob`, and synchronously from the plan page's "Try again with AI" (`date-night.retry-ai`, `throttle:ai`; see `date-night.md`) |
| `app/Services/WeeklyReflectionService.php` | `weekly_reflection` | Web request (see `weekly-reflection.md`) |
| `app/Http/Controllers/Admin/AdminAiSettingController.php` | Any (test connection, preview) | Admin UI (see `admin.md`). Exempt from consent: test connection calls the provider directly with a fixed probe, preview passes `people: []` because the admin types the context |


### How to add a new use case
1. Add a case to `AIUseCase`.
2. Add a template to `AiPromptTemplateSeeder` whose `name` is the enum value (the test `test_seeder_registers_a_template_for_every_use_case` enforces this). Prefix `system_prompt` with `config('ai.safety.system_rules')` like the others, and tell the model to answer with a JSON object.
3. Add a branch to `NullAIProvider::stubData()`.
4. Call `AIService::generate($useCase, $context, $people, $subject, $userId)`. `$people` is required: list everyone whose content is in the context (both partners for couple content). Pass `[]` only when the context holds no personal data. Pass the data under the key your template uses (the seeded templates use `{{context}}`, so pass `['context' => [...]]`).
5. Wrap the call in `try/catch (Throwable)` and check `$response->successful`; keep a deterministic fallback.

### How to switch provider
Set `AI_PROVIDER` to `anthropic`, `deepseek` or `null` (and run `php artisan optimize:clear` if config is cached), or change it in admin AI settings. An admin-stored `ai.provider` / `ai.model` overrides `.env`, and the stored model is applied to whichever provider is selected, so change both together: a DeepSeek model name sent to Claude (or the reverse) fails with `http_error`.

### How to add a new provider
1. Implement `App\Contracts\AI\AIProvider`. Return `AIResponse::failure()` for expected errors, do not throw.
2. Register it in `AIServiceProvider::PROVIDERS` and add its settings under `config/ai.php` `providers`.
3. Extend the `in:anthropic,deepseek,null` rule in `app/Http/Requests/Admin/UpdateAiSettingsRequest.php` and the `providers` list in `AdminAiSettingController::index()`.
4. The admin "model" setting follows the selected provider (`ai.providers.<provider>.model`); the new provider needs a `model` key in config.

## Rules and gotchas
- **Consent comes first** (`users.ai_consent_at`, `ai_consent_declined_at`; both null = not asked yet). Default is no consent, so existing users get the rule-based results until they agree on the dashboard card or in Settings, Profile (`PUT settings/ai-consent`, see `auth-settings.md`). Who must have agreed: date night plan = the owner of each response in the prompt (one person for a solo plan, both for a couple plan); weekly reflection = the user and their partner; the four undispatched `ai`-queue jobs = the plan's response owners, or the user with id `coupleId` and their partner (an unknown id skips the call). `UserFactory` defaults to agreed so AI tests need no setup; use `->withoutAiConsent()` for the other case.
- **Prompts live in the database**, not in code. The seeder is only the initial source; admins edit templates in the admin UI. The seeder uses `updateOrCreate` on (`name`, `version`), so reseeding overwrites admin edits to a row with the same version. To ship a prompt change, bump `version` in the seeder and run `php artisan datenight:sync-prompts` on deploy.
- **`datenight:sync-prompts`** (`app/Console/Commands/SyncPromptTemplatesCommand.php`) reads `AiPromptTemplateSeeder::templates()` and inserts a template only when no row with that name has the same or a higher version. It never updates a row, prints each `Added <name> v<version>`, and is safe on every deploy. "Same or higher" matters because an admin edit raises the row's version in place: an admin-edited v2 is kept when the seeder ships v2, and is superseded (not overwritten) when the seeder ships v3.
- **Safety rules are baked in at seed time**: `config('ai.safety.system_rules')` is concatenated into `system_prompt` by the seeder. `AIService` does not append it at runtime, so templates created in the admin UI do not get it automatically.
- **Config defaults** (`config/ai.php`): provider `anthropic`, temperature 0.7, max tokens 5000, timeout 60s, retry attempts 3, retry delay 250ms, response format `json_object`. Env names: `AI_PROVIDER`, `AI_TEMPERATURE`, `AI_MAX_TOKENS`, `AI_TIMEOUT`, `AI_RETRY_ATTEMPTS`, `AI_RETRY_DELAY_MS`, `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL`, `ANTHROPIC_BASE_URL`, `ANTHROPIC_EFFORT` (default `medium`), `ANTHROPIC_MIN_MAX_TOKENS` (16000), `ANTHROPIC_FALLBACKS` (true), `DEEPSEEK_API_KEY`, `DEEPSEEK_BASE_URL`, `DEEPSEEK_MODEL`. `.env.example` ships different values (max tokens 2500, timeout 30).
- **Claude model**: `ANTHROPIC_MODEL`, else the older `AI_MODEL_REASONING` env name, else `claude-opus-5-5`. Use exact model IDs without a date suffix. `ANTHROPIC_FALLBACKS=false` is needed for models without server-side refusal fallback (for example Haiku).
- **Admin limits**: global max tokens 1 to 8000, timeout 1 to 300s, retry attempts 0 to 10, temperature 0 to 2; per-template `max_tokens` 1 to 32000.
- **Failure handling**: providers return failures as data. `AIService` logs `AI request failed.` at warning level and still records the row. The only exception `AIService` throws itself is the missing-template `RuntimeException`, so callers must catch `Throwable`.
- **Logging**: the service and provider log through the default logger. Both HTTP providers log the response body on non-2xx and the raw content on invalid JSON.
- **Analytics attribution**: `user_id` and `subject` are only stored when the caller uses `AIService::generate()` with those arguments. Date night generation uses the semantic methods, so its rows have neither.
- **Worst-case duration** of one call is roughly `timeout` x `retry_attempts` (180s with defaults) because timeouts are retried. Keep this in mind for job timeouts and web requests.
- **Tests** set `ai.provider` to `deepseek` (or call the `useAnthropic()` helper in `AIInfrastructureTest`) with a fake key and `Http::fake()`, or swap the contract with an anonymous class via `$this->app->instance(AIProvider::class, ...)`. `phpunit.xml` does not set `AI_PROVIDER`.
- **Null provider**: `NullAIProvider::stubData()` has an arm for every `AIUseCase` (a test loops over the cases), and the plan stubs return only keys the generator accepts. With `AI_PROVIDER=null` every plan therefore gets the canned summary and `ai_enhanced = true`; that is what the provider is for.
- Suspected issue: `config('ai.logging.*')` (`AI_LOG_CHANNEL`, `AI_LOG_PROMPTS`) is not read anywhere.
- Suspected issue: the comment in `AiPromptTemplateSeeder` (line 68) says the schema has 9 fields; it has 7 since drink and music were removed.

## Related modules
- `admin.md`: prompt template CRUD, AI settings, test connection, previews, analytics view.
- `date-night.md`: main consumer; rule engine plus AI enhancement and its fallback flags.
- `weekly-reflection.md`: second consumer.
- `events-jobs.md`: queue names, including `ai`.
- `platform.md`: service providers and config loading.

## Tests
- `php artisan test tests/Feature/AI`
- `php artisan test --filter=AIInfrastructureTest`
- `php artisan test --filter=AIIntegrationTest`
- `php artisan test --filter=SyncPromptTemplatesCommandTest` (the sync command), `--filter=RuntimeSettingsTest` (the `ai_generation` flag)

## Change log
- 2026-11-24 — Initial doc.
- 2026-11-24 — Added `AnthropicProvider` and made it the default; DeepSeek kept as a selectable provider. Admin model setting now follows the selected provider.
- 2026-11-24 — New use case `date_night_plan_out_couple` (compromise date-out plan for two sets of answers). Its template must be seeded: `php artisan db:seed --class=AiPromptTemplateSeeder`.
- 2026-11-24 — `weekly_reflection` template is now version 2 and `WeeklyReflectionService` passes its data under `context`.
- 2026-11-24 — Roadmap Phase 2: null provider covers `date_night_plan_solo`, `weekly_reflection` and `intimacy_game`; new synchronous caller "Try again with AI".
- 2026-11-24 — `intimacy_game` template is now version 2 and asks for `{ "prompt": string }` (still no caller).
- 2026-11-24 — Roadmap Phase 2 (2.13, 2.16): feature flag `ai_generation` off short-circuits `AIService` with `ai_disabled`; new command `datenight:sync-prompts` adds missing prompt versions without touching existing rows (`AiPromptTemplateSeeder::templates()` is public for it); the admin AI settings page exists.
- 2026-11-24 — `config/ai.php` turns a null `AI_PROVIDER` back into the provider name `null`: Laravel parses the text "null" in `.env` as a real null, which previously left the provider empty and unresolvable.
- 2026-11-25 — Roadmap Phase 3 (3.6): per-user AI consent. `AIService` methods require `$people` and refuse with `ai_consent_missing` unless all of them agreed; callers pass the people whose content is sent.
- 2026-11-26 — Roadmap Phase 4: removed the legacy `App\Contracts\AIProvider`, `App\AI\NullAIProvider` and their binding, `AiPrompt` (new migration drops `ai_prompts`), the four never-dispatched `ai`-queue jobs, and the six use cases only they or nobody used (`conversation_prompt`, `relationship_insight`, `compatibility_summary`, `challenge_variation`, `theme_description`, `moment_caption`) with their `AIService` methods, null-provider stubs and seeded templates. Rows for those templates already in a database are left alone and are no longer read.
