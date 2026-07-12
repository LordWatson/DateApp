<?php

namespace App\Listeners;

use App\Events\MomentCreated;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogActivityOnMomentCreated implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(MomentCreated $event): void
    {
        $this->service->log($event->user, 'moment_created', $event->moment);
    }
}
