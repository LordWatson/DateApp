<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use Illuminate\Database\Seeder;

class AiPromptTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            AiPromptTemplate::query()->updateOrCreate(
                ['name' => $template['name'], 'version' => $template['version']],
                $template,
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function templates(): array
    {
        $safety = (string) config('ai.safety.system_rules');

        return [
            [
                'name' => AIUseCase::DateNightPlan->value,
                'description' => 'Generates a romantic, mood-appropriate date night plan for a couple.',
                'system_prompt' => $safety."\n\nYou craft romantic, playful and thoughtful date night plans that respect both partners' preferences and consent.",
                'user_prompt_template' => <<<'PROMPT'
Enhance the wording of a date night plan for a couple. The plan MUST match the
questionnaire's topic and the couple's actual answers — never a generic template.

Rules:
- Anchor every field to `questionnaire.title` / `questionnaire.description`. If
  the questionnaire is about games, produce a games-night plan; if intimate,
  keep it sensual yet tasteful.
- Ground the plan in the ACTUAL answers in `couple_answers.partner_one` and
  `couple_answers.partner_two`. Where they agree, lean in; where they differ,
  choose a warm compromise. Never invent preferences.
- Every suggestion must be concrete and evocative — no filler like "a nice
  meal" or "a fun activity".
- `theme` is a 2–5 word Title Case name that accurately reflects the plan you
  actually produced (no emojis, no markdown, no trailing punctuation).

Context:
{{context}}

Respond with strict JSON matching exactly this shape (all fields required,
non-empty strings, no markdown):
{
  "theme": string,
  "summary": string,
  "meal_suggestion": string,
  "atmosphere": string,
  "activity": string,
  "conversation_prompt": string,
  "romantic_challenge": string
}
PROMPT,
                'version' => 11,
                'active' => true,
                // Schema is 7 short string fields (~40–80 tokens each). A tight
                // budget keeps DeepSeek latency low — output tokens dominate
                // response time. If the model ever truncates the analytics
                // recorder surfaces `response_truncated` distinctly so we know
                // to raise this, rather than paying for headroom we never use.
                'max_tokens' => 900,
            ],
            [
                'name' => AIUseCase::DateNightPlanSolo->value,
                'description' => 'Generates a solo-planned date night for one partner, tuned to their location, timing and budget.',
                'system_prompt' => $safety."\n\nYou craft romantic, playful and thoughtful date night plans that respect both partners' preferences and consent.",
                'user_prompt_template' => <<<'PROMPT'
Enhance the wording of a date night plan generated from ONE partner's answers.
The plan is intended for a real-world outing (not an evening in) and MUST match
the questionnaire's topic and the user's actual answers — never a generic template.

Rules:
- Anchor every field to `questionnaire.title` / `questionnaire.description` and
  the entries in `user_answers` (each has a `question` and `answer`). Reference
  stated timing, available time, budget and vibe concretely. Never invent
  preferences.
- Budget answers may take the form `custom_amount:<number>` — treat the number
  as a rough total budget in the user's local currency and tailor spend to it.
- `theme` is a 2–5 word Title Case name that accurately reflects the plan you
  produced (no emojis, no markdown, no trailing punctuation).

Location & travel radius (only when `location` is provided):
- `location` may include a rough label (city/region/country), approximate
  `latitude`/`longitude`, and `travel_radius_minutes` — the maximum one-way
  DRIVING/PUBLIC TRANSPORT time the user accepts. Treat it as a HARD constraint.
- When the radius allows driving beyond the user's home city, actively broaden
  the plan and name the destination town/area/region in `summary` and
  `activity`. When the radius is small, keep everything genuinely local.
- Never suggest anywhere the user would clearly need to drive longer than
  `travel_radius_minutes` (one way) to reach. If `travel_radius_minutes` is 0
  or missing, favour ideas at or immediately around the user's location.
- NEVER invent addresses, precise coordinates, phone numbers or URLs. NEVER
  claim a venue is open, available or verified. Frame local suggestions as
  ideas to explore, not confirmed bookings.
- If `location` is null, gracefully avoid location references and return an
  empty `local_suggestions` array.

Context:
{{context}}

Respond with strict JSON matching exactly this shape (all string fields required,
non-empty, no markdown):
{
  "theme": string,
  "summary": string,
  "meal_suggestion": string,
  "atmosphere": string,
  "activity": string,
  "conversation_prompt": string,
  "romantic_challenge": string,
  "local_suggestions": [
    {
      "name": string,
      "category": string,
      "description": string
    }
  ]
}

Hard limit: `local_suggestions` MUST contain at most 3 entries; prefer fewer,
higher-quality picks over padding. Return an empty array when `location` is null.
PROMPT,
                'version' => 1,
                'active' => true,
                // Solo schema adds up to 3 `local_suggestions` (~60 tokens each)
                // on top of the 7 base fields. Budget accordingly without
                // encouraging the model to pad the response.
                'max_tokens' => 3500,
            ],
            [
                'name' => AIUseCase::ConversationPrompt->value,
                'description' => 'Generates a thoughtful conversation starter for a couple.',
                'system_prompt' => $safety."\n\nYou create warm, open-ended conversation prompts that build emotional connection.",
                'user_prompt_template' => <<<'PROMPT'
Suggest one gentle, non-invasive conversation prompt for the couple.

Context:
{{context}}

Respond as JSON with:
{ "prompt": string, "follow_up": string }
PROMPT,
                'version' => 1,
                'active' => true,
            ],
            [
                'name' => AIUseCase::RelationshipInsight->value,
                'description' => 'Summarises a light, supportive relationship insight from questionnaire data.',
                'system_prompt' => $safety."\n\nYou highlight relationship strengths and gentle growth areas without diagnosing or advising.",
                'user_prompt_template' => <<<'PROMPT'
Given the couple's recent shared answers, produce one supportive insight.

Context:
{{context}}

Respond as JSON with:
{ "headline": string, "body": string, "suggestion": string }
PROMPT,
                'version' => 1,
                'active' => true,
            ],
            [
                'name' => AIUseCase::CompatibilitySummary->value,
                'description' => 'Summarises the deterministic compatibility calculation in friendly language.',
                'system_prompt' => $safety."\n\nYou describe an already-calculated compatibility score in warm, non-judgemental language. Never recompute the score.",
                'user_prompt_template' => <<<'PROMPT'
Summarise the couple's compatibility using ONLY the provided score and matches.

Context:
{{context}}

Respond as JSON with:
{ "summary": string, "strengths": [string], "growth_areas": [string] }
PROMPT,
                'version' => 1,
                'active' => true,
            ],
            [
                'name' => AIUseCase::ChallengeVariation->value,
                'description' => 'Creates a playful variation of an existing romantic challenge.',
                'system_prompt' => $safety."\n\nYou create playful, respectful variations of romantic challenges without adding unsafe content.",
                'user_prompt_template' => <<<'PROMPT'
Given the challenge below, propose one variation the couple could try instead.

Context:
{{context}}

Respond as JSON with:
{ "variation": string, "why_it_works": string }
PROMPT,
                'version' => 1,
                'active' => true,
            ],
            [
                'name' => AIUseCase::ThemeDescription->value,
                'description' => 'Writes a short, evocative description for a date night theme.',
                'system_prompt' => $safety."\n\nYou write short, evocative, tasteful theme descriptions.",
                'user_prompt_template' => <<<'PROMPT'
Enhance the given theme with a short, evocative description.

Context:
{{context}}

Respond as JSON with:
{ "description": string, "keywords": [string] }
PROMPT,
                'version' => 1,
                'active' => true,
            ],
            [
                'name' => AIUseCase::MomentCaption->value,
                'description' => 'Writes a short, warm caption for a saved shared moment.',
                'system_prompt' => $safety."\n\nYou write short, warm captions for shared moments. Never invent personal details.",
                'user_prompt_template' => <<<'PROMPT'
Write one short caption for the couple's saved moment.

Context:
{{context}}

Respond as JSON with:
{ "caption": string }
PROMPT,
                'version' => 1,
                'active' => true,
            ],
            [
                'name' => AIUseCase::IntimacyGame->value,
                'description' => 'Writes a short, sexy, erotic game for a couple to play together.',
                'system_prompt' => $safety."\n\nYou write sexy, erotic games for couples. Never invent personal details.",
                'user_prompt_template' => <<<'PROMPT'
Write one sex / intimacy game for a couple.

Context:
{{context}}

Respond as JSON with:
{ "caption": string }
PROMPT,
                'version' => 1,
                'active' => true,
            ],
            [
                'name' => AIUseCase::WeeklyReflection->value,
                'description' => 'Writes a short, warm weekly reflection summarising the couple’s shared week.',
                'system_prompt' => $safety."\n\nYou write supportive, non-judgemental weekly reflections for a couple. Celebrate small wins, notice patterns kindly, and suggest one gentle idea for the week ahead. Avoid diagnosing, ranking, or lecturing. Never mention private message content verbatim.",
                'user_prompt_template' => <<<'PROMPT'
Write a short reflection covering the couple's past week based ONLY on the
aggregated context below. Keep the tone warm, supportive, playful and modern —
never clinical or corporate. Do not quote private notes or messages verbatim;
speak in gentle generalities. Do not invent activities that are not implied by
the data.

Context (aggregated counts and summaries for the past week):
{{context}}

Respond as strict JSON with exactly this shape (all fields required, all
strings non-empty, no markdown):
{
  "headline": string,          // 3–6 words, warm and inviting
  "summary": string,           // 2–4 sentences reflecting on the past week together
  "highlights": [string],      // 1–3 short bullet-style highlights from the week
  "gentle_suggestion": string, // ONE kind, non-prescriptive idea for next week
  "encouragement": string      // one short sentence of warm encouragement
}
PROMPT,
                'version' => 1,
                'active' => true,
            ],
        ];
    }
}
