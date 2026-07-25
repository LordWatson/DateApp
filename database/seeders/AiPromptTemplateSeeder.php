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
Given the couple's shared context below, propose a single date night plan.

Context:
{{context}}

Respond as JSON with:
{
  "title": string,
  "summary": string,
  "activities": [ { "title": string, "description": string, "duration_minutes": integer } ],
  "vibe": string
}
PROMPT,
                'version' => 1,
                'active' => true,
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
        ];
    }
}
