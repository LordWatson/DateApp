<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\AppNotification;
use App\Models\PartnerInvitation;
use App\Models\Role;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\PartnerInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectionAnniversaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_an_invitation_dates_the_connection_for_both_and_disconnecting_clears_it(): void
    {
        $this->travelTo('2026-03-10 09:00:00');
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $invitation = PartnerInvitation::create([
            'sender_id' => $sender->id,
            'email' => $recipient->email,
            'token' => 'token',
            'expires_at' => now()->addDay(),
        ]);
        $service = app(PartnerInvitationService::class);

        $service->acceptInvitation($invitation, $recipient);

        $this->assertTrue(now()->equalTo($sender->fresh()->connected_at));
        $this->assertTrue(now()->equalTo($recipient->fresh()->connected_at));

        $service->disconnectPartner($sender->fresh());

        $this->assertNull($sender->fresh()->connected_at);
        $this->assertNull($recipient->fresh()->connected_at);
    }

    public function test_an_admin_reconnect_dates_the_connection(): void
    {
        $admin = User::factory()->create(['role_id' => Role::factory()->superAdmin()->create()->id]);
        $one = User::factory()->create();
        $two = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.couples.reconnect'), ['user_id' => $one->id, 'partner_id' => $two->id])
            ->assertRedirect();

        $this->assertNotNull($one->fresh()->connected_at);
        $this->assertNotNull($two->fresh()->connected_at);
    }

    public function test_the_partner_page_shows_when_the_couple_connected(): void
    {
        [$user] = $this->couple('2025-03-10 08:00:00');

        $this->actingAs($user)
            ->get(route('partner.index'))
            ->assertInertia(fn ($page) => $page->where('connectedSince', $user->connected_at->toISOString()));
    }

    public function test_the_command_notifies_both_partners_on_the_anniversary_only(): void
    {
        $this->travelTo('2026-03-10 09:00:00');
        [$user, $partner] = $this->couple('2024-03-10 08:00:00');
        [$dayLate] = $this->couple('2025-03-11 08:00:00');
        [$connectedToday] = $this->couple('2026-03-10 08:00:00');

        $this->artisan('datenight:connection-anniversaries')->assertSuccessful();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
            'type' => 'connection_anniversary',
            'title' => '2 years together today',
        ]);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $partner->id, 'type' => 'connection_anniversary']);
        $this->assertSame(2, AppNotification::where('type', 'connection_anniversary')->count());
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $dayLate->id, 'type' => 'connection_anniversary']);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $connectedToday->id, 'type' => 'connection_anniversary']);
    }

    public function test_the_year_together_achievement_unlocks_after_365_days(): void
    {
        $achievement = Achievement::factory()->create(['unlock_condition' => 'days_connected', 'unlock_value' => 365]);
        [$early] = $this->couple(now()->subDays(364));
        [$ready] = $this->couple(now()->subDays(365));
        $single = User::factory()->create(['connected_at' => now()->subDays(400)]);
        $service = app(AchievementService::class);

        $this->assertEmpty($service->checkAndAward($early));
        $this->assertEmpty($service->checkAndAward($single));
        $this->assertTrue($service->checkAndAward($ready)[0]->is($achievement));
    }

    /**
     * @return array{User, User}
     */
    private function couple(mixed $connectedAt): array
    {
        $user = User::factory()->create(['connected_at' => $connectedAt]);
        $partner = User::factory()->create(['partner_id' => $user->id, 'connected_at' => $connectedAt]);
        $user->update(['partner_id' => $partner->id]);

        return [$user, $partner];
    }
}
