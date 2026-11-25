<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Models\DateNightPlan;
use App\Models\Moment;
use App\Models\User;
use App\Services\RuntimeSettings;
use Illuminate\Support\Facades\DB;

/**
 * Formats DateNightPlan models into the array payloads consumed by
 * Inertia views and JSON endpoints.
 */
final class DateNightPlanPresenter
{
    public function __construct(
        private readonly RuntimeSettings $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function detail(DateNightPlan $plan, User $viewer): array
    {
        $partnerOne = $plan->partnerOneResponse?->user;
        $partnerTwo = $plan->partnerTwoResponse?->user;

        $plan->loadCount('likedBy');
        $isLiked = $plan->isLikedBy($viewer);

        $completions = DB::table('date_night_plan_completions')->where('date_night_plan_id', $plan->id)->get()->keyBy('user_id');
        $regenerationsLeft = max(0, DateNightPlan::MAX_REGENERATIONS - $plan->regeneration_count);
        // A steer needs the AI, which needs the feature on and everyone in the plan to have agreed.
        $aiAvailable = $this->settings->flag('ai_generation')
            && collect([$partnerOne, $plan->is_solo ? null : $partnerTwo])->filter()->every(fn (User $person): bool => $person->hasAiConsent());

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
            // Per viewer: set by DateNightPlan::scopeWithFavouriteFor or loadExists.
            'is_favourite' => (bool) $plan->is_favourite,
            'ai_enhanced' => (bool) $plan->ai_enhanced,
            'fallback_used' => (bool) $plan->fallback_used,
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
            'completion' => [
                'mine' => $this->completion($completions->get($viewer->id), $viewer),
                'partner' => $this->completion($completions->get($viewer->partner_id), $viewer->partner),
            ],
            'moments' => Moment::query()
                ->where('date_night_plan_id', $plan->id)
                ->whereIn('user_id', array_filter([$viewer->id, $viewer->partner_id]))
                ->latest('date')
                ->get()
                ->map(fn (Moment $moment): array => [
                    'id' => $moment->id,
                    'title' => $moment->title,
                    'mood' => $moment->mood,
                    'photo' => $moment->photoUrls()[0] ?? null,
                ])
                ->all(),
            'last_steer' => $plan->last_steer,
            'regenerations_left' => $regenerationsLeft,
            'can_regenerate' => $aiAvailable && $regenerationsLeft > 0,
            'ai_available' => $aiAvailable,
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
            // One person answered, so there is nothing to compare.
            'compatibility_score' => $plan->is_solo ? null : $plan->compatibility_score,
            'is_solo' => $plan->is_solo,
            'is_favourite' => (bool) $plan->is_favourite,
            // Set by DateNightPlan::scopeWithDone.
            'is_done' => (bool) $plan->is_done,
            'created_at' => $plan->created_at?->toISOString(),
            'questionnaire_title' => $plan->questionnaire?->title,
        ];
    }

    /**
     * @return array{name: string, rating: int, note: string|null, completed_on: string}|null
     */
    private function completion(?\stdClass $row, ?User $person): ?array
    {
        if (! $row || ! $person) {
            return null;
        }

        return [
            'name' => $person->display_name ?? $person->name,
            'rating' => (int) $row->rating,
            'note' => $row->note,
            'completed_on' => substr((string) $row->completed_on, 0, 10),
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
