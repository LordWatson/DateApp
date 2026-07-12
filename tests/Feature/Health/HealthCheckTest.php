<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_healthy_status(): void
    {
        $response = $this->getJson('/health');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'timestamp', 'checks' => ['database', 'cache', 'queue', 'storage']])
            ->assertJsonPath('status', 'healthy');
    }

    public function test_returns_check_details_for_each_service(): void
    {
        $response = $this->getJson('/health');
        $this->assertSame('ok', $response->json('checks.database.status'));
        $this->assertSame('ok', $response->json('checks.cache.status'));
        $this->assertSame('ok', $response->json('checks.storage.status'));
    }
}
