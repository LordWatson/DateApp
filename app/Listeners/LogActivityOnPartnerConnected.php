<?php

namespace App\Listeners;

use App\Events\PartnerConnected;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogActivityOnPartnerConnected implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(PartnerConnected $event): void
    {
        $this->service->log($event->userOne, 'partner_connected', $event->userTwo);
        $this->service->log($event->userTwo, 'partner_connected', $event->userOne);
    }
}
