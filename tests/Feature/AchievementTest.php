<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    public function test_user_can_view_achievements(): void
    {
        $response = $this->actingAs($this->user)->get(route('achievements.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('achievements/Index'));
    }

    public function test_achievements_page_shows_progress(): void
    {
        Achievement::create([
            'name' => 'First Date Night',
            'description' => 'Complete your first questionnaire.',
            'emoji' => '❤️',
            'category' => 'questionnaires',
            'points' => 10,
            'hidden' => false,
            'unlock_condition' => 'questionnaires_completed',
            'unlock_value' => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('achievements.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('achievements/Index')
            ->has('progress')
            ->has('achievements')
        );
    }

    public function test_achievement_service_awards_on_condition(): void
    {
        $achievement = Achievement::create([
            'name' => 'Test Achievement',
            'description' => 'Test.',
            'emoji' => '🎯',
            'category' => 'questionnaires',
            'points' => 10,
            'hidden' => false,
            'unlock_condition' => 'streak_days',
            'unlock_value' => 1,
        ]);

        $this->user->update(['current_streak' => 5]);

        $service = app(AchievementService::class);
        $unlocked = $service->checkAndAward($this->user);

        $this->assertCount(1, $unlocked);
        $this->assertEquals($achievement->id, $unlocked[0]->id);
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $this->user->id,
            'achievement_id' => $achievement->id,
        ]);
    }
}
