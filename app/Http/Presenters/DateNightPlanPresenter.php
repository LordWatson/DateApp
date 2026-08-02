<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Models\DateNightPlan;
use App\Models\User;

/**
 * Formats DateNightPlan models into the array payloads consumed by
 * Inertia views and JSON endpoints.
 */
final class DateNightPlanPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function detail(DateNightPlan $plan, User $viewer): array
    {
        $partnerOne = $plan->partnerOneResponse?->user;
        $partnerTwo = $plan->partnerTwoResponse?->user;

        $plan->loadCount('likedBy');
        $isLiked = $plan->isLikedBy($viewer);

        return [
            'id' => $plan->id,
            'theme' => $plan->theme,
            'theme_emoji' => $plan->theme_emoji,
            'compatibility_score' => $plan->compatibility_score,
            'summary' => $plan->summary,
            'meal_suggestion' => $plan->meal_suggestion,
            'atmosphere' => $plan->atmosphere,
            'activity' => $plan->activity,
            'conversation_prompt' => $plan->conversation_prompt,
            'romantic_challenge' => $plan->romantic_challenge,
            'is_solo' => $plan->is_solo,
            'location_label' => $plan->location_label,
            'local_suggestions' => $plan->local_suggestions ?? [],
            'is_favourite' => $plan->is_favourite,
            'is_liked' => $isLiked,
            'likes_count' => (int) ($plan->liked_by_count ?? 0),
            'created_at' => $plan->created_at?->toISOString(),
            'questionnaire' => [
                'id' => $plan->questionnaire?->id,
                'title' => $plan->questionnaire?->title,
                'slug' => $plan->questionnaire?->slug,
            ],
            'partner_one' => $this->partner($partnerOne),
            'partner_two' => $this->partner($partnerTwo),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(DateNightPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'theme' => $plan->theme,
            'theme_emoji' => $plan->theme_emoji,
            'compatibility_score' => $plan->compatibility_score,
            'is_favourite' => $plan->is_favourite,
            'created_at' => $plan->created_at?->toISOString(),
            'questionnaire_title' => $plan->questionnaire?->title,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function partner(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->display_name ?? $user->name,
            'avatar' => $user->avatar,
        ];
    }
}
