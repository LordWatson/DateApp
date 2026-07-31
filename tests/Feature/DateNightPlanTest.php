<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateNightPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $userOne;

    private User $userTwo;

    private Questionnaire $questionnaire;

    private Response $responseOne;

    private Response $responseTwo;

    private DateNightPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userOne = User::factory()->create(['onboarding_completed' => true]);
        $this->userTwo = User::factory()->create(['onboarding_completed' => true]);

        $this->userOne->update(['partner_id' => $this->userTwo->id]);
        $this->userTwo->update(['partner_id' => $this->userOne->id]);

        $this->questionnaire = Questionnaire::factory()->create([
            'status' => QuestionnaireStatus::Active,
            'visibility' => QuestionnaireVisibility::Public,
        ]);

        $this->responseOne = Response::factory()->completed()->create([
            'user_id' => $this->userOne->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $this->responseTwo = Response::factory()->completed()->create([
            'user_id' => $this->userTwo->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $this->plan = DateNightPlan::factory()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'partner_one_response_id' => $this->responseOne->id,
            'partner_two_response_id' => $this->responseTwo->id,
            'compatibility_score' => 80,
        ]);
    }

    public function test_user_can_view_their_date_night_plan(): void
    {
        $this->actingAs($this->userOne)
            ->get(route('date-night.show', $this->plan->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('date-night/Show')
                ->has('plan')
                ->where('plan.id', $this->plan->id)
                ->where('plan.compatibility_score', 80)
            );
    }

    public function test_partner_can_also_view_the_plan(): void
    {
        $this->actingAs($this->userTwo)
            ->get(route('date-night.show', $this->plan->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('date-night/Show'));
    }

    public function test_unrelated_user_cannot_view_plan(): void
    {
        $stranger = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($stranger)
            ->get(route('date-night.show', $this->plan->id))
            ->assertForbidden();
    }

    public function test_user_can_view_history(): void
    {
        $this->actingAs($this->userOne)
            ->get(route('date-night.history'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('date-night/History')
                ->has('plans', 1)
            );
    }

    public function test_history_can_be_filtered_by_compatibility(): void
    {
        $this->actingAs($this->userOne)
            ->get(route('date-night.history', ['compatibility' => 'high']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('plans', 1));

        $this->actingAs($this->userOne)
            ->get(route('date-night.history', ['compatibility' => 'low']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('plans', 0));
    }

    public function test_user_can_toggle_favourite(): void
    {
        $this->assertFalse($this->plan->is_favourite);

        $this->actingAs($this->userOne)
            ->post(route('date-night.toggle-favourite', $this->plan->id))
            ->assertRedirect();

        $this->assertTrue($this->plan->fresh()->is_favourite);
    }

    public function test_unrelated_user_cannot_toggle_favourite(): void
    {
        $stranger = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($stranger)
            ->post(route('date-night.toggle-favourite', $this->plan->id))
            ->assertForbidden();
    }

    public function test_user_can_view_favourites(): void
    {
        $this->plan->update(['is_favourite' => true]);

        $this->actingAs($this->userOne)
            ->get(route('date-night.favourites'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('date-night/Favourites')
                ->has('plans', 1)
            );
    }

    public function test_favourites_excludes_non_favourited_plans(): void
    {
        $this->actingAs($this->userOne)
            ->get(route('date-night.favourites'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('plans', 0));
    }

    public function test_user_can_export_plan(): void
    {
        $this->actingAs($this->userOne)
            ->get(route('date-night.export', $this->plan->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_unrelated_user_cannot_export_plan(): void
    {
        $stranger = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($stranger)
            ->get(route('date-night.export', $this->plan->id))
            ->assertForbidden();
    }

    public function test_user_can_like_and_unlike_plan_and_partner_is_notified(): void
    {
        // Like
        $this->actingAs($this->userOne)
            ->post(route('date-night.toggle-like', $this->plan->id))
            ->assertRedirect();

        $this->assertDatabaseHas('date_night_plan_likes', [
            'date_night_plan_id' => $this->plan->id,
            'user_id' => $this->userOne->id,
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->userTwo->id,
            'type' => NotificationType::PartnerLikedPlan->value,
        ]);

        // Unlike
        $this->actingAs($this->userOne)
            ->post(route('date-night.toggle-like', $this->plan->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('date_night_plan_likes', [
            'date_night_plan_id' => $this->plan->id,
            'user_id' => $this->userOne->id,
        ]);

        // Unliking must not produce a second notification
        $this->assertSame(1, AppNotification::query()
            ->where('user_id', $this->userTwo->id)
            ->where('type', NotificationType::PartnerLikedPlan)
            ->count());
    }

    public function test_liking_twice_is_idempotent(): void
    {
        $this->actingAs($this->userOne)
            ->post(route('date-night.toggle-like', $this->plan->id))
            ->assertRedirect();

        // Same user cannot double-like: second call unlikes
        $this->actingAs($this->userOne)
            ->post(route('date-night.toggle-like', $this->plan->id))
            ->assertRedirect();

        $this->assertSame(0, $this->plan->likedBy()->count());
    }

    public function test_unrelated_user_cannot_like_plan(): void
    {
        $stranger = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($stranger)
            ->post(route('date-night.toggle-like', $this->plan->id))
            ->assertForbidden();

        $this->assertDatabaseMissing('date_night_plan_likes', [
            'date_night_plan_id' => $this->plan->id,
            'user_id' => $stranger->id,
        ]);
    }

    public function test_plan_page_exposes_like_state(): void
    {
        $this->plan->likedBy()->attach($this->userOne->id);

        $this->actingAs($this->userOne)
            ->get(route('date-night.show', $this->plan->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('plan.is_liked', true)
                ->where('plan.likes_count', 1)
            );
    }

    public function test_history_search_filters_by_theme(): void
    {
        $this->actingAs($this->userOne)
            ->get(route('date-night.history', ['search' => $this->plan->theme]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('plans', 1));

        $this->actingAs($this->userOne)
            ->get(route('date-night.history', ['search' => 'nonexistentthemexyz']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('plans', 0));
    }
}
