<?php

namespace App\Services;

use App\Contracts\AIProvider;
use App\Models\DateNightPlan;
use App\Models\DateNightTheme;
use App\Models\Questionnaire;
use App\Models\Response;

class DateNightGeneratorService
{
    public function __construct(
        private readonly RuleEngineService $ruleEngine,
        private readonly AIProvider $aiProvider,
    ) {}

    /**
     * Generate a DateNightPlan from two completed responses.
     *
     * @param  array<string, mixed>  $compatibility
     */
    public function generate(
        Response $partnerOneResponse,
        Response $partnerTwoResponse,
        array $compatibility,
        Questionnaire $questionnaire,
    ): DateNightPlan {
        $userAnswers = $partnerOneResponse->answers()->with('questionOption')->get();
        $partnerAnswers = $partnerTwoResponse->answers()->with('questionOption')->get();
        $score = $compatibility['percentage'];

        $themes = DateNightTheme::active()->get();
        $theme = $themes->isNotEmpty()
            ? $this->ruleEngine->selectTheme($userAnswers, $partnerAnswers, $score, $themes)
            : $this->fallbackTheme();

        $context = $this->ruleEngine->deriveContext($userAnswers, $partnerAnswers, $score);

        $plan = $this->buildPlan($theme, $context, $score, $questionnaire);

        if ($this->aiProvider->isConfigured()) {
            $plan = $this->aiProvider->enhancePlan($plan, [
                'compatibility' => $compatibility,
                'questionnaire' => $questionnaire->title,
            ]);
        }

        return DateNightPlan::create([
            'questionnaire_id' => $questionnaire->id,
            'partner_one_response_id' => $partnerOneResponse->id,
            'partner_two_response_id' => $partnerTwoResponse->id,
            'date_night_theme_id' => $theme instanceof DateNightTheme ? $theme->id : null,
            'compatibility_score' => $score,
            'theme' => $plan['theme'],
            'theme_emoji' => $plan['theme_emoji'],
            'summary' => $plan['summary'],
            'meal_suggestion' => $plan['meal_suggestion'],
            'drink_suggestion' => $plan['drink_suggestion'],
            'music_vibe' => $plan['music_vibe'],
            'atmosphere' => $plan['atmosphere'],
            'activity' => $plan['activity'],
            'conversation_prompt' => $plan['conversation_prompt'],
            'romantic_challenge' => $plan['romantic_challenge'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string|null>
     */
    private function buildPlan(
        DateNightTheme $theme,
        array $context,
        int $score,
        Questionnaire $questionnaire,
    ): array {
        return [
            'theme' => $theme->name,
            'theme_emoji' => $theme->emoji,
            'summary' => $this->buildSummary($theme, $score),
            'meal_suggestion' => $this->selectMeal($context),
            'drink_suggestion' => $this->selectDrink($context),
            'music_vibe' => $this->selectMusic($context),
            'atmosphere' => $this->selectAtmosphere($context),
            'activity' => $this->selectActivity($context),
            'conversation_prompt' => $this->selectConversationPrompt($score),
            'romantic_challenge' => $this->selectRomanticChallenge($context),
        ];
    }

    private function buildSummary(DateNightTheme $theme, int $score): string
    {
        $scoreLabel = match (true) {
            $score >= 85 => 'an incredibly well-matched',
            $score >= 70 => 'a beautifully compatible',
            $score >= 55 => 'a wonderfully balanced',
            $score >= 40 => 'a thoughtfully curated',
            default => 'a uniquely personal',
        };

        return "Based on your answers, we've crafted {$scoreLabel} evening for you both. "
            ."Tonight's theme is {$theme->emoji} {$theme->name} — {$theme->description}";
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function selectMeal(array $context): string
    {
        if ($context['prefers_food']) {
            return 'Homemade pasta with a shared cooking experience';
        }

        if ($context['prefers_outdoors']) {
            return 'A picnic-style spread with artisan bread, cheese, and seasonal fruit';
        }

        if ($context['romance_high']) {
            return 'Candlelit dinner with your favourite dishes';
        }

        if ($context['prefers_movies']) {
            return 'Cosy movie snacks — popcorn, dips, and finger foods';
        }

        return 'A simple but beautiful shared meal at home';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function selectDrink(array $context): string
    {
        if ($context['romance_high']) {
            return 'A bottle of your favourite wine or sparkling water with fresh fruit';
        }

        if ($context['prefers_relaxing']) {
            return 'Herbal tea or hot chocolate with marshmallows';
        }

        if ($context['prefers_food']) {
            return 'Craft cocktails or mocktails to complement your meal';
        }

        return 'Something refreshing — sparkling water, juice, or a light cocktail';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function selectMusic(array $context): string
    {
        if ($context['prefers_music']) {
            return 'A curated playlist of songs that mean something to you both';
        }

        if ($context['romance_high']) {
            return 'Soft jazz or acoustic love songs in the background';
        }

        if ($context['prefers_relaxing']) {
            return 'Ambient or lo-fi music to set a calm, cosy mood';
        }

        if ($context['prefers_movies']) {
            return 'Film scores and cinematic soundtracks';
        }

        return 'Whatever playlist makes you both smile';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function selectAtmosphere(array $context): string
    {
        if ($context['romance_high']) {
            return 'Dim the lights, light some candles, and add fresh flowers if you can';
        }

        if ($context['prefers_relaxing']) {
            return 'Soft lighting, blankets, and a clutter-free space to unwind';
        }

        if ($context['prefers_outdoors']) {
            return 'Take it outside — fairy lights, a blanket, and the open sky';
        }

        if ($context['prefers_games']) {
            return 'Clear the table, grab your favourite games, and keep it playful';
        }

        return 'Create a cosy, distraction-free space just for the two of you';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function selectActivity(array $context): string
    {
        if ($context['prefers_movies']) {
            return 'Pick a film you\'ve both been meaning to watch and watch it together';
        }

        if ($context['prefers_games']) {
            return 'Play a board game or card game — winner chooses dessert';
        }

        if ($context['prefers_outdoors']) {
            return 'Take a slow evening walk together and watch the sunset';
        }

        if ($context['prefers_music']) {
            return 'Dance together in your living room — no choreography required';
        }

        if ($context['prefers_relaxing']) {
            return 'Give each other a five-minute shoulder massage and just talk';
        }

        if ($context['prefers_food']) {
            return 'Cook a new recipe together and enjoy the process as much as the meal';
        }

        return 'Spend an hour with no phones — just conversation and connection';
    }

    private function selectConversationPrompt(int $score): string
    {
        $prompts = [
            'What\'s one thing you\'ve always wanted to do together but haven\'t yet?',
            'What\'s your favourite memory of us from the past year?',
            'If we could go anywhere tomorrow, where would you choose?',
            'What\'s something small I do that makes you feel loved?',
            'What\'s one thing you\'d love us to do more of together?',
            'Describe your perfect lazy Sunday with me.',
            'What\'s something you\'ve been wanting to tell me but haven\'t found the right moment?',
            'What\'s the best date we\'ve ever had, and why?',
        ];

        return $prompts[$score % count($prompts)];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function selectRomanticChallenge(array $context): string
    {
        if ($context['romance_high']) {
            return 'Write each other a short love note and read them aloud tonight';
        }

        if ($context['prefers_music']) {
            return 'Dance to one full song together without stopping';
        }

        if ($context['prefers_outdoors']) {
            return 'Watch the sunset or stars together for at least 10 minutes';
        }

        if ($context['prefers_relaxing']) {
            return 'Give each other a five-minute hand or shoulder massage';
        }

        if ($context['low_compatibility']) {
            return 'Share one thing you appreciate about each other that you rarely say out loud';
        }

        return 'Put your phones away for the entire evening and be fully present';
    }

    private function fallbackTheme(): DateNightTheme
    {
        $theme = new DateNightTheme;
        $theme->name = 'Cozy Night In';
        $theme->emoji = '❤️';
        $theme->description = 'a warm, intimate evening at home';
        $theme->colour = '#EC4899';

        return $theme;
    }
}
