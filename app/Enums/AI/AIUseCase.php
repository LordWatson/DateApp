<?php

declare(strict_types=1);

namespace App\Enums\AI;

/**
 * Canonical set of AI use cases supported by the platform.
 *
 * The string value of each case must match the `name` column of the
 * corresponding row in the `ai_prompt_templates` table so that prompt
 * templates can be resolved deterministically from the database.
 */
enum AIUseCase: string
{
    case DateNightPlan = 'date_night_plan';
    case ConversationPrompt = 'conversation_prompt';
    case RelationshipInsight = 'relationship_insight';
    case CompatibilitySummary = 'compatibility_summary';
    case ChallengeVariation = 'challenge_variation';
    case ThemeDescription = 'theme_description';
    case MomentCaption = 'moment_caption';
    case IntimacyGame = 'intimacy_game';
}
