<?php

namespace App\Listeners;

use App\Events\MomentCreated;
use App\Services\ActivityLogService;

final class LogActivityOnMomentCreated
{
    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(MomentCreated $event): void
    {
        $this->service->log($event->user, 'moment_created', $event->moment);
    }
}
