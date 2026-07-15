<?php

namespace App\Listeners;

use App\Events\PartnerConnected;
use App\Services\ActivityLogService;

final class LogActivityOnPartnerConnected
{
    public function __construct(private readonly ActivityLogService $service) {}

    public function handle(PartnerConnected $event): void
    {
        $this->service->log($event->userOne, 'partner_connected', $event->userTwo);
        $this->service->log($event->userTwo, 'partner_connected', $event->userOne);
    }
}
