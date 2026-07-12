<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluateStreaksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_resets_broken_streaks(): void
    {
        $user = User::factory()->create([
            'current_streak' => 5,
            'last_completed_questionnaire_at' => now()->subDays(3),
        ]);
        $this->artisan('datenight:evaluate-streaks')->assertSuccessful();
        $this->assertSame(0, $user->fresh()->current_streak);
    }

    public function test_does_not_reset_active_streaks(): void
    {
        $user = User::factory()->create([
            'current_streak' => 5,
            'last_completed_questionnaire_at' => now()->subHours(12),
        ]);
        $this->artisan('datenight:evaluate-streaks')->assertSuccessful();
        $this->assertSame(5, $user->fresh()->current_streak);
    }
}
