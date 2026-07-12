<?php

namespace Tests\Feature;

use App\Enums\ChallengeDifficulty;
use App\Models\Challenge;
use App\Models\DailyChallenge;
use Database\Seeders\ChallengeSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_can_be_created(): void
    {
        $challenge = Challenge::factory()->create([
            'title' => 'Cook a meal together',
            'emoji' => '🍳',
            'difficulty' => ChallengeDifficulty::Medium,
            'active' => true,
        ]);

        $this->assertEquals('Cook a meal together', $challenge->title);
        $this->assertEquals(ChallengeDifficulty::Medium, $challenge->difficulty);
        $this->assertTrue($challenge->active);
    }

    public function test_challenge_seeder_creates_100_challenges(): void
    {
        $this->seed(ChallengeSeeder::class);

        $this->assertEquals(100, Challenge::count());
    }

    public function test_all_seeded_challenges_are_active(): void
    {
        $this->seed(ChallengeSeeder::class);

        $this->assertEquals(100, Challenge::where('active', true)->count());
    }

    public function test_seeded_challenges_have_valid_difficulties(): void
    {
        $this->seed(ChallengeSeeder::class);

        $validValues = array_column(ChallengeDifficulty::cases(), 'value');

        Challenge::all()->each(function (Challenge $challenge) use ($validValues) {
            $this->assertContains($challenge->difficulty->value, $validValues);
        });
    }

    public function test_daily_challenge_can_be_assigned(): void
    {
        $challenge = Challenge::factory()->create();

        $daily = DailyChallenge::factory()->create([
            'challenge_id' => $challenge->id,
            'date' => today()->toDateString(),
        ]);

        $this->assertEquals($challenge->id, $daily->challenge->id);
        $this->assertEquals(today()->toDateString(), $daily->date->toDateString());
    }

    public function test_daily_challenge_date_is_unique(): void
    {
        $challenge = Challenge::factory()->create();
        $date = today()->toDateString();

        DailyChallenge::factory()->create([
            'challenge_id' => $challenge->id,
            'date' => $date,
        ]);

        $this->expectException(QueryException::class);

        DailyChallenge::factory()->create([
            'challenge_id' => $challenge->id,
            'date' => $date,
        ]);
    }

    public function test_challenge_has_many_daily_challenges(): void
    {
        $challenge = Challenge::factory()->create();

        DailyChallenge::create(['challenge_id' => $challenge->id, 'date' => today()->subDays(1)->toDateString()]);
        DailyChallenge::create(['challenge_id' => $challenge->id, 'date' => today()->toDateString()]);

        $this->assertCount(2, $challenge->dailyChallenges);
    }
}
