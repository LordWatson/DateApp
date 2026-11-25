<?php

namespace Tests\Feature\Admin;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminQuestionnaireEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role_id' => Role::factory()->create()->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Plan a Picnic',
            'status' => 'draft',
            'visibility' => 'public',
            'display_order' => 0,
            ...$overrides,
        ];
    }

    public function test_store_saves_the_solo_intimacy_and_partner_optional_flags(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/questionnaires', $this->payload([
                'is_solo' => true,
                'partner_optional' => true,
                'is_intimacy' => true,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('questionnaires', [
            'slug' => 'plan-a-picnic',
            'is_solo' => true,
            'partner_optional' => true,
            'is_intimacy' => true,
        ]);
    }

    public function test_update_saves_the_flags_and_keeps_the_slug(): void
    {
        $questionnaire = Questionnaire::factory()->create(['slug' => 'original-slug']);

        $this->actingAs($this->admin)
            ->put("/admin/questionnaires/{$questionnaire->id}", $this->payload([
                'title' => 'Renamed',
                'is_solo' => true,
                'partner_optional' => true,
            ]))
            ->assertRedirect();

        $questionnaire->refresh();
        $this->assertSame('original-slug', $questionnaire->slug);
        $this->assertTrue($questionnaire->is_solo);
        $this->assertTrue($questionnaire->partner_optional);
        $this->assertFalse($questionnaire->is_intimacy);
    }

    public function test_partner_optional_is_cleared_when_the_questionnaire_is_not_solo(): void
    {
        $questionnaire = Questionnaire::factory()->create(['is_solo' => true, 'partner_optional' => true]);

        $this->actingAs($this->admin)
            ->put("/admin/questionnaires/{$questionnaire->id}", $this->payload([
                'is_solo' => false,
                'partner_optional' => true,
            ]))
            ->assertRedirect();

        $this->assertFalse($questionnaire->fresh()->partner_optional);
    }

    public function test_invalid_status_and_visibility_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/questionnaires', $this->payload(['status' => 'published', 'visibility' => 'secret']))
            ->assertSessionHasErrors(['status', 'visibility']);

        $this->assertDatabaseCount('questionnaires', 0);
    }

    public function test_duplicate_title_gets_its_own_slug(): void
    {
        $this->actingAs($this->admin)->post('/admin/questionnaires', $this->payload())->assertRedirect();
        $this->actingAs($this->admin)->post('/admin/questionnaires', $this->payload())->assertRedirect();
        $this->actingAs($this->admin)->post('/admin/questionnaires', $this->payload())->assertRedirect();

        $this->assertSame(
            ['plan-a-picnic', 'plan-a-picnic-2', 'plan-a-picnic-3'],
            Questionnaire::orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_edit_page_renders_with_questions_and_signal_options(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        $question = Question::factory()->singleChoice()->create(['questionnaire_id' => $questionnaire->id]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'signals' => ['food']]);

        $this->actingAs($this->admin)
            ->get("/admin/questionnaires/{$questionnaire->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/questionnaires/Edit')
                ->has('questionnaire.questions', 1)
                ->where('questionnaire.questions.0.options.0.signals', ['food'])
                ->where('signalOptions', fn ($signals) => collect($signals)->contains('romance'))
            );
    }

    public function test_question_can_be_updated(): void
    {
        $question = Question::factory()->singleChoice()->create(['title' => 'Old title']);

        $this->actingAs($this->admin)
            ->put("/admin/questions/{$question->id}", [
                'title' => 'How far will you travel?',
                'type' => 'slider',
                'display_order' => 3,
                'required' => false,
                'minimum_value' => 0,
                'maximum_value' => 120,
                'step_value' => 15,
                'unit' => 'min',
            ])
            ->assertRedirect();

        $question->refresh();
        $this->assertSame('How far will you travel?', $question->title);
        $this->assertSame(QuestionType::Slider, $question->type);
        $this->assertFalse($question->required);
        $this->assertSame(120, $question->maximum_value);
        $this->assertSame(15, $question->step_value);
        $this->assertSame('min', $question->unit);
    }

    public function test_invalid_question_type_is_rejected(): void
    {
        $question = Question::factory()->singleChoice()->create(['title' => 'Unchanged']);

        $this->actingAs($this->admin)
            ->put("/admin/questions/{$question->id}", ['title' => 'Changed', 'type' => 'dropdown', 'display_order' => 0])
            ->assertSessionHasErrors('type');

        $this->actingAs($this->admin)
            ->post("/admin/questionnaires/{$question->questionnaire_id}/questions", ['title' => 'New', 'type' => 'dropdown', 'display_order' => 1])
            ->assertSessionHasErrors('type');

        $this->assertSame('Unchanged', $question->fresh()->title);
        $this->assertDatabaseCount('questions', 1);
    }

    public function test_option_can_be_updated_with_signals(): void
    {
        $option = QuestionOption::factory()->create(['title' => 'Old']);

        $this->actingAs($this->admin)
            ->put("/admin/options/{$option->id}", [
                'title' => 'A picnic in the park',
                'value' => 'picnic',
                'display_order' => 2,
                'signals' => ['food', 'outdoors'],
            ])
            ->assertRedirect();

        $option->refresh();
        $this->assertSame('A picnic in the park', $option->title);
        $this->assertSame('picnic', $option->value);
        $this->assertSame(['food', 'outdoors'], $option->signals);
    }

    public function test_unknown_option_signal_is_rejected(): void
    {
        $option = QuestionOption::factory()->create(['title' => 'Unchanged']);

        $this->actingAs($this->admin)
            ->put("/admin/options/{$option->id}", [
                'title' => 'Changed',
                'value' => 'x',
                'display_order' => 0,
                'signals' => ['skydiving'],
            ])
            ->assertSessionHasErrors('signals.0');

        $this->assertSame('Unchanged', $option->fresh()->title);
    }

    public function test_questions_can_be_reordered(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        $first = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'display_order' => 0]);
        $second = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'display_order' => 1]);

        $this->actingAs($this->admin)
            ->post('/admin/questionnaires/reorder', ['order' => [
                ['id' => $second->id, 'display_order' => 0],
                ['id' => $first->id, 'display_order' => 1],
            ]])
            ->assertRedirect();

        $this->assertSame([$second->id, $first->id], $questionnaire->questions()->pluck('id')->all());
    }

    public function test_options_can_be_reordered(): void
    {
        $question = Question::factory()->singleChoice()->create();
        $first = QuestionOption::factory()->create(['question_id' => $question->id, 'display_order' => 0]);
        $second = QuestionOption::factory()->create(['question_id' => $question->id, 'display_order' => 1]);

        $this->actingAs($this->admin)
            ->post('/admin/questionnaires/reorder', ['type' => 'options', 'order' => [
                ['id' => $second->id, 'display_order' => 0],
                ['id' => $first->id, 'display_order' => 1],
            ]])
            ->assertRedirect();

        $this->assertSame([$second->id, $first->id], $question->options()->pluck('id')->all());
    }
}
