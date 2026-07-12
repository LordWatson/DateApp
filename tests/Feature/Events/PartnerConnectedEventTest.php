<?php

namespace Tests\Feature\Events;

use App\Events\PartnerConnected;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PartnerConnectedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_fires_partner_connected_event_with_both_users(): void
    {
        Event::fake();

        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        PartnerConnected::dispatch($userOne, $userTwo);

        Event::assertDispatched(PartnerConnected::class, function ($event) use ($userOne, $userTwo): bool {
            return $event->userOne->id === $userOne->id
                && $event->userTwo->id === $userTwo->id;
        });
    }
}
