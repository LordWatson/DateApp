<?php

namespace App\Listeners;

use App\Events\PartnerConnected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

final class InvalidateCacheOnPartnerConnected implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(PartnerConnected $event): void
    {
        Cache::forget("dashboard:{$event->userOne->id}");
        Cache::forget("dashboard:{$event->userTwo->id}");
    }
}
