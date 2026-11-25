<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateOutPlanningModeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    private Questionnaire $questionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        // No network: the generator falls back to its rule-based plan.
        config()->set('ai.provider', 'null');

        $this->user = User::factory()->create();
        $this->partner = User::factory()->create(['partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);

        $this->questionnaire = Questionnaire::factory()->create([
            'is_solo' => true,
            'partner_optional' => true,
        ]);

        // Optional free text: these tests are not about answer validation.
        Question::factory()->text()->create(['questionnaire_id' => $this->questionnaire->id, 'display_order' => 1, 'required' => false]);
    }

    public function test_solo_attempt_generates_a_plan_as_soon_as_it_is_finished(): void
    {
        $this->completeAs($this->user, 'solo');

        $response = Response::where('user_id', $this->user->id)->sole();
        $plan = DateNightPlan::sole();

        $this->assertTrue($response->is_solo);
        $this->assertTrue($plan->is_solo);
        $this->assertSame($response->id, $plan->partner_one_response_id);
        $this->assertSame($response->id, $plan->partner_two_response_id);
        $this->assertSame(0, $this->partnerRequests($this->partner)->count());
    }

    public function test_couple_attempt_asks_the_partner_and_plans_once_both_have_answered(): void
    {
        $this->completeAs($this->user, 'couple');

        $this->assertSame(0, DateNightPlan::count());
        $request = $this->partnerRequests($this->partner)->sole();
        $this->assertStringContainsString('wants to plan a date with you', $request->title);
        $this->assertSame($this->questionnaire->slug, $request->data['questionnaire_slug']);

        $this->completeAs($this->partner, 'couple');

        $plan = DateNightPlan::sole();
        $userResponse = Response::where('user_id', $this->user->id)->sole();
        $partnerResponse = Response::where('user_id', $this->partner->id)->sole();

        $this->assertFalse($plan->is_solo);
        $this->assertEqualsCanonicalizing(
            [$userResponse->id, $partnerResponse->id],
            [$plan->partner_one_response_id, $plan->partner_two_response_id],
        );
    }

    public function test_a_solo_attempt_is_never_paired_with_the_partners_couple_attempt(): void
    {
        $this->completeAs($this->partner, 'couple');
        $this->completeAs($this->user, 'solo');

        // Only the user's own solo plan exists; the partner is still waiting.
        $this->assertTrue(DateNightPlan::sole()->is_solo);

        $this->completeAs($this->user, 'couple');

        $couplePlan = DateNightPlan::where('is_solo', false)->sole();
        $userCoupleResponse = Response::where('user_id', $this->user->id)->where('is_solo', false)->sole();
        $partnerResponse = Response::where('user_id', $this->partner->id)->sole();

        $this->assertEqualsCanonicalizing(
            [$userCoupleResponse->id, $partnerResponse->id],
            [$couplePlan->partner_one_response_id, $couplePlan->partner_two_response_id],
        );
    }

    public function test_couple_mode_is_refused_without_a_partner(): void
    {
        $single = User::factory()->create();

        $this->actingAs($single)
            ->post(route('questionnaires.start', $this->questionnaire->slug), ['mode' => 'couple'])
            ->assertSessionHasErrors('mode');

        $this->assertSame(0, Response::where('user_id', $single->id)->count());
    }

    public function test_intro_page_offers_the_choice_and_leads_with_a_waiting_partner(): void
    {
        $this->completeAs($this->partner, 'couple');

        $this->actingAs($this->user)
            ->get(route('questionnaires.show', $this->questionnaire->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/Show')
                ->where('mode.selectable', true)
                ->where('mode.has_partner', true)
                ->where('partner_completed', true));
    }

    private function completeAs(User $user, string $mode): void
    {
        $this->actingAs($user)
            ->post(route('questionnaires.start', $this->questionnaire->slug), ['mode' => $mode])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('questionnaires.finish', $this->questionnaire->slug), [
                'location_label' => 'Bristol',
                'travel_radius_minutes' => $user->is($this->user) ? 60 : 30,
            ])
            ->assertRedirect(route('questionnaires.complete', $this->questionnaire->slug));
    }

    /**
     * @return Builder<AppNotification>
     */
    private function partnerRequests(User $recipient): Builder
    {
        return AppNotification::where('user_id', $recipient->id)
            ->where('type', NotificationType::PartnerCompleted);
    }
}
