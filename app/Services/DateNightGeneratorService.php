<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\DateNightPlan;
use App\Models\DateNightTheme;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use App\Services\AI\AIService;
use Psr\Log\LoggerInterface;
use Throwable;

class DateNightGeneratorService
{
    /**
     * Fields the AI is allowed to rewrite. The Rule Engine remains
     * authoritative — AI can only enhance wording of these keys.
     *
     * @var list<string>
     */
    private const ENHANCEABLE_FIELDS = [
        'theme',
        'summary',
        'meal_suggestion',
        'drink_suggestion',
        'music_vibe',
        'atmosphere',
        'activity',
        'conversation_prompt',
        'romantic_challenge',
    ];

    public function __construct(
        private readonly RuleEngineService $ruleEngine,
        private readonly AIService $ai,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Generate a DateNightPlan from two completed responses.
     *
     * @param  array<string, mixed>  $compatibility
     */
    public function generate(
        Response $partnerOneResponse,
        ?Response $partnerTwoResponse,
        array $compatibility,
        Questionnaire $questionnaire,
        ?User $soloPartner = null,
    ): DateNightPlan {
        $isSolo = $partnerTwoResponse === null || $questionnaire->is_solo;
        $secondResponse = $partnerTwoResponse ?? $partnerOneResponse;

        $userAnswers = $partnerOneResponse->answers()->with('questionOption')->get();
        $partnerAnswers = $secondResponse->answers()->with('questionOption')->get();
        $score = $compatibility['percentage'];

        $themes = DateNightTheme::active()->get();
        $theme = $themes->isNotEmpty()
            ? $this->ruleEngine->selectTheme($userAnswers, $partnerAnswers, $score, $themes)
            : $this->fallbackTheme();

        $context = $this->ruleEngine->deriveContext($userAnswers, $partnerAnswers, $score);

        // 1. Deterministic plan (authoritative).
        $deterministicPlan = $this->buildPlan($theme, $context, $score, $questionnaire);

        // 2. Optional AI enhancement (wording only + optional local suggestions).
        [$plan, $aiEnhanced, $fallbackUsed, $localSuggestions] = $this->enhanceWithAI(
            $deterministicPlan,
            $compatibility,
            $questionnaire,
            $partnerOneResponse,
            $isSolo,
        );

        // 3. Validation — reject anything the Rule Engine didn't authorise.
        $plan = $this->validateEnhancement($deterministicPlan, $plan);

        // 4. Persist.
        return DateNightPlan::create([
            'questionnaire_id' => $questionnaire->id,
            'partner_one_response_id' => $partnerOneResponse->id,
            'partner_two_response_id' => $secondResponse->id,
            'date_night_theme_id' => $theme instanceof DateNightTheme ? $theme->id : null,
            'partner_user_id' => $isSolo ? $soloPartner?->id : null,
            'compatibility_score' => $score,
            'is_solo' => $isSolo,
            'location_label' => $isSolo ? $partnerOneResponse->location_label : null,
            'local_suggestions' => $localSuggestions,
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
            'ai_enhanced' => $aiEnhanced,
            'fallback_used' => $fallbackUsed,
        ]);
    }

    /**
     * Attempt to enhance the deterministic plan wording via AI.
     * Never lets AI failures surface — always returns a usable plan.
     *
     * For solo (single-user) questionnaires, the user's rough location is
     * passed to the AI so it can propose local spots / areas / events.
     *
     * @param  array<string, string|null>  $plan
     * @param  array<string, mixed>  $compatibility
     * @return array{0: array<string, string|null>, 1: bool, 2: bool, 3: array<int, array<string, string>>|null}
     */
    private function enhanceWithAI(
        array $plan,
        array $compatibility,
        Questionnaire $questionnaire,
        Response $primaryResponse,
        bool $isSolo,
    ): array {
        $location = $isSolo ? $this->buildLocationContext($primaryResponse) : null;

        $context = $this->buildAIContext(
            plan: $plan,
            compatibility: $compatibility,
            questionnaire: $questionnaire,
            primaryResponse: $primaryResponse,
            isSolo: $isSolo,
            location: $location,
        );

        try {
            $response = $this->ai->generateDateNightPlan([
                'context' => $context,
                'plan' => $plan,
                'compatibility' => $compatibility,
                'questionnaire' => $questionnaire->title,
                'is_solo' => $isSolo,
                'location' => $location,
                'enhanceable_fields' => self::ENHANCEABLE_FIELDS,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('AI enhancement threw; falling back to deterministic plan.', [
                'exception' => $e->getMessage(),
                'questionnaire_id' => $questionnaire->id,
            ]);

            return [$plan, false, true, null];
        }

        if (! $response->successful) {
            $this->logger->warning('AI enhancement unsuccessful; using deterministic plan.', [
                'error_code' => $response->errorCode,
                'questionnaire_id' => $questionnaire->id,
            ]);

            return [$plan, false, true, null];
        }

        $enhanced = $plan;
        $touched = false;
        foreach (self::ENHANCEABLE_FIELDS as $field) {
            $value = $response->data[$field] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $enhanced[$field] = $value;
                $touched = true;
            }
        }

        $localSuggestions = null;
        if ($isSolo) {
            $raw = $response->data['local_suggestions'] ?? null;
            if (is_array($raw)) {
                $localSuggestions = $this->sanitiseLocalSuggestions($raw);
            }
        }

        return [$enhanced, $touched, false, $localSuggestions];
    }

    /**
     * Build a rich, structured context payload for the AI prompt.
     *
     * The DB prompt template interpolates `{{context}}` as a JSON blob, so
     * everything the AI needs to tailor the plan MUST live inside this array.
     *
     * @param  array<string, string|null>  $plan
     * @param  array<string, mixed>  $compatibility
     * @param  array<string, string|null>|null  $location
     * @return array<string, mixed>
     */
    private function buildAIContext(
        array $plan,
        array $compatibility,
        Questionnaire $questionnaire,
        Response $primaryResponse,
        bool $isSolo,
        ?array $location,
    ): array {
        $primaryAnswers = $primaryResponse
            ->answers()
            ->with(['question', 'questionOption'])
            ->get();

        $context = [
            'questionnaire' => [
                'title' => $questionnaire->title,
                'description' => $questionnaire->description,
                'is_solo' => $isSolo,
                'is_intimacy' => (bool) $questionnaire->is_intimacy,
                'is_seasonal' => (bool) $questionnaire->is_seasonal,
            ],
            'is_solo' => $isSolo,
            'compatibility' => $compatibility,
            'deterministic_plan' => $plan,
            'enhanceable_fields' => self::ENHANCEABLE_FIELDS,
        ];

        if ($isSolo) {
            $context['user_answers'] = $this->formatAnswers($primaryAnswers);
            $context['location'] = $location;
        } else {
            $context['couple_answers'] = [
                'partner_one' => $this->formatAnswers($primaryAnswers),
                'partner_two' => $this->formatAnswers(
                    Response::query()
                        ->whereKey($this->secondaryResponseIdFor($primaryResponse, $questionnaire))
                        ->with(['answers.question', 'answers.questionOption'])
                        ->first()?->answers ?? collect(),
                ),
            ];
        }

        return $context;
    }

    /**
     * Turn a collection of Answer models into a compact, self-describing
     * structure the AI can reason over (question text, selected option,
     * free-text value, slider value).
     *
     * @param  iterable<int, Answer>  $answers
     * @return list<array<string, mixed>>
     */
    private function formatAnswers(iterable $answers): array
    {
        $formatted = [];

        foreach ($answers as $answer) {
            $question = $answer->question;
            if ($question === null) {
                continue;
            }

            $option = $answer->questionOption;

            $formatted[] = [
                'question' => $question->title,
                'question_description' => $question->description,
                'type' => $question->type?->value,
                'answer' => $option?->title ?? $answer->value,
                'answer_description' => $option?->description,
                'answer_value' => $option?->value ?? $answer->value,
            ];
        }

        return $formatted;
    }

    /**
     * Best-effort lookup of the partner's response id for a couple's
     * questionnaire, used only to reload answers with their questions when
     * building AI context. Falls back to null so the AI still gets partner_one
     * data even if the partner response cannot be resolved here.
     */
    private function secondaryResponseIdFor(Response $primary, Questionnaire $questionnaire): ?int
    {
        return Response::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->where('id', '!=', $primary->id)
            ->orderByDesc('completed_at')
            ->value('id');
    }

    /**
     * @return array<string, string|null>|null
     */
    private function buildLocationContext(Response $response): ?array
    {
        $label = $response->location_label;
        $city = $response->location_city;
        $region = $response->location_region;
        $country = $response->location_country;

        if ($label === null && $city === null && $region === null && $country === null) {
            return null;
        }

        return [
            'label' => $label,
            'city' => $city,
            'region' => $region,
            'country' => $country,
        ];
    }

    /**
     * Keep only well-formed suggestions with a name and description; cap at 6.
     *
     * @param  array<int|string, mixed>  $raw
     * @return list<array<string, string>>
     */
    private function sanitiseLocalSuggestions(array $raw): array
    {
        $suggestions = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = isset($item['name']) && is_string($item['name']) ? trim($item['name']) : '';
            $description = isset($item['description']) && is_string($item['description']) ? trim($item['description']) : '';

            if ($name === '' || $description === '') {
                continue;
            }

            $category = isset($item['category']) && is_string($item['category']) ? trim($item['category']) : '';

            $suggestions[] = [
                'name' => $name,
                'description' => $description,
                'category' => $category,
            ];

            if (count($suggestions) >= 6) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * Ensure AI enhancement never mutates fields it is not allowed to.
     *
     * @param  array<string, string|null>  $deterministic
     * @param  array<string, string|null>  $enhanced
     * @return array<string, string|null>
     */
    private function validateEnhancement(array $deterministic, array $enhanced): array
    {
        foreach ($deterministic as $key => $value) {
            if (! in_array($key, self::ENHANCEABLE_FIELDS, true)) {
                $enhanced[$key] = $value;
            }
        }

        return $enhanced;
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
