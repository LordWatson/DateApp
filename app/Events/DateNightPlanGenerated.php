<?php

namespace App\Events;

use App\Models\DateNightPlan;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DateNightPlanGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $userOne,
        public readonly User $userTwo,
        public readonly DateNightPlan $plan,
    ) {}
}
