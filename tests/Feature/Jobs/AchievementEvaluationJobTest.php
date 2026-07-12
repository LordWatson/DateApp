<?php

namespace Tests\Feature\Jobs;

use App\Events\AchievementUnlocked;
use App\Jobs\AchievementEvaluationJob;
use App\Models\Achievement;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AchievementEvaluationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_achievement_unlocked_for_newly_unlocked(): void
    {
        Event::fake();
        $user = User::factory()->create(['current_streak' => 10]);
        Achievement::factory()->create(['unlock_condition' => 'streak_days', 'unlock_value' => 5]);
        (new AchievementEvaluationJob($user))->handle(app(AchievementService::class));
        Event::assertDispatched(AchievementUnlocked::class);
    }
}
