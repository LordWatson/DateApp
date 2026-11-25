<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\DateNightPlan;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::factory()->superAdmin()->create()->id]);
    }

    private function activity(User $user, CarbonInterface $at): void
    {
        $log = new ActivityLog(['user_id' => $user->id, 'action' => 'moment_created']);
        $log->created_at = $at;
        $log->save();
    }

    public function test_active_users_are_users_with_activity_not_sign_ups(): void
    {
        $this->travelTo('2026-11-25 12:00:00');

        $admin = $this->admin();
        [$busy, $occasional, $lapsed] = User::factory()->count(3)->create();
        // Signed up today and did nothing: not active.
        User::factory()->create();

        $this->activity($busy, now()->subDay());
        $this->activity($busy, now()->subDay());
        $this->activity($busy, now()->subDays(3));
        $this->activity($occasional, now()->subDay());
        $this->activity($lapsed, now()->subMonths(2));

        $this->actingAs($admin)->get('/admin/analytics')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/analytics/Index')
                ->where('active_users', 2)
                ->where('dau', [
                    ['date' => '2026-11-22', 'count' => 1],
                    ['date' => '2026-11-24', 'count' => 2],
                ])
                ->has('mau', 12)
                ->where('mau.9', ['month' => '2026-09', 'count' => 1])
                ->where('mau.10', ['month' => '2026-10', 'count' => 0])
                ->where('mau.11', ['month' => '2026-11', 'count' => 2])
            );
    }

    public function test_average_compatibility_comes_from_couple_plans(): void
    {
        DateNightPlan::factory()->create(['compatibility_score' => 80]);
        DateNightPlan::factory()->create(['compatibility_score' => 65]);
        // A solo plan has nobody to be compatible with.
        DateNightPlan::factory()->create(['compatibility_score' => 0, 'is_solo' => true]);

        $this->actingAs($this->admin())->get('/admin/analytics')
            ->assertInertia(fn (Assert $page) => $page->where('avg_compatibility', 72.5));
    }

    public function test_average_compatibility_is_zero_without_plans(): void
    {
        $this->actingAs($this->admin())->get('/admin/analytics')
            ->assertInertia(fn (Assert $page) => $page->where('avg_compatibility', 0));
    }
}
