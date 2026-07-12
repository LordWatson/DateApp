<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Challenge;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminChallengeTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::factory()->create(['name' => UserRole::Administrator->value, 'label' => UserRole::Administrator->label()]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_view_challenges(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get('/admin/challenges');

        $response->assertOk();
    }

    public function test_admin_can_create_challenge(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/challenges', [
            'title' => 'Cook dinner together',
            'description' => 'Prepare a meal as a team.',
            'emoji' => '🍳',
            'difficulty' => 'easy',
            'category' => 'cooking',
            'weight' => 1,
            'active' => true,
            'display_order' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('challenges', ['title' => 'Cook dinner together']);
    }

    public function test_admin_can_update_challenge(): void
    {
        $admin = $this->adminUser();
        $challenge = Challenge::factory()->create(['title' => 'Old Title']);

        $response = $this->actingAs($admin)->put("/admin/challenges/{$challenge->id}", [
            'title' => 'New Title',
            'difficulty' => 'medium',
            'weight' => 2,
            'active' => true,
            'display_order' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('challenges', ['id' => $challenge->id, 'title' => 'New Title']);
    }

    public function test_admin_can_delete_challenge(): void
    {
        $admin = $this->adminUser();
        $challenge = Challenge::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/challenges/{$challenge->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('challenges', ['id' => $challenge->id]);
    }

    public function test_admin_can_archive_challenge(): void
    {
        $admin = $this->adminUser();
        $challenge = Challenge::factory()->create(['active' => true, 'archived' => false]);

        $response = $this->actingAs($admin)->post("/admin/challenges/{$challenge->id}/archive");

        $response->assertRedirect();
        $this->assertDatabaseHas('challenges', ['id' => $challenge->id, 'archived' => 1, 'active' => 0]);
    }

    public function test_regular_user_cannot_manage_challenges(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $response = $this->actingAs($user)->get('/admin/challenges');

        $response->assertForbidden();
    }
}
