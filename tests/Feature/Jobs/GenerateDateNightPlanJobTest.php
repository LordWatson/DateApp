<?php

namespace Tests\Feature\Jobs;

use App\Actions\GenerateDateNightPlanAction;
use App\Enums\CompletionStatus;
use App\Jobs\GenerateDateNightPlanJob;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateDateNightPlanJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_pushed_to_queue(): void
    {
        Queue::fake();
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();
        $questionnaire = Questionnaire::factory()->create();
        GenerateDateNightPlanJob::dispatch($userOne, $userTwo, $questionnaire);
        Queue::assertPushed(GenerateDateNightPlanJob::class);
    }

    public function test_does_nothing_when_responses_not_completed(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();
        $questionnaire = Questionnaire::factory()->create();
        Response::factory()->create([
            'user_id' => $userOne->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::InProgress,
        ]);
        $action = $this->createMock(GenerateDateNightPlanAction::class);
        $action->expects($this->never())->method('execute');
        (new GenerateDateNightPlanJob($userOne, $userTwo, $questionnaire))->handle($action);
    }
}
