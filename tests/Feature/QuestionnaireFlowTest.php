<?php

namespace Tests\Feature;

use App\Enums\CompletionStatus;
use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\SavedProfile;
use App\Models\SavedProfileAnswer;
use App\Models\User;
use App\Services\CompatibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Questionnaire $questionnaire;

    private Question $question;

    private QuestionOption $option;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['onboarding_completed' => true]);

        $this->questionnaire = Questionnaire::factory()->create([
            'status' => QuestionnaireStatus::Active,
            'visibility' => QuestionnaireVisibility::Public,
            'slug' => 'tonight',
        ]);

        $this->question = Question::factory()->singleChoice()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'display_order' => 1,
            'required' => true,
        ]);

        $this->option = QuestionOption::factory()->create([
            'question_id' => $this->question->id,
            'value' => 'dominant',
            'title' => 'Dominant',
        ]);
    }

    // ─── Index ───────────────────────────────────────────────────────────────

    public function test_guests_cannot_access_questionnaire_index(): void
    {
        $this->get(route('questionnaires.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_questionnaire_list(): void
    {
        $this->actingAs($this->user)
            ->get(route('questionnaires.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/Index')
                ->has('questionnaires')
            );
    }

    public function test_only_active_questionnaires_are_listed(): void
    {
        Questionnaire::factory()->create(['status' => QuestionnaireStatus::Draft]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.index'))
            ->assertInertia(fn ($page) => $page
                ->has('questionnaires', 1)
            );
    }

    // ─── Show (Introduction) ─────────────────────────────────────────────────

    public function test_user_can_view_questionnaire_introduction(): void
    {
        $this->actingAs($this->user)
            ->get(route('questionnaires.show', $this->questionnaire->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/Show')
                ->has('questionnaire')
                ->where('response', null)
            );
    }

    public function test_introduction_shows_in_progress_response(): void
    {
        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.show', $this->questionnaire->slug))
            ->assertInertia(fn ($page) => $page
                ->where('response.status', 'in_progress')
            );
    }

    // ─── Start ───────────────────────────────────────────────────────────────

    public function test_starting_questionnaire_creates_response(): void
    {
        $this->actingAs($this->user)
            ->post(route('questionnaires.start', $this->questionnaire->slug))
            ->assertRedirect();

        $this->assertDatabaseHas('responses', [
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);
    }

    public function test_starting_questionnaire_redirects_to_first_question(): void
    {
        $this->actingAs($this->user)
            ->post(route('questionnaires.start', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.question', [$this->questionnaire->slug, 1]));
    }

    public function test_starting_again_resumes_from_unanswered_question(): void
    {
        $response = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);

        $q2 = Question::factory()->text()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'display_order' => 2,
        ]);

        Answer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $this->question->id,
            'value' => 'dominant',
        ]);

        $this->actingAs($this->user)
            ->post(route('questionnaires.start', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.question', [$this->questionnaire->slug, 2]));
    }

    // ─── Question ────────────────────────────────────────────────────────────

    public function test_user_can_view_a_question(): void
    {
        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.question', [$this->questionnaire->slug, 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/Question')
                ->has('question')
                ->has('progress')
            );
    }

    public function test_invalid_question_order_redirects_to_intro(): void
    {
        $this->actingAs($this->user)
            ->get(route('questionnaires.question', [$this->questionnaire->slug, 999]))
            ->assertRedirect(route('questionnaires.show', $this->questionnaire->slug));
    }

    public function test_question_restores_previous_answer(): void
    {
        $response = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        Answer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->option->id,
            'value' => 'dominant',
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.question', [$this->questionnaire->slug, 1]))
            ->assertInertia(fn ($page) => $page
                ->where('current_answer', 'dominant')
            );
    }

    // ─── Answer (Auto-save) ──────────────────────────────────────────────────

    public function test_answer_is_saved_via_ajax(): void
    {
        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $this->actingAs($this->user)
            ->postJson(
                route('questionnaires.answer', [$this->questionnaire->slug, 1]),
                ['value' => 'dominant'],
            )
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertDatabaseHas('answers', ['value' => 'dominant']);
    }

    public function test_answer_replaces_previous_single_choice_answer(): void
    {
        $response = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        Answer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $this->question->id,
            'value' => 'dominant',
        ]);

        $this->actingAs($this->user)
            ->postJson(
                route('questionnaires.answer', [$this->questionnaire->slug, 1]),
                ['value' => 'submissive'],
            )
            ->assertOk();

        $this->assertDatabaseMissing('answers', ['value' => 'dominant']);
        $this->assertDatabaseHas('answers', ['value' => 'submissive']);
    }

    public function test_multiple_choice_answer_saves_multiple_rows(): void
    {
        $question = Question::factory()->multipleChoice()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'display_order' => 2,
        ]);

        QuestionOption::factory()->create(['question_id' => $question->id, 'value' => 'candles']);
        QuestionOption::factory()->create(['question_id' => $question->id, 'value' => 'music']);

        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $this->actingAs($this->user)
            ->postJson(
                route('questionnaires.answer', [$this->questionnaire->slug, 2]),
                ['value' => ['candles', 'music']],
            )
            ->assertOk();

        $this->assertEquals(2, Answer::where('question_id', $question->id)->count());
    }

    public function test_answer_requires_value_field(): void
    {
        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $this->actingAs($this->user)
            ->postJson(
                route('questionnaires.answer', [$this->questionnaire->slug, 1]),
                [],
            )
            ->assertUnprocessable();
    }

    // ─── Summary ─────────────────────────────────────────────────────────────

    public function test_user_can_view_summary(): void
    {
        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.summary', $this->questionnaire->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/Summary')
                ->has('grouped_answers')
            );
    }

    public function test_summary_redirects_if_no_response(): void
    {
        $this->actingAs($this->user)
            ->get(route('questionnaires.summary', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.show', $this->questionnaire->slug));
    }

    // ─── Finish ───────────────────────────────────────────────────────────────

    public function test_finish_marks_response_as_completed(): void
    {
        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);

        $this->actingAs($this->user)
            ->post(route('questionnaires.finish', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.complete', $this->questionnaire->slug));

        $this->assertDatabaseHas('responses', [
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed->value,
        ]);
    }

    // ─── Complete ─────────────────────────────────────────────────────────────

    public function test_user_can_view_completion_screen(): void
    {
        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.complete', $this->questionnaire->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/Complete')
            );
    }

    // ─── Saved Profiles ───────────────────────────────────────────────────────

    public function test_user_can_save_a_profile(): void
    {
        $response = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
        ]);

        Answer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $this->question->id,
            'value' => 'dominant',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('saved-profiles.store'), [
                'name' => 'Romantic Evening',
                'emoji' => '❤️',
                'colour' => '#EC4899',
                'questionnaire_id' => $this->questionnaire->id,
            ])
            ->assertOk()
            ->assertJsonPath('profile.name', 'Romantic Evening');

        $this->assertDatabaseHas('saved_profiles', [
            'user_id' => $this->user->id,
            'name' => 'Romantic Evening',
        ]);

        $this->assertDatabaseHas('saved_profile_answers', [
            'question_id' => $this->question->id,
            'value' => 'dominant',
        ]);
    }

    public function test_applying_saved_profile_populates_answers(): void
    {
        $profile = SavedProfile::factory()->create(['user_id' => $this->user->id]);

        SavedProfileAnswer::factory()->create([
            'saved_profile_id' => $profile->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->option->id,
            'value' => 'dominant',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('saved-profiles.apply', [$profile->id, $this->questionnaire->slug]))
            ->assertOk()
            ->assertJson(['applied' => true]);

        $this->assertDatabaseHas('answers', [
            'question_id' => $this->question->id,
            'value' => 'dominant',
        ]);
    }

    public function test_user_cannot_apply_another_users_profile(): void
    {
        $otherUser = User::factory()->create(['onboarding_completed' => true]);
        $profile = SavedProfile::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->postJson(route('saved-profiles.apply', [$profile->id, $this->questionnaire->slug]))
            ->assertForbidden();
    }

    // ─── Compatibility ────────────────────────────────────────────────────────

    public function test_compatibility_page_shows_waiting_when_partner_not_completed(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $this->user->update(['partner_id' => $partner->id]);

        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.compatibility', $this->questionnaire->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/Compatibility')
                ->where('partner_completed', false)
                ->where('compatibility', null)
            );
    }

    public function test_compatibility_is_calculated_when_both_completed(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $this->user->update(['partner_id' => $partner->id]);

        $userResponse = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        $partnerResponse = Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        Answer::factory()->create([
            'response_id' => $userResponse->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->option->id,
            'value' => 'dominant',
        ]);

        Answer::factory()->create([
            'response_id' => $partnerResponse->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->option->id,
            'value' => 'dominant',
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.compatibility', $this->questionnaire->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('partner_completed', true)
                ->has('compatibility')
            );
    }

    // ─── CompatibilityService Unit ────────────────────────────────────────────

    public function test_compatibility_service_returns_100_for_identical_answers(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $this->user->update(['partner_id' => $partner->id]);

        $userResponse = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        $partnerResponse = Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        Answer::factory()->create([
            'response_id' => $userResponse->id,
            'question_id' => $this->question->id,
            'value' => 'dominant',
        ]);

        Answer::factory()->create([
            'response_id' => $partnerResponse->id,
            'question_id' => $this->question->id,
            'value' => 'dominant',
        ]);

        $service = app(CompatibilityService::class);
        $result = $service->calculate($this->user, $this->questionnaire);

        $this->assertNotNull($result);
        $this->assertEquals(100, $result['percentage']);
    }

    public function test_compatibility_service_returns_0_for_different_answers(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $this->user->update(['partner_id' => $partner->id]);

        $option2 = QuestionOption::factory()->create([
            'question_id' => $this->question->id,
            'value' => 'submissive',
        ]);

        $userResponse = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        $partnerResponse = Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        Answer::factory()->create([
            'response_id' => $userResponse->id,
            'question_id' => $this->question->id,
            'value' => 'dominant',
        ]);

        Answer::factory()->create([
            'response_id' => $partnerResponse->id,
            'question_id' => $this->question->id,
            'value' => 'submissive',
        ]);

        $service = app(CompatibilityService::class);
        $result = $service->calculate($this->user, $this->questionnaire);

        $this->assertNotNull($result);
        $this->assertEquals(0, $result['percentage']);
    }

    // ─── Partner Answers ──────────────────────────────────────────────────────

    public function test_partner_answers_redirects_when_no_partner(): void
    {
        $this->actingAs($this->user)
            ->get(route('questionnaires.partner-answers', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.show', $this->questionnaire->slug));
    }

    public function test_partner_answers_redirects_when_user_not_completed(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $this->user->update(['partner_id' => $partner->id]);

        Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.partner-answers', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.show', $this->questionnaire->slug));
    }

    public function test_partner_answers_redirects_when_partner_not_completed(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $this->user->update(['partner_id' => $partner->id]);

        Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.partner-answers', $this->questionnaire->slug))
            ->assertRedirect(route('questionnaires.show', $this->questionnaire->slug));
    }

    public function test_partner_answers_shown_when_both_completed(): void
    {
        $partner = User::factory()->create(['onboarding_completed' => true]);
        $this->user->update(['partner_id' => $partner->id]);

        $userResponse = Response::factory()->create([
            'user_id' => $this->user->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        $partnerResponse = Response::factory()->create([
            'user_id' => $partner->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status' => CompletionStatus::Completed,
        ]);

        Answer::factory()->create([
            'response_id' => $userResponse->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->option->id,
            'value' => 'dominant',
        ]);

        Answer::factory()->create([
            'response_id' => $partnerResponse->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->option->id,
            'value' => 'dominant',
        ]);

        $this->actingAs($this->user)
            ->get(route('questionnaires.partner-answers', $this->questionnaire->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('questionnaires/PartnerAnswers')
                ->has('grouped_answers')
                ->has('partner_name')
            );
    }
}
