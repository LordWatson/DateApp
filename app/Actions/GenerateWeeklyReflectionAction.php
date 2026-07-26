<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\WeeklyReflection;
use App\Services\WeeklyReflectionService;

/**
 * Single-purpose action: (re)generate the current week's reflection for a user.
 *
 * Delegates all heavy lifting to WeeklyReflectionService so controllers,
 * jobs and console commands can share the same behaviour.
 */
final readonly class GenerateWeeklyReflectionAction
{
    public function __construct(
        private WeeklyReflectionService $service,
    ) {}

    public function execute(User $user, bool $force = false): WeeklyReflection
    {
        return $force
            ? $this->service->regenerateCurrent($user)
            : $this->service->currentOrGenerate($user);
    }
}
