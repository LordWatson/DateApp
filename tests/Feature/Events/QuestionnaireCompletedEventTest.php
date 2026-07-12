<?php

namespace Tests\Feature\Events;

use App\Events\QuestionnaireCompleted;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class QuestionnaireCompletedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_fires_questionnaire_completed_event(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $questionnaire = Questionnaire::factory()->create();
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
        ]);

        QuestionnaireCompleted::dispatch($user, $questionnaire, $response);

        Event::assertDispatched(QuestionnaireCompleted::class, function ($event) use ($user, $questionnaire): bool {
            return $event->user->id === $user->id
                && $event->questionnaire->id === $questionnaire->id;
        });
    }
}
