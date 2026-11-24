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
                // Schema has 9 short string fields; keep a generous but not
                // excessive output budget so DeepSeek does not truncate.
                'max_tokens' => 5000,
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
                'max_tokens' => 5000,
            ],
            [
                'name' => AIUseCase::DateNightPlanOutCouple->value,
                'description' => 'Generates a date out for a couple as a compromise between both partners\' answers, tuned to location, timing and budget.',
                'system_prompt' => $safety."\n\nYou craft romantic, playful and thoughtful date night plans that respect both partners' preferences and consent.",
                'user_prompt_template' => <<<'PROMPT'
Enhance the wording of a date plan for a COUPLE. Each partner answered the same
questions separately; their answers are in `couple_answers.partner_one` and
`couple_answers.partner_two` (each entry has a `question` and `answer`).
The plan is for a real-world outing (not an evening in) and MUST match the
questionnaire's topic and what the two of them actually said.

Compromise rules (this is the point of the plan):
- Where both gave the same answer, honour it fully.
- Where they differ, find a plan both would genuinely enjoy. Blend the two
  (for example one partner's choice of activity with the other's choice of
  food or mood), or pick the middle ground. Never simply adopt one partner's
  answers and ignore the other's.
- Hard limits always use the stricter answer: stay within the LOWER budget,
  the SHORTER available time and the EARLIER finish. Budget answers may take
  the form `custom_amount:<number>` — treat the number as a rough total budget
  in the couple's local currency.
- `summary` must say in one or two warm sentences how the plan balances what
  each of them wanted. Refer to them as "one of you" and "the other"; never
  say which partner gave which answer and never invent preferences.
- `theme` is a 2–5 word Title Case name that reflects the plan you produced
  (no emojis, no markdown, no trailing punctuation).

Location & travel radius (only when `location` is provided):
- `location` may include a rough label (city/region/country), approximate
  `latitude`/`longitude`, and `travel_radius_minutes` — the maximum one-way
  travel time, already reduced to the smaller of the two partners' answers.
  Treat it as a HARD constraint.
- When the radius is small, keep everything genuinely local. When it allows
  travel beyond the home city, you may name a destination town or area in
  `summary` and `activity`.
- NEVER invent addresses, precise coordinates, phone numbers or URLs. NEVER
  claim a venue is open, available or verified. Frame local suggestions as
  ideas to explore, not confirmed bookings.
- If `location` is missing, avoid location references and return an empty
  `local_suggestions` array.

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
higher-quality picks over padding. Return an empty array when `location` is missing.
PROMPT,
                'version' => 1,
                'active' => true,
                'max_tokens' => 5000,
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
                'description' => 'Writes the couple’s shared weekly reflection: specific insights drawn from what they did in the past seven days.',
                'system_prompt' => $safety."\n\nYou write supportive, non-judgemental weekly reflections for a couple. Celebrate small wins, notice patterns kindly, and suggest one gentle idea for the week ahead. Avoid diagnosing, ranking, or lecturing. Never mention private message content verbatim.",
                'user_prompt_template' => <<<'PROMPT'
Write this week's reflection for a couple, using ONLY the activity log below.
Both partners read the SAME reflection, so write to the two of them together
("you two", "you both") and use the first names in `partners` when you say who
did what. If `partner_connected` is false, write to the one person instead.

Being specific is the whole point of this reflection:
- Name the real things from the log: date night themes and what the plan was,
  moment titles, calendar events, questionnaires, achievements, streaks.
- Give at least one genuine insight: a pattern you can actually see in the
  log. For example what they keep choosing, the moods of their moments, who
  tends to start things, how a date night connects to a moment, or what the
  week says about how they spend time together. Offer it as a kind
  observation, never a verdict.
- `gentle_suggestion` must grow out of this week's log: build on something
  they enjoyed, even out something one-sided, or get ready for an entry in
  `calendar.coming_up`. Never give a tip that would fit any couple.
- If the log is nearly empty, say warmly and briefly that it was a quiet week
  and keep everything short. Do not pad, and never mention anything that is
  not in the log.
- `love_notes.messages` are private. You may say what the notes were about or
  how many were sent, but never quote one word for word.
- Never rank the partners, keep score or blame either of them. Do not give
  per-person tallies ("Alex sent three, Sam sent one"); give the total for the
  two of them instead.
- Warm, natural, modern British English. No emojis, no markdown, no lists
  inside strings.

Activity log for the past seven days:
{{context}}

Respond as strict JSON with exactly this shape (all fields required, all
strings non-empty, no markdown):
{
  "headline": string,          // 3–6 words that could only describe THIS week
  "summary": string,           // 2–4 sentences: what the week was like and the insight you noticed
  "highlights": [string],      // 2–4 short highlights, each naming something real from the log (1 for a quiet week)
  "gentle_suggestion": string, // ONE kind idea for next week that follows from this week
  "encouragement": string      // one short, warm sentence
}
PROMPT,
                // Version 2: the activity log replaces aggregated counts, and the reflection is shared by the couple.
                'version' => 2,
                'active' => true,
            ],
        ];
    }
}
