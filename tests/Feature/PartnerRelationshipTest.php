<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_be_linked_as_partners(): void
    {
        $alex = User::factory()->create();
        $eliza = User::factory()->create();

        $alex->update(['partner_id' => $eliza->id]);
        $eliza->update(['partner_id' => $alex->id]);

        $this->assertEquals($eliza->id, $alex->fresh()->partner_id);
        $this->assertEquals($alex->id, $eliza->fresh()->partner_id);
    }

    public function test_partner_relationship_returns_correct_user(): void
    {
        $alex = User::factory()->create();
        $eliza = User::factory()->create();

        $alex->update(['partner_id' => $eliza->id]);

        $this->assertEquals($eliza->id, $alex->fresh()->partner->id);
        $this->assertEquals($eliza->name, $alex->fresh()->partner->name);
    }

    public function test_partner_id_can_be_null(): void
    {
        $user = User::factory()->create(['partner_id' => null]);

        $this->assertNull($user->partner_id);
        $this->assertNull($user->partner);
    }

    public function test_partner_is_nulled_when_partner_user_is_deleted(): void
    {
        $alex = User::factory()->create();
        $eliza = User::factory()->create(['partner_id' => $alex->id]);

        $alex->delete();

        $this->assertNull($eliza->fresh()->partner_id);
    }

    public function test_user_responses_relationship_exists(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(HasMany::class, $user->responses());
    }

    public function test_user_saved_profiles_relationship_exists(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(HasMany::class, $user->savedProfiles());
    }
}
