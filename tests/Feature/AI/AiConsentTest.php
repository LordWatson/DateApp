<?php

namespace Tests\Feature\AI;

use App\Contracts\AI\AIProvider;
use App\DTOs\AI\AIRequest;
use App\DTOs\AI\AIResponse;
use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\DateNightGeneratorService;
use App\Services\WeeklyReflectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Nothing is sent to the AI provider unless everyone whose content is in the
 * prompt has agreed; without that the app uses its rule-based result.
 */
class AiConsentTest extends TestCase
{
    use RefreshDatabase;

    private int $providerCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([AIUseCase::DateNightPlan, AIUseCase::DateNightPlanSolo, AIUseCase::WeeklyReflection] as $useCase) {
            AiPromptTemplate::factory()->forUseCase($useCase)->create();
        }

        $this->app->instance(AIProvider::class, new class($this->providerCalls) implements AIProvider
        {
            public function __construct(private int &$calls) {}

            public function name(): string
            {
                return 'spy';
            }

            public function complete(AIRequest $request): AIResponse
            {
                $this->calls++;

                return AIResponse::success($request->useCase, ['summary' => 'Written by AI.', 'headline' => 'Written by AI.'], 'spy');
            }
        });
    }

    /**
     * @return array{0: Response, 1: Response, 2: Questionnaire}
     */
    private function coupleResponses(User $one, User $two): array
    {
        $questionnaire = Questionnaire::factory()->create(['is_solo' => false]);

        return [
            Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id, 'user_id' => $one->id]),
            Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id, 'user_id' => $two->id]),
            $questionnaire,
        ];
    }

    public function test_the_service_refuses_without_consent_and_records_nothing(): void
    {
        $user = User::factory()->withoutAiConsent()->create();

        $response = app(AIService::class)->generateDateNightPlan(['context' => []], [$user]);

        $this->assertFalse($response->successful);
        $this->assertSame(AIService::NO_CONSENT_ERROR_CODE, $response->errorCode);
        $this->assertSame(0, $this->providerCalls);
        $this->assertDatabaseCount('ai_generations', 0);
    }

    public function test_a_solo_plan_without_consent_is_the_rule_based_plan(): void
    {
        $user = User::factory()->withoutAiConsent()->create();
        $questionnaire = Questionnaire::factory()->create(['is_solo' => true]);
        $response = Response::factory()->completed()->create(['questionnaire_id' => $questionnaire->id, 'user_id' => $user->id]);

        $plan = app(DateNightGeneratorService::class)->generate($response, null, ['percentage' => 100, 'matched' => [], 'different' => []], $questionnaire);

        $this->assertSame(0, $this->providerCalls);
        $this->assertTrue($plan->fallback_used);
        $this->assertFalse($plan->ai_enhanced);
        $this->assertStringContainsString('date out for you', $plan->summary);
    }

    public function test_a_couple_plan_needs_both_partners_to_agree(): void
    {
        $compatibility = ['percentage' => 80, 'matched' => [], 'different' => []];

        [$one, $two, $questionnaire] = $this->coupleResponses(User::factory()->create(), User::factory()->withoutAiConsent()->create());
        $plan = app(DateNightGeneratorService::class)->generate($one, $two, $compatibility, $questionnaire);

        $this->assertSame(0, $this->providerCalls);
        $this->assertTrue($plan->fallback_used);
        $this->assertStringContainsString('evening for you both', $plan->summary);

        [$one, $two, $questionnaire] = $this->coupleResponses(User::factory()->create(), User::factory()->create());
        $plan = app(DateNightGeneratorService::class)->generate($one, $two, $compatibility, $questionnaire);

        $this->assertSame(1, $this->providerCalls);
        $this->assertFalse($plan->fallback_used);
        $this->assertSame('Written by AI.', $plan->summary);
    }

    public function test_the_shared_weekly_reflection_needs_both_partners_to_agree(): void
    {
        $user = User::factory()->create();
        $partner = User::factory()->withoutAiConsent()->create(['partner_id' => $user->id]);
        $user->update(['partner_id' => $partner->id]);

        $reflection = app(WeeklyReflectionService::class)->currentOrGenerate($user);

        $this->assertSame(0, $this->providerCalls);
        $this->assertTrue($reflection->fallback_used);
        $this->assertSame('This Week Together', $reflection->headline);

        $partner->recordAiConsent(true);
        $reflection = app(WeeklyReflectionService::class)->regenerateCurrent($user->fresh());

        $this->assertSame(1, $this->providerCalls);
        $this->assertFalse($reflection->fallback_used);
        $this->assertSame('Written by AI.', $reflection->headline);
    }

    public function test_a_user_is_asked_once_and_can_change_the_answer_in_settings(): void
    {
        $user = User::factory()->withoutAiConsent()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.ai_consent', null));

        $this->actingAs($user)->put(route('ai-consent.update'), ['agreed' => false])->assertRedirect();
        $this->assertFalse($user->fresh()->aiConsent());
        $this->assertFalse($user->fresh()->hasAiConsent());

        $this->actingAs($user)->put(route('ai-consent.update'), ['agreed' => true])->assertRedirect();
        $this->assertTrue($user->fresh()->hasAiConsent());
        $this->assertNull($user->fresh()->ai_consent_declined_at);

        $this->actingAs($user)->put(route('ai-consent.update'), [])->assertSessionHasErrors('agreed');
    }
}
