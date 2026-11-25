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

    public function test_a_configured_token_is_required(): void
    {
        config()->set('app.health_token', 'monitor-token');

        $this->getJson('/health')->assertNotFound();
        $this->withToken('wrong')->getJson('/health')->assertNotFound();
        $this->withToken('monitor-token')->getJson('/health')->assertOk();
    }

    public function test_it_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/health')->assertOk();
        }

        $this->getJson('/health')->assertTooManyRequests();
    }

    public function test_returns_check_details_for_each_service(): void
    {
        $response = $this->getJson('/health');
        $this->assertSame('ok', $response->json('checks.database.status'));
        $this->assertSame('ok', $response->json('checks.cache.status'));
        $this->assertSame('ok', $response->json('checks.storage.status'));
    }
}
