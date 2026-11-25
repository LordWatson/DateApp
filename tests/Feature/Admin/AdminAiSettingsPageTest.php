<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAiSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_the_ai_settings_page(): void
    {
        $admin = User::factory()->create(['role_id' => Role::factory()->superAdmin()->create()->id]);

        $this->actingAs($admin)
            ->get('/admin/ai-settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/ai-settings/Index')
                ->where('providers', ['anthropic', 'deepseek', 'null'])
                ->has('settings.provider')
                ->has('settings.model')
                ->has('analytics.by_feature')
                ->has('useCases')
            );
    }

    public function test_other_admin_roles_cannot_open_ai_settings(): void
    {
        $administrator = User::factory()->create(['role_id' => Role::factory()->create()->id]);

        $this->actingAs($administrator)->get('/admin/ai-settings')->assertForbidden();
        $this->actingAs($administrator)->post('/admin/ai-settings/test-connection')->assertForbidden();
    }

    public function test_regular_user_cannot_open_ai_settings(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/ai-settings')->assertForbidden();
    }
}
