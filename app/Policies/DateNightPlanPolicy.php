<?php

namespace App\Policies;

use App\Models\DateNightPlan;
use App\Models\Response;
use App\Models\User;

/**
 * Authorisation rules for DateNightPlan.
 *
 * A user may act on a plan if they are its explicit partner_user_id or
 * if they own one of the responses that produced the plan.
 */
class DateNightPlanPolicy
{
    public function view(User $user, DateNightPlan $plan): bool
    {
        if ($plan->partner_user_id === $user->id) {
            return true;
        }

        $responseIds = Response::where('user_id', $user->id)->pluck('id');

        return $responseIds->contains($plan->partner_one_response_id)
            || $responseIds->contains($plan->partner_two_response_id);
    }

    public function update(User $user, DateNightPlan $plan): bool
    {
        return $this->view($user, $plan);
    }

    public function favourite(User $user, DateNightPlan $plan): bool
    {
        return $this->view($user, $plan);
    }

    public function like(User $user, DateNightPlan $plan): bool
    {
        return $this->view($user, $plan);
    }

    public function addToCalendar(User $user, DateNightPlan $plan): bool
    {
        return $this->view($user, $plan);
    }
}
