<?php

namespace Tests\Feature;

use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use App\Models\Answer;
use App\Models\DateNightTheme;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Response;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\DateNightGeneratorService;
use App\Services\RuleEngineService;
use Database\Seeders\IntimacyQuestionnaireSeeder;
use Database\Seeders\QuestionnaireSeeder;
use Database\Seeders\SeasonalQuestionnaireSeeder;
use Database\Seeders\SoloDatePlannerQuestionnaireSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * The rule-based plan (no AI) and the signals that drive it.
 */
class DateNightEngineTest extends TestCase
{
    use RefreshDatabase;

    private function answer(Response $response, Question $question, string $value, ?array $signals = null, bool $withOption = true): Answer
    {
        $option = $withOption
            ? QuestionOption::factory()->create(['question_id' => $question->id, 'value' => $value, 'signals' => $signals])
            : null;

        return Answer::factory()->create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'question_option_id' => $option?->id,
            'value' => $value,
        ]);
    }

    /**
     * @return Collection<int, Answer>
     */
    private function answersOf(Response $response): Collection
    {
        return $response->answers()->with(['question', 'questionOption'])->get();
    }

    public function test_a_date_out_plan_without_ai_reads_as_a_date_out_with_solo_wording(): void
    {
        DateNightTheme::factory()->create(['name' => 'Cozy Night In', 'description' => 'a warm evening at home', 'active' => true]);
        $questionnaire = Questionnaire::factory()->create(['is_solo' => true]);
        $response = Response::factory()->completed()->create([
            'questionnaire_id' => $questionnaire->id,
            'location_label' => 'Bath, England',
        ]);
        $question = Question::factory()->multipleChoice()->create(['questionnaire_id' => $questionnaire->id]);
        $this->answer($response, $question, 'option_a', ['food']);

        // No prompt template is seeded, so the AI call throws and the rule-based plan is kept.
        $plan = app(DateNightGeneratorService::class)->generate(
            $response,
            null,
            ['percentage' => 100, 'matched' => [], 'different' => []],
            $questionnaire,
        );

        $this->assertTrue($plan->fallback_used);
        $this->assertSame('Dinner Out', $plan->theme);
        $this->assertStringContainsString('date out for you near Bath, England.', $plan->summary);
        $this->assertStringContainsString('near Bath, England', $plan->meal_suggestion);

        $wording = implode(' ', $plan->only(['theme', 'summary', 'meal_suggestion', 'atmosphere', 'activity', 'romantic_challenge']));
        $this->assertStringNotContainsStringIgnoringCase('you both', $wording);
        $this->assertStringNotContainsStringIgnoringCase('at home', $wording);
        $this->assertStringNotContainsStringIgnoringCase('night in', $wording);
    }

    public function test_a_couple_date_out_plan_is_worded_for_two_and_works_without_a_location(): void
    {
        $questionnaire = Questionnaire::factory()->create(['is_solo' => true, 'partner_optional' => true]);
        $one = Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id, 'is_solo' => false]);
        $two = Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id, 'is_solo' => false]);

        $plan = app(DateNightGeneratorService::class)->generate(
            $one,
            $two,
            ['percentage' => 80, 'matched' => [], 'different' => []],
            $questionnaire,
        );

        $this->assertFalse($plan->is_solo);
        $this->assertSame('A Date Out', $plan->theme);
        $this->assertStringContainsString('date out for you both.', $plan->summary);
        $this->assertStringNotContainsString('near', $plan->summary.$plan->meal_suggestion.$plan->activity);
    }

    public function test_other_questionnaires_still_get_an_evening_in_plan(): void
    {
        DateNightTheme::factory()->create(['name' => 'Cozy Night In', 'active' => true]);
        $questionnaire = Questionnaire::factory()->create(['is_solo' => false]);
        $one = Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id]);
        $two = Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id]);

        $plan = app(DateNightGeneratorService::class)->generate(
            $one,
            $two,
            ['percentage' => 80, 'matched' => [], 'different' => []],
            $questionnaire,
        );

        $this->assertSame('Cozy Night In', $plan->theme);
        $this->assertStringContainsString('evening for you both', $plan->summary);
    }

    public function test_option_signals_drive_theme_selection(): void
    {
        $themes = collect([
            DateNightTheme::factory()->create(['name' => 'Movie Marathon', 'active' => true]),
            DateNightTheme::factory()->create(['name' => 'Game Night', 'active' => true]),
        ]);
        $response = Response::factory()->completed()->create();
        $question = Question::factory()->singleChoice()->create(['questionnaire_id' => $response->questionnaire_id]);

        // The raw value says nothing; only the signal can pick the theme.
        $this->answer($response, $question, 'option_b', ['games']);

        $answers = $this->answersOf($response);
        $selected = app(RuleEngineService::class)->selectTheme($answers, $answers, 60, $themes);

        $this->assertSame('Game Night', $selected->name);
    }

    public function test_signals_replace_keyword_matching_for_a_tagged_option_and_untagged_options_fall_back(): void
    {
        $response = Response::factory()->completed()->create();
        $question = Question::factory()->multipleChoice()->create(['questionnaire_id' => $response->questionnaire_id]);

        $this->answer($response, $question, 'movie', ['relaxing']);
        $this->answer($response, $question, 'picnic');

        $context = app(RuleEngineService::class)->deriveContext($this->answersOf($response), collect(), 60);

        $this->assertTrue($context['prefers_relaxing']);
        $this->assertFalse($context['prefers_movies']);
        $this->assertTrue($context['prefers_outdoors']);
    }

    public function test_only_romance_sliders_feed_romance_high(): void
    {
        $engine = app(RuleEngineService::class);
        $response = Response::factory()->completed()->create();
        $travel = Question::factory()->slider(0, 240)->create([
            'questionnaire_id' => $response->questionnaire_id,
            'title' => 'How many minutes will you travel?',
        ]);
        $this->answer($response, $travel, '120', withOption: false);

        $this->assertFalse($engine->deriveContext($this->answersOf($response), collect(), 60)['romance_high']);

        $romance = Question::factory()->slider()->create([
            'questionnaire_id' => $response->questionnaire_id,
            'title' => 'How romantic are you feeling?',
        ]);
        $this->answer($response, $romance, '9', withOption: false);

        $this->assertTrue($engine->deriveContext($this->answersOf($response), collect(), 60)['romance_high']);
    }

    public function test_a_romance_signal_sets_romance_high(): void
    {
        $response = Response::factory()->completed()->create();
        $question = Question::factory()->singleChoice()->create(['questionnaire_id' => $response->questionnaire_id]);
        $this->answer($response, $question, 'candles', ['romance']);

        $context = app(RuleEngineService::class)->deriveContext($this->answersOf($response), collect(), 60);

        $this->assertTrue($context['romance_high']);
    }

    public function test_questionnaire_seeders_tag_options_and_are_safe_to_re_run(): void
    {
        $seeders = [
            QuestionnaireSeeder::class,
            SeasonalQuestionnaireSeeder::class,
            IntimacyQuestionnaireSeeder::class,
            SoloDatePlannerQuestionnaireSeeder::class,
        ];

        $this->seed($seeders);
        $questions = Question::count();
        $options = QuestionOption::count();
        $tagged = QuestionOption::whereNotNull('signals')->count();

        $this->assertGreaterThan(0, $tagged);
        $this->assertSame(
            [],
            QuestionOption::whereNotNull('signals')->get()
                ->flatMap(fn (QuestionOption $option) => $option->signals)
                ->unique()
                ->diff(array_keys(RuleEngineService::SIGNALS))
                ->all(),
        );

        // Rows seeded before the column existed are filled in; an admin's own tags are kept.
        QuestionOption::query()->update(['signals' => null]);
        $custom = QuestionOption::where('value', 'pizza')->firstOrFail();
        $custom->update(['signals' => ['games']]);

        $this->seed($seeders);

        $this->assertSame($questions, Question::count());
        $this->assertSame($options, QuestionOption::count());
        $this->assertSame($tagged, QuestionOption::whereNotNull('signals')->count());
        $this->assertSame(['games'], $custom->fresh()->signals);
    }

    public function test_null_provider_answers_every_use_case(): void
    {
        config()->set('ai.provider', 'null');

        foreach (AIUseCase::cases() as $useCase) {
            AiPromptTemplate::factory()->forUseCase($useCase)->create();

            $response = app(AIService::class)->generate($useCase, ['context' => []], []);

            $this->assertTrue($response->successful, $useCase->value);
            $this->assertNotEmpty($response->data, $useCase->value);
        }
    }

    public function test_null_provider_plan_stub_is_accepted_by_the_generator(): void
    {
        config()->set('ai.provider', 'null');
        AiPromptTemplate::factory()->forUseCase(AIUseCase::DateNightPlan)->create();

        $questionnaire = Questionnaire::factory()->create(['is_solo' => false]);
        $one = Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id, 'user_id' => User::factory()]);
        $two = Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id]);

        $plan = app(DateNightGeneratorService::class)->generate(
            $one,
            $two,
            ['percentage' => 80, 'matched' => [], 'different' => []],
            $questionnaire,
        );

        $this->assertTrue($plan->ai_enhanced);
        $this->assertFalse($plan->fallback_used);
        $this->assertSame('A calm, connection-focused evening at home.', $plan->summary);
    }
}
