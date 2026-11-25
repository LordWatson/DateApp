<?php

namespace Tests\Feature;

use App\Models\DateNightPlan;
use App\Models\Moment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnThisDayTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-06-15 12:00:00');

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->partner = User::factory()->create(['partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);
    }

    public function test_moments_from_this_date_in_earlier_years_are_shown_newest_first(): void
    {
        Moment::factory()->create(['user_id' => $this->partner->id, 'title' => 'Two years back', 'date' => '2024-06-15']);
        Moment::factory()->create(['user_id' => $this->user->id, 'title' => 'Last year', 'date' => '2025-06-15']);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('onThisDay', 2)
                ->where('onThisDay.0.title', 'Last year')
                ->where('onThisDay.0.years_ago', 1)
                ->where('onThisDay.0.type', 'moment')
                ->where('onThisDay.1.title', 'Two years back')
                ->where('onThisDay.1.years_ago', 2)
            );
    }

    public function test_a_date_night_from_this_date_is_shown(): void
    {
        $plan = DateNightPlan::factory()->create([
            'partner_user_id' => $this->user->id,
            'theme' => 'Candlelit Dinner',
            'created_at' => '2025-06-15 19:00:00',
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('onThisDay', 1)
                ->where('onThisDay.0.type', 'plan')
                ->where('onThisDay.0.title', 'Candlelit Dinner')
                ->where('onThisDay.0.url', route('date-night.show', $plan))
            );
    }

    public function test_other_dates_this_year_and_other_people_are_left_out(): void
    {
        Moment::factory()->create(['user_id' => $this->user->id, 'date' => '2025-06-14']);
        Moment::factory()->create(['user_id' => $this->user->id, 'date' => '2026-06-15']);
        Moment::factory()->create(['date' => '2025-06-15']);
        DateNightPlan::factory()->create(['created_at' => '2025-06-15 19:00:00']);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('onThisDay', []));
    }
}
