<?php

namespace Tests\Feature;

use App\Mail\QuestionnaireCompletedMail;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use App\Services\QuestionnaireService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NudgePartnerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    private Questionnaire $questionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->partner = User::factory()->create(['partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);

        $this->questionnaire = Questionnaire::factory()->create(['is_solo' => false]);
    }

    public function test_a_waiting_user_can_remind_their_partner_once_a_day(): void
    {
        Mail::fake();
        $this->waitingResponse();

        $this->actingAs($this->user)
            ->post(route('questionnaires.nudge', $this->questionnaire->slug))
            ->assertSessionHas('success');

        $this->assertSame(1, $this->nudgesFor($this->partner));
        Mail::assertQueued(QuestionnaireCompletedMail::class, fn (QuestionnaireCompletedMail $mail) => $mail->hasTo($this->partner->email));

        $this->actingAs($this->user)
            ->post(route('questionnaires.nudge', $this->questionnaire->slug))
            ->assertSessionHas('error');

        $this->assertSame(1, $this->nudgesFor($this->partner));

        $this->travel(25)->hours();

        $this->actingAs($this->user)
            ->post(route('questionnaires.nudge', $this->questionnaire->slug))
            ->assertSessionHas('success');

        $this->assertSame(2, $this->nudgesFor($this->partner));
    }

    public function test_the_waiting_screen_offers_the_reminder_until_it_is_sent(): void
    {
        $this->waitingResponse();

        $this->actingAs($this->user)
            ->get(route('questionnaires.complete', $this->questionnaire->slug))
            ->assertInertia(fn ($page) => $page->where('can_nudge', true));

        $this->actingAs($this->user)->post(route('questionnaires.nudge', $this->questionnaire->slug));

        $this->actingAs($this->user)
            ->get(route('questionnaires.complete', $this->questionnaire->slug))
            ->assertInertia(fn ($page) => $page->where('can_nudge', false));
    }

    public function test_a_solo_attempt_cannot_nudge(): void
    {
        $this->questionnaire->update(['is_solo' => true]);
        $this->waitingResponse();

        $this->actingAs($this->user)
            ->post(route('questionnaires.nudge', $this->questionnaire->slug))
            ->assertSessionHas('error');

        $this->assertSame(0, $this->nudgesFor($this->partner));
    }

    public function test_a_user_without_a_partner_cannot_nudge(): void
    {
        $loner = User::factory()->create();
        Response::factory()->completed()->create([
            'user_id' => $loner->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $this->actingAs($loner)
            ->post(route('questionnaires.nudge', $this->questionnaire->slug))
            ->assertSessionHas('error');

        $this->assertSame(0, AppNotification::where('type', 'partner_nudge')->count());
    }

    public function test_an_attempt_that_already_has_a_plan_cannot_nudge(): void
    {
        $response = $this->waitingResponse();
        DateNightPlan::factory()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'partner_one_response_id' => $response->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('questionnaires.nudge', $this->questionnaire->slug))
            ->assertSessionHas('error');

        $this->assertSame(0, $this->nudgesFor($this->partner));
    }

    public function test_the_command_reminds_once_after_a_day_of_waiting(): void
    {
        $this->waitingResponse(['completed_at' => now()->subHours(25)]);

        $this->artisan('datenight:nudge-partners')->assertSuccessful();
        $this->artisan('datenight:nudge-partners')->assertSuccessful();

        $this->assertSame(1, $this->nudgesFor($this->partner));
    }

    public function test_the_command_leaves_recent_and_already_nudged_attempts_alone(): void
    {
        $this->waitingResponse(['completed_at' => now()->subHour()]);

        $other = User::factory()->create();
        $otherPartner = User::factory()->create(['partner_id' => $other->id]);
        $other->update(['partner_id' => $otherPartner->id]);
        Response::factory()->completed()->create([
            'user_id' => $other->id,
            'questionnaire_id' => $this->questionnaire->id,
            'completed_at' => now()->subHours(30),
            'partner_nudged_at' => now()->subHours(28),
        ]);

        $this->artisan('datenight:nudge-partners')->assertSuccessful();

        $this->assertSame(0, AppNotification::where('type', 'partner_nudge')->count());
    }

    public function test_the_reminder_disappears_once_the_partner_answers(): void
    {
        $this->waitingResponse();
        $this->actingAs($this->user)->post(route('questionnaires.nudge', $this->questionnaire->slug));
        $this->assertSame(1, $this->nudgesFor($this->partner));

        $partnerResponse = Response::factory()->create([
            'user_id' => $this->partner->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);
        app(QuestionnaireService::class)->completeResponse($partnerResponse);

        $this->assertSame(0, $this->nudgesFor($this->partner));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function waitingResponse(array $attributes = []): Response
    {
        return Response::factory()->completed()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            ...$attributes,
        ]);
    }

    private function nudgesFor(User $user): int
    {
        return AppNotification::where('user_id', $user->id)->where('type', 'partner_nudge')->count();
    }
}
