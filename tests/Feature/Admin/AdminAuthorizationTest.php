<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(UserRole $role = UserRole::Administrator): User
    {
        $roleModel = Role::factory()->create(['name' => $role->value, 'label' => $role->label()]);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    private function createUser(): User
    {
        return User::factory()->create(['role_id' => null]);
    }

    public function test_unauthenticated_user_cannot_access_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdministrator);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }

    public function test_support_can_access_admin_dashboard(): void
    {
        $admin = $this->createAdmin(UserRole::Support);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }

    public function test_moderator_can_access_admin_dashboard(): void
    {
        $admin = $this->createAdmin(UserRole::Moderator);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }

    public function test_user_is_admin_returns_false_for_regular_user(): void
    {
        $user = $this->createUser();

        $this->assertFalse($user->isAdmin());
    }

    public function test_user_is_admin_returns_true_for_admin(): void
    {
        $admin = $this->createAdmin();

        $this->assertTrue($admin->isAdmin());
    }

    public function test_user_is_super_admin_returns_true_for_super_admin(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdministrator);

        $this->assertTrue($admin->isSuperAdmin());
    }

    public function test_user_is_super_admin_returns_false_for_regular_admin(): void
    {
        $admin = $this->createAdmin(UserRole::Administrator);

        $this->assertFalse($admin->isSuperAdmin());
    }
}
