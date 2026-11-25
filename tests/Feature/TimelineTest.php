<?php

namespace Tests\Feature;

use App\Models\Moment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    public function test_user_can_view_timeline(): void
    {
        $response = $this->actingAs($this->user)->get(route('timeline.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('timeline/Index'));
    }

    public function test_timeline_accepts_filter(): void
    {
        $response = $this->actingAs($this->user)->get(route('timeline.index', ['filter' => 'moments']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('timeline/Index')
            ->where('filter', 'moments')
        );
    }

    public function test_timeline_shows_moments(): void
    {
        Moment::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Sunset Walk',
        ]);

        $response = $this->actingAs($this->user)->get(route('timeline.index', ['filter' => 'moments']));

        $response->assertInertia(fn ($page) => $page
            ->component('timeline/Index')
            ->has('entries', 1)
        );
    }

    public function test_dropping_the_unused_tables_can_be_rolled_back(): void
    {
        $migration = require database_path('migrations/2026_11_25_064636_drop_ai_prompts_and_timeline_entries_tables.php');

        $this->assertFalse(Schema::hasTable('ai_prompts'));
        $this->assertFalse(Schema::hasTable('timeline_entries'));

        $migration->down();

        $this->assertTrue(Schema::hasColumns('ai_prompts', ['key', 'label', 'type', 'content', 'version', 'active']));
        $this->assertTrue(Schema::hasColumns('timeline_entries', ['user_id', 'type', 'title', 'subject_type', 'subject_id', 'occurred_at']));

        $migration->up();

        $this->assertFalse(Schema::hasTable('ai_prompts'));
        $this->assertFalse(Schema::hasTable('timeline_entries'));
    }
}
