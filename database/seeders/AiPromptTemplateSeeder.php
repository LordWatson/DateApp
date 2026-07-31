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
You are enhancing a date night plan for a couple (or, if `is_solo` is true, for
one partner planning the date for both of them). The plan you produce MUST
match the topic, mood and intent of the questionnaire and the actual answers,
and MUST NOT be a generic template.

Rules you MUST follow:
  - Anchor every field to the questionnaire's topic (see `questionnaire.title`
    and `questionnaire.description` inside the context). If the questionnaire
    is about a games night, produce a games-night plan. If it is about an
    intimate or sexual evening, keep the plan intimate and sensual (still
    tasteful and within safety rules). Never invent an unrelated theme
    (e.g. cooking or star gazing) unless the questionnaire clearly implies it.
  - Ground the plan in the ACTUAL ANSWERS. Read every entry in
    `couple_answers.partner_one`, `couple_answers.partner_two` and, when
    `is_solo` is true, `user_answers`. Each entry contains the `question` text
    and the `answer` (option title or free-text value). Reference these
    preferences concretely — do not invent preferences that aren't there.
    Where both partners agree, lean into it. Where they differ, choose a warm
    compromise that honours both.
  - If `questionnaire.is_intimacy` is true, `meal_suggestion` and
    `drink_suggestion` may be small sensual touches (e.g. a shared bite,
    a shared sip) rather than a full meal.
  - If `is_solo` is true, the plan is generated from ONE user's answers and
    is intended for a real-world outing rather than an evening in. Anchor the
    activity, meal, drink and atmosphere to the user's stated timing
    (day/evening), available time, budget and vibe as expressed in
    `user_answers`. When `location` is provided, it MAY include a rough label
    (city/region/country), the user's approximate `latitude`/`longitude`, and
    `travel_radius_minutes` — the maximum one-way travel time the user is
    happy with, measured strictly as DRIVING TIME BY CAR OR PUBLIC TRANSPORT (not walking, not
    cycling). You MUST treat `travel_radius_minutes`
    as a HARD constraint that defines the geographic scope of the plan and
    reason about it as "how far can we realistically drive from the user's
    location in this many minutes?":
      * 0–30 minutes by car → you MAY include areas within a 30-minute drive of the users location.
      * 31–90 minutes by car → you MAY include nearby towns, coastline,
        countryside or larger cities or destinations reachable within that drive.
      * 91–180 minutes by car → you MAY include further afield destinations,
        neighbouring regions or day-trip locations that are realistically
        driveable in that time from the user's location.
      * 181+ minutes by car → you MAY propose a trip to another region
        or major destination reachable by car in that time.
    You MUST NOT collapse everything back to the user's home city when
    `travel_radius_minutes` is large — actively broaden the plan to somewhere
    exciting within the allowed driving radius. Explicitly name the
    destination town, area or region in `summary` and `activity` (not just
    the user's home city) whenever the radius allows driving beyond it. When
    the radius is small, keep everything genuinely local to the user's
    `city`/`region`. Never suggest anywhere the user would clearly need to
    drive longer than `travel_radius_minutes` (one way) to reach. If
    `travel_radius_minutes` is 0 or missing, favour ideas at or immediately
    around the user's location.
    Budget answers may take the form `custom_amount:<number>` — treat the
    number as a rough total budget in the user's local currency and tailor
    spend accordingly. NEVER invent addresses, precise coordinates, phone
    numbers or URLs. NEVER claim a venue is open, available or verified.
    Frame local suggestions as ideas to explore, not confirmed bookings. If
    `location` is null, gracefully avoid location references and return an
    empty `local_suggestions` array.
  - Keep the theme consistent across all fields and make each suggestion
    concrete, inviting and evocative — never generic filler like "a nice
    meal" or "a fun activity".
  - You MUST return a `theme` field: a short (2–5 words), punchy, human
    friendly NAME for this specific date night that accurately reflects the
    plan you have generated (activity + meal + atmosphere). The context may
    include a `deterministic_plan.theme` — treat it only as a rough category
    hint. If your generated plan diverges from that name (e.g. the plan is
    paddleboarding followed by a pub meal but the hint says "Pizza Night In"),
    you MUST override it with an accurate name (e.g. "Paddleboard & Pub
    Evening"). Never invent a theme unrelated to the plan you actually
    produced. Use Title Case, no emojis, no markdown, no trailing
    punctuation.

Context:
{{context}}

Respond as strict JSON matching exactly this shape (all string fields required,
non-empty, concise, and free of markdown):
{
  "theme": string,                 // 2–5 word Title Case name for the date night that accurately reflects the generated plan
  "summary": string,               // 1–3 sentences describing the overall evening, referencing the questionnaire's topic
  "meal_suggestion": string,       // a specific dish or shared food idea appropriate to the questionnaire
  "drink_suggestion": string,      // a specific drink pairing (alcoholic or not) appropriate to the questionnaire
  "music_vibe": string,            // genre, mood or example artists/playlist idea that fits the evening
  "atmosphere": string,            // lighting, scent, setting cues that fit the questionnaire's mood
  "activity": string,              // the main shared activity, aligned to the questionnaire's topic
  "conversation_prompt": string,   // one gentle, open-ended question tied to the questionnaire's theme
  "romantic_challenge": string,    // one small, playful challenge that fits the couple's answers
  "local_suggestions": [           // AT MOST 3 items. ONLY populate when `is_solo` is true AND `location` is provided; otherwise return an empty array
    {
      "name": string,              // a plausible local spot, area, neighbourhood or type of event
      "category": string,          // e.g. "restaurant", "bar", "park", "gallery", "walk", "event", "neighbourhood"
      "description": string        // 1 sentence explaining why it fits the date's vibe and timing
    }
  ]
}

Hard limit: `local_suggestions` MUST contain at most 3 entries. Prefer fewer,
higher-quality picks over padding the list.
PROMPT,
                'version' => 10,
                'active' => true,
                // Verbose schema (11 fields incl. a local_suggestions array):
                // needs a larger output budget than cheaper single-field
                // prompts to avoid `finish_reason=length` truncation.
                'max_tokens' => 2500,
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
