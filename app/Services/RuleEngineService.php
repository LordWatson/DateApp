<?php

namespace App\Services;

use App\Models\DateNightTheme;
use Illuminate\Support\Collection;

class RuleEngineService
{
    private const ROMANCE_SLIDER_THRESHOLD = 8;

    private const HIGH_COMPATIBILITY_THRESHOLD = 75;

    private const LOW_COMPATIBILITY_THRESHOLD = 40;

    /**
     * Select the best matching theme based on both partners' answers.
     *
     * @param  Collection<int, mixed>  $userAnswers
     * @param  Collection<int, mixed>  $partnerAnswers
     */
    public function selectTheme(
        Collection $userAnswers,
        Collection $partnerAnswers,
        int $compatibilityScore,
        Collection $availableThemes,
    ): DateNightTheme {
        $scores = $availableThemes->mapWithKeys(function (DateNightTheme $theme) use ($userAnswers, $partnerAnswers, $compatibilityScore) {
            return [$theme->id => $this->scoreTheme($theme, $userAnswers, $partnerAnswers, $compatibilityScore)];
        });

        $bestId = $scores->sortDesc()->keys()->first();

        return $availableThemes->firstWhere('id', $bestId) ?? $availableThemes->first();
    }

    /**
     * Derive activity suggestions from answer values.
     *
     * @param  Collection<int, mixed>  $userAnswers
     * @param  Collection<int, mixed>  $partnerAnswers
     * @return array<string, string|null>
     */
    public function deriveContext(
        Collection $userAnswers,
        Collection $partnerAnswers,
        int $compatibilityScore,
    ): array {
        $allValues = $this->extractValues($userAnswers)
            ->merge($this->extractValues($partnerAnswers));

        return [
            'prefers_relaxing' => $this->hasValue($allValues, ['massage', 'relax', 'cozy', 'slow', 'bath']),
            'prefers_adventure' => $this->hasValue($allValues, ['adventure', 'outdoor', 'active', 'explore']),
            'prefers_music' => $this->hasValue($allValues, ['music', 'dance', 'playlist', 'concert']),
            'prefers_food' => $this->hasValue($allValues, ['cook', 'dinner', 'food', 'restaurant', 'pizza']),
            'prefers_movies' => $this->hasValue($allValues, ['movie', 'film', 'cinema', 'netflix', 'series']),
            'prefers_games' => $this->hasValue($allValues, ['game', 'board', 'play', 'cards']),
            'prefers_outdoors' => $this->hasValue($allValues, ['walk', 'park', 'sunset', 'picnic', 'nature']),
            'romance_high' => $this->getRomanceLevel($userAnswers, $partnerAnswers) >= self::ROMANCE_SLIDER_THRESHOLD,
            'low_compatibility' => $compatibilityScore < self::LOW_COMPATIBILITY_THRESHOLD,
            'high_compatibility' => $compatibilityScore >= self::HIGH_COMPATIBILITY_THRESHOLD,
        ];
    }

    private function scoreTheme(
        DateNightTheme $theme,
        Collection $userAnswers,
        Collection $partnerAnswers,
        int $compatibilityScore,
    ): int {
        $score = 0;
        $name = strtolower($theme->name);
        $context = $this->deriveContext($userAnswers, $partnerAnswers, $compatibilityScore);

        $rules = [
            'cozy' => $context['prefers_relaxing'],
            'candlelit' => $context['romance_high'],
            'movie' => $context['prefers_movies'],
            'pizza' => $context['prefers_food'],
            'rainy' => $context['prefers_relaxing'],
            'sunset' => $context['prefers_outdoors'],
            'game' => $context['prefers_games'],
            'picnic' => $context['prefers_outdoors'],
            'anniversary' => $context['high_compatibility'],
            'coffee' => $context['low_compatibility'],
            'music' => $context['prefers_music'],
            'slow' => $context['prefers_relaxing'],
        ];

        foreach ($rules as $keyword => $matches) {
            if (str_contains($name, $keyword) && $matches) {
                $score += 10;
            }
        }

        return $score;
    }

    private function getRomanceLevel(Collection $userAnswers, Collection $partnerAnswers): float
    {
        $values = $this->extractSliderValues($userAnswers)
            ->merge($this->extractSliderValues($partnerAnswers));

        if ($values->isEmpty()) {
            return 5.0;
        }

        return $values->avg();
    }

    /**
     * @param  Collection<int, mixed>  $answers
     * @return Collection<int, string>
     */
    private function extractValues(Collection $answers): Collection
    {
        return $answers->pluck('value')
            ->filter()
            ->map(fn ($v) => strtolower((string) $v))
            ->values();
    }

    /**
     * @param  Collection<int, mixed>  $answers
     * @return Collection<int, float>
     */
    private function extractSliderValues(Collection $answers): Collection
    {
        return $answers->filter(fn ($a) => is_numeric($a->value ?? null))
            ->pluck('value')
            ->map(fn ($v) => (float) $v)
            ->values();
    }

    /**
     * @param  Collection<int, string>  $values
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
