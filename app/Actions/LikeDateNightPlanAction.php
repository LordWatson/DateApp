<?php

namespace App\Actions;

use App\Models\DateNightPlan;
use App\Models\User;
use App\Services\NotificationService;

class LikeDateNightPlanAction
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Toggle a user's "like" on a date night plan.
     * When a like is added, the partner (if any) receives a notification.
     *
     * @return bool The new liked state (true = liked, false = unliked).
     */
    public function execute(User $user, DateNightPlan $plan): bool
    {
        $alreadyLiked = $plan->isLikedBy($user);

        if ($alreadyLiked) {
            $plan->likedBy()->detach($user->id);

            return false;
        }

        $plan->likedBy()->attach($user->id);

        $partner = $user->partner;

        if ($partner) {
            $this->notificationService->notifyPartnerLikedPlan($partner, $user, $plan);
        }

        return true;
    }
}
