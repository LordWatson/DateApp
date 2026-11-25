<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_registers_a_device_token_and_it_follows_the_signed_in_user(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->postJson('/device-tokens', ['token' => 'abc', 'platform' => 'android'])->assertNoContent();
        $this->actingAs($second)->postJson('/device-tokens', ['token' => 'abc', 'platform' => 'android'])->assertNoContent();

        $this->assertSame([$second->id], DeviceToken::pluck('user_id')->all());
    }

    public function test_guests_cannot_register_a_device_token(): void
    {
        $this->postJson('/device-tokens', ['token' => 'abc', 'platform' => 'android'])->assertUnauthorized();
    }

    public function test_creating_a_notification_pushes_to_each_device_and_drops_dead_tokens(): void
    {
        $user = User::factory()->create();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'live', 'platform' => 'android']);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'dead', 'platform' => 'android']);

        $this->mock(FcmService::class, function (MockInterface $fcm): void {
            $fcm->shouldReceive('isConfigured')->andReturnTrue();
            $fcm->shouldReceive('send')->once()->with('live', 'Hello', 'Body', \Mockery::any())->andReturnTrue();
            $fcm->shouldReceive('send')->once()->with('dead', 'Hello', 'Body', \Mockery::any())->andReturnFalse();
        });

        $this->notify($user);

        $this->assertSame(['live'], DeviceToken::pluck('token')->all());
    }

    public function test_no_push_is_sent_when_the_user_turned_push_off(): void
    {
        $user = User::factory()->create(['push_notifications' => false]);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'live', 'platform' => 'android']);

        $this->mock(FcmService::class, function (MockInterface $fcm): void {
            $fcm->shouldReceive('isConfigured')->andReturnTrue();
            $fcm->shouldNotReceive('send');
        });

        $this->notify($user);
    }

    public function test_signing_out_removes_the_users_device_tokens(): void
    {
        $user = User::factory()->create();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'live', 'platform' => 'android']);

        $this->actingAs($user)->post('/logout');

        $this->assertSame(0, DeviceToken::count());
    }

    private function notify(User $user): void
    {
        AppNotification::create([
            'user_id' => $user->id,
            'type' => NotificationType::LoveNoteReceived,
            'title' => 'Hello',
            'body' => 'Body',
        ]);
    }
}
