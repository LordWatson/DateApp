<?php

namespace Tests\Feature\Jobs;

use App\Jobs\DailyChallengeSelectionJob;
use App\Models\Challenge;
use App\Models\DailyChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyChallengeSelectionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_daily_challenge_for_today(): void
    {
        Challenge::factory()->create();
        (new DailyChallengeSelectionJob)->handle();
        $this->assertTrue(DailyChallenge::whereDate('date', now()->toDateString())->exists());
    }

    public function test_does_not_create_duplicate_daily_challenge(): void
    {
        $challenge = Challenge::factory()->create();
        DailyChallenge::create(['date' => now()->toDateString(), 'challenge_id' => $challenge->id]);
        (new DailyChallengeSelectionJob)->handle();
        $this->assertSame(1, DailyChallenge::whereDate('date', now()->toDateString())->count());
    }
}
