<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogViewerAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(?UserRole $role): User
    {
        $roleId = null;

        if ($role !== null) {
            $roleId = Role::factory()->create([
                'name' => $role->value,
                'label' => $role->label(),
            ])->id;
        }

        return User::factory()->create(['role_id' => $roleId]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/log-viewer')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_log_viewer(): void
    {
        $user = $this->userWithRole(null);

        $this->actingAs($user)->get('/log-viewer')->assertForbidden();
    }

    public function test_administrator_cannot_access_log_viewer(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);

        $this->actingAs($admin)->get('/log-viewer')->assertForbidden();
    }

    public function test_super_administrator_can_access_log_viewer(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdministrator);

        $this->actingAs($superAdmin)->get('/log-viewer')->assertOk();
    }
}
