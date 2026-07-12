<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::factory()->create(['name' => UserRole::Administrator->value, 'label' => UserRole::Administrator->label()]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_view_users_index(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
    }

    public function test_admin_can_view_user_detail(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}");

        $response->assertOk();
    }

    public function test_admin_can_update_user(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => 'New Name',
            'email' => $user->email,
            'role_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_admin_can_suspend_user(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['is_suspended' => false]);

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/suspend", [
            'reason' => 'Violation of terms.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_suspended' => 1,
            'suspension_reason' => 'Violation of terms.',
        ]);
    }

    public function test_admin_can_unsuspend_user(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['is_suspended' => true]);

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/unsuspend");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_suspended' => false]);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/users/{$user->id}");

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_disconnect_user_from_partner(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $partner = User::factory()->create();
        $user->update(['partner_id' => $partner->id]);
        $partner->update(['partner_id' => $user->id]);

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/disconnect");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'partner_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $partner->id, 'partner_id' => null]);
    }

    public function test_regular_user_cannot_manage_users(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $response = $this->actingAs($user)->get('/admin/users');

        $response->assertForbidden();
    }

    public function test_admin_can_search_users(): void
    {
        $admin = $this->adminUser();
        User::factory()->create(['name' => 'Searchable Person']);
        User::factory()->create(['name' => 'Other Person']);

        $response = $this->actingAs($admin)->get('/admin/users?search=Searchable');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 1)
        );
    }
}
