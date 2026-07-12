<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\FeatureFlag;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::factory()->create(['name' => UserRole::Administrator->value, 'label' => UserRole::Administrator->label()]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_view_feature_flags(): void
    {
        $admin = $this->adminUser();
        FeatureFlag::create(['key' => 'test_flag', 'label' => 'Test Flag', 'enabled' => true, 'group' => 'features']);

        $response = $this->actingAs($admin)->get('/admin/feature-flags');

        $response->assertOk();
    }

    public function test_admin_can_toggle_feature_flag(): void
    {
        $admin = $this->adminUser();
        $flag = FeatureFlag::create(['key' => 'test_flag', 'label' => 'Test Flag', 'enabled' => true, 'group' => 'features']);

        $response = $this->actingAs($admin)->put("/admin/feature-flags/{$flag->id}", [
            'enabled' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feature_flags', ['id' => $flag->id, 'enabled' => false]);
    }

    public function test_feature_flag_is_enabled_helper(): void
    {
        FeatureFlag::create(['key' => 'my_feature', 'label' => 'My Feature', 'enabled' => true, 'group' => 'features']);

        $this->assertTrue(FeatureFlag::isEnabled('my_feature'));
    }

    public function test_feature_flag_is_disabled_helper(): void
    {
        FeatureFlag::create(['key' => 'my_feature', 'label' => 'My Feature', 'enabled' => false, 'group' => 'features']);

        $this->assertFalse(FeatureFlag::isEnabled('my_feature'));
    }

    public function test_feature_flag_returns_false_for_missing_key(): void
    {
        $this->assertFalse(FeatureFlag::isEnabled('nonexistent_flag'));
    }

    public function test_regular_user_cannot_access_feature_flags(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $response = $this->actingAs($user)->get('/admin/feature-flags');

        $response->assertForbidden();
    }
}
