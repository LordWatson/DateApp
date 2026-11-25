<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\DateNightTheme;
use Illuminate\Support\Collection;

class RuleEngineService
{
    /**
     * The fixed vocabulary for `question_options.signals`. Each signal sets one context flag.
     *
     * @var array<string, string>
     */
    public const SIGNALS = [
        'food' => 'prefers_food',
        'outdoors' => 'prefers_outdoors',
        'movies' => 'prefers_movies',
        'games' => 'prefers_games',
        'music' => 'prefers_music',
        'relaxing' => 'prefers_relaxing',
        'adventure' => 'prefers_adventure',
        'romance' => 'romance_high',
    ];

    /**
     * Fallback for options that carry no signals: substring matches on the raw answer value.
     *
     * @var array<string, list<string>>
     */
    private const KEYWORDS = [
        'prefers_relaxing' => ['massage', 'relax', 'cozy', 'slow', 'bath'],
        'prefers_adventure' => ['adventure', 'outdoor', 'active', 'explore'],
        'prefers_music' => ['music', 'dance', 'playlist', 'concert'],
        'prefers_food' => ['cook', 'dinner', 'food', 'restaurant', 'pizza'],
        'prefers_movies' => ['movie', 'film', 'cinema', 'netflix', 'series'],
        'prefers_games' => ['game', 'board', 'play', 'cards'],
        'prefers_outdoors' => ['walk', 'park', 'sunset', 'picnic', 'nature'],
    ];

    /**
     * Theme name keyword => context flag that makes the theme score.
     *
     * @var array<string, string>
     */
    private const THEME_RULES = [
        'cozy' => 'prefers_relaxing',
        'candlelit' => 'romance_high',
        'movie' => 'prefers_movies',
        'pizza' => 'prefers_food',
        'rainy' => 'prefers_relaxing',
        'sunset' => 'prefers_outdoors',
        'game' => 'prefers_games',
        'picnic' => 'prefers_outdoors',
        'anniversary' => 'high_compatibility',
        'coffee' => 'low_compatibility',
        'music' => 'prefers_music',
        'slow' => 'prefers_relaxing',
    ];

    private const ROMANCE_SLIDER_THRESHOLD = 8;

    private const HIGH_COMPATIBILITY_THRESHOLD = 75;

    private const LOW_COMPATIBILITY_THRESHOLD = 40;

    /**
     * Select the best matching theme based on both partners' answers.
     *
     * @param  Collection<int, mixed>  $userAnswers
     * @param  Collection<int, mixed>  $partnerAnswers
     * @param  Collection<int, DateNightTheme>  $availableThemes
     */
    public function selectTheme(
        Collection $userAnswers,
        Collection $partnerAnswers,
        int $compatibilityScore,
        Collection $availableThemes,
    ): DateNightTheme {
        $context = $this->deriveContext($userAnswers, $partnerAnswers, $compatibilityScore);

        $scores = $availableThemes->mapWithKeys(fn (DateNightTheme $theme) => [
            $theme->id => $this->scoreTheme($theme, $context),
        ]);

        $bestId = $scores->sortDesc()->keys()->first();

        return $availableThemes->firstWhere('id', $bestId) ?? $availableThemes->first();
    }

    /**
     * Derive the context flags that drive the plan.
     *
     * An answer whose option has `signals` contributes exactly those signals.
     * Any other answer falls back to keyword matching on its raw value.
     * `romance_high` is set by a `romance` signal, or by romance sliders
     * averaging 8 or more (see isRomanceSlider).
     *
     * @param  Collection<int, mixed>  $userAnswers
     * @param  Collection<int, mixed>  $partnerAnswers
     * @return array<string, bool>
     */
    public function deriveContext(
        Collection $userAnswers,
        Collection $partnerAnswers,
        int $compatibilityScore,
    ): array {
        $answers = $userAnswers->concat($partnerAnswers);

        // `??` keeps plain objects without the relation working.
        $signalsOf = fn ($answer): array => (array) (($answer->questionOption ?? null)->signals ?? []);

        [$tagged, $untagged] = $answers->partition(fn ($answer) => $signalsOf($answer) !== []);

        $signals = $tagged->flatMap($signalsOf)->unique();

        $values = $untagged->pluck('value')
            ->filter()
            ->map(fn ($value) => strtolower((string) $value))
            ->values();

        $context = [];

        foreach (self::KEYWORDS as $flag => $keywords) {
            $context[$flag] = $signals->contains(array_search($flag, self::SIGNALS, true))
                || $this->hasValue($values, $keywords);
        }

        $context['romance_high'] = $signals->contains('romance')
            || $this->getRomanceLevel($answers) >= self::ROMANCE_SLIDER_THRESHOLD;
        $context['low_compatibility'] = $compatibilityScore < self::LOW_COMPATIBILITY_THRESHOLD;
        $context['high_compatibility'] = $compatibilityScore >= self::HIGH_COMPATIBILITY_THRESHOLD;

        return $context;
    }

    /**
     * @param  array<string, bool>  $context
     */
    private function scoreTheme(DateNightTheme $theme, array $context): int
    {
        $score = 0;
        $name = strtolower($theme->name);

        foreach (self::THEME_RULES as $keyword => $flag) {
            if (str_contains($name, $keyword) && $context[$flag]) {
                $score += 10;
            }
        }

        return $score;
    }

    /**
     * Average of the romance sliders, or a neutral 5 when there are none.
     *
     * @param  Collection<int, mixed>  $answers
     */
    private function getRomanceLevel(Collection $answers): float
    {
        $values = $answers
            ->filter(fn ($answer) => is_numeric($answer->value ?? null) && $this->isRomanceSlider($answer))
            ->map(fn ($answer) => (float) $answer->value);

        return $values->isEmpty() ? 5.0 : (float) $values->avg();
    }

    /**
     * Only a slider whose question title mentions romance ("romance" or "romantic")
     * counts, so budgets, energy levels and travel minutes never feed romance_high.
     */
    private function isRomanceSlider(mixed $answer): bool
    {
        $question = $answer->question ?? null;

        return $question !== null
            && $question->type === QuestionType::Slider
            && preg_match('/roman(ce|tic)/i', (string) $question->title) === 1;
    }

    /**
     * @param  Collection<int, lowercase-string&non-falsy-string>  $values
     * @param  array<int, string>  $keywords
     */
    private function hasValue(Collection $values, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($values->contains(fn ($v) => str_contains($v, $keyword))) {
                return true;
            }
        }

        return false;
    }
}
