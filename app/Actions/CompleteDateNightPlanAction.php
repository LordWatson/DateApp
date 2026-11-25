<?php

namespace App\Actions;

use App\Models\DateNightPlan;
use App\Models\User;
use App\Services\NotificationService;

class CompleteDateNightPlanAction
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Record (or change) this person's rating of a date night they did.
     * The partner is told the first time only.
     *
     * @param  string|null  $completedOn  A date (Y-m-d); the user's own today when left out.
     * @return bool True when this was the user's first rating of the plan.
     */
    public function execute(User $user, DateNightPlan $plan, int $rating, ?string $note = null, ?string $completedOn = null): bool
    {
        $isFirst = ! $plan->completedBy()->whereKey($user->id)->exists();

        $plan->completedBy()->syncWithoutDetaching([
            $user->id => [
                'rating' => $rating,
                'note' => $note,
                'completed_on' => $completedOn ?? $user->today()->toDateString(),
            ],
        ]);

        $partner = $user->activePartner();

        if ($isFirst && $partner && $partner->can('view', $plan)) {
            $this->notificationService->notifyPartnerCompletedPlan($partner, $user, $plan);
        }

        return $isFirst;
    }
}
