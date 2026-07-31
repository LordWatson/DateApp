<?php

namespace App\Services;

use App\Enums\CompletionStatus;
use App\Enums\NotificationType;
use App\Events\QuestionnaireCompleted;
use App\Models\Answer;
use App\Models\AppNotification;
use App\Models\DateNightPlan;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\SavedProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class QuestionnaireService
{
    public function findOrCreateResponse(User $user, Questionnaire $questionnaire): Response
    {
        $inProgress = Response::where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::InProgress)
            ->latest('id')
            ->first();

        if ($inProgress) {
            return $inProgress;
        }

        return Response::create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::InProgress,
            'started_at' => Carbon::now(),
        ]);
    }

    public function startNewResponse(User $user, Questionnaire $questionnaire): Response
    {
        Response::where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::InProgress)
            ->delete();

        return Response::create([
            'user_id' => $user->id,
            'questionnaire_id' => $questionnaire->id,
            'status' => CompletionStatus::InProgress,
            'started_at' => Carbon::now(),
        ]);
    }

    public function getResponseWithAnswers(User $user, Questionnaire $questionnaire): ?Response
    {
        return Response::with('answers')
            ->where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->latest('id')
            ->first();
    }

    public function getLatestCompletedResponse(User $user, Questionnaire $questionnaire): ?Response
    {
        return Response::with('answers')
            ->where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->latest('completed_at')
            ->latest('id')
            ->first();
    }

    public function getCompletedResponses(User $user, Questionnaire $questionnaire): Collection
    {
        return Response::where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();
    }

    public function saveAnswer(Response $response, Question $question, mixed $value): void
    {
        if ($question->type->value === 'multiple_choice') {
            $this->saveMultipleChoiceAnswer($response, $question, (array) $value);
        } elseif (in_array($question->type->value, ['single_choice'])) {
            $this->saveSingleChoiceAnswer($response, $question, $value);
        } else {
            $this->saveTextAnswer($response, $question, $value);
        }
    }

    private function saveSingleChoiceAnswer(Response $response, Question $question, mixed $value): void
    {
        Answer::where('response_id', $response->id)
            ->where('question_id', $question->id)
            ->delete();

        $option = $question->options()->where('value', $value)->first();

        Answer::create([
            'response_id' => $response->id,
            'question_id' => $question->id,
            'question_option_id' => $option?->id,
            'value' => $value,
            'created_at' => Carbon::now(),
        ]);
    }

    private function saveMultipleChoiceAnswer(Response $response, Question $question, array $values): void
    {
        Answer::where('response_id', $response->id)
            ->where('question_id', $question->id)
            ->delete();

        foreach ($values as $value) {
            $option = $question->options()->where('value', $value)->first();

            Answer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'question_option_id' => $option?->id,
                'value' => $value,
                'created_at' => Carbon::now(),
            ]);
        }
    }

    private function saveTextAnswer(Response $response, Question $question, mixed $value): void
    {
        Answer::updateOrCreate(
            ['response_id' => $response->id, 'question_id' => $question->id],
            ['value' => $value, 'created_at' => Carbon::now()],
        );
    }

    public function getAnswersForResponse(Response $response): Collection
    {
        return $response->answers()->with('question', 'questionOption')->get();
    }

    public function applyProfile(Response $response, SavedProfile $profile): void
    {
        $profileAnswers = $profile->answers()->with('question')->get();

        foreach ($profileAnswers->groupBy('question_id') as $questionId => $answers) {
            $question = $answers->first()->question;

            if (! $question) {
                continue;
            }

            if ($question->type->value === 'multiple_choice') {
                $this->saveMultipleChoiceAnswer($response, $question, $answers->pluck('value')->toArray());
            } elseif ($question->type->value === 'single_choice') {
                $this->saveSingleChoiceAnswer($response, $question, $answers->first()->value);
            } else {
                $this->saveTextAnswer($response, $question, $answers->first()->value);
            }
        }
    }

    public function completeResponse(Response $response): void
    {
        $response->update([
            'status' => CompletionStatus::Completed,
            'completed_at' => Carbon::now(),
        ]);

        $response->loadMissing(['user', 'questionnaire']);

        $this->wipeOverhangingPendingResponses($response);

        QuestionnaireCompleted::dispatch($response->user, $response->questionnaire, $response);
    }

    /**
     * Persist the location fields on a solo response (if provided) and
     * mark it as completed. No-op when the response is already completed.
     *
     * @param  array<string, mixed>  $locationData
     */
    public function completeWithLocation(Response $response, array $locationData): void
    {
        if ($response->status === CompletionStatus::Completed) {
            return;
        }

        $questionnaire = $response->questionnaire ?? $response->questionnaire()->first();

        if ($questionnaire?->is_solo) {
            $response->fill([
                'location_label' => $locationData['location_label'] ?? null,
                'location_city' => $locationData['location_city'] ?? null,
                'location_region' => $locationData['location_region'] ?? null,
                'location_country' => $locationData['location_country'] ?? null,
                'location_latitude' => $locationData['location_latitude'] ?? null,
                'location_longitude' => $locationData['location_longitude'] ?? null,
                'travel_radius_minutes' => $locationData['travel_radius_minutes'] ?? null,
            ])->save();
        }

        $this->completeResponse($response);
    }

    /**
     * Ensure the user never has more than one "pending" (unpaired completed)
     * response for a non-solo questionnaire. When a fresh completion arrives
     * while the previous cycle is still waiting on the partner, discard the
     * older completed-but-unpaired response and clean up the partner's stale
     * "please complete" notification so a new one can be sent for this cycle.
     */
    private function wipeOverhangingPendingResponses(Response $latestResponse): void
    {
        $questionnaire = $latestResponse->questionnaire;
        $user = $latestResponse->user;

        if (! $questionnaire || $questionnaire->is_solo) {
            return;
        }

        $previousCompleted = Response::where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->where('id', '!=', $latestResponse->id)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();

        foreach ($previousCompleted as $previous) {
            $paired = DateNightPlan::query()
                ->where('questionnaire_id', $questionnaire->id)
                ->where(function ($query) use ($previous): void {
                    $query->where('partner_one_response_id', $previous->id)
                        ->orWhere('partner_two_response_id', $previous->id);
                })
                ->exists();

            if ($paired) {
                continue;
            }

            $previous->delete();
        }

        $partner = $user->partner;

        if (! $partner) {
            return;
        }

        AppNotification::where('user_id', $partner->id)
            ->where('type', NotificationType::PartnerCompleted)
            ->whereJsonContains('data->questionnaire_id', $questionnaire->id)
            ->delete();
    }

    public function getAnsweredQuestionIds(Response $response): array
    {
        return $response->answers()->pluck('question_id')->unique()->values()->toArray();
    }

    /**
     * Determine whether the partner has a completed response for the questionnaire
     * that has NOT yet been paired with any of the given user's completed responses
     * in an existing DateNightPlan.
     *
     * This distinguishes between "partner has completed for this cycle" (true) and
     * "partner's latest completion was already consumed by a previous plan, so the
     * user is waiting for the partner to restart" (false).
     */
    public function partnerHasFreshCompletedResponse(User $user, User $partner, Questionnaire $questionnaire): bool
    {
        $partnerLatest = $this->getLatestCompletedResponse($partner, $questionnaire);

        if (! $partnerLatest) {
            return false;
        }

        $userResponseIds = Response::where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::Completed)
            ->pluck('id');

        if ($userResponseIds->isEmpty()) {
            return true;
        }

        $alreadyPaired = DateNightPlan::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->where(function ($query) use ($partnerLatest): void {
                $query->where('partner_one_response_id', $partnerLatest->id)
                    ->orWhere('partner_two_response_id', $partnerLatest->id);
            })
            ->where(function ($query) use ($userResponseIds): void {
                $query->whereIn('partner_one_response_id', $userResponseIds)
                    ->orWhereIn('partner_two_response_id', $userResponseIds);
            })
            ->exists();

        return ! $alreadyPaired;
    }

    /**
     * Determine whether the user should be automatically moved into a new questionnaire
     * attempt because their partner has already started a new cycle.
     *
     * Returns true when:
     *  - the user has no in-progress response for this questionnaire,
     *  - the user's latest completed response has already been paired in a DateNightPlan,
     *  - the partner has a completed response that has not yet been paired with any of
     *    the user's completed responses (i.e. the partner has restarted and completed).
     */
    public function shouldAutoStartNewCycle(User $user, User $partner, Questionnaire $questionnaire): bool
    {
        $userInProgress = Response::where('user_id', $user->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('status', CompletionStatus::InProgress)
            ->exists();

        if ($userInProgress) {
            return false;
        }

        $userLatestCompleted = $this->getLatestCompletedResponse($user, $questionnaire);

        if (! $userLatestCompleted) {
            return false;
        }

        $userLatestPaired = DateNightPlan::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->where(function ($query) use ($userLatestCompleted): void {
                $query->where('partner_one_response_id', $userLatestCompleted->id)
                    ->orWhere('partner_two_response_id', $userLatestCompleted->id);
            })
            ->exists();

        if (! $userLatestPaired) {
            return false;
        }

        return $this->partnerHasFreshCompletedResponse($user, $partner, $questionnaire);
    }

    public function getFirstUnansweredOrder(Questionnaire $questionnaire, Response $response): int
    {
        $answeredIds = $this->getAnsweredQuestionIds($response);

        $next = $questionnaire->questions()
            ->whereNotIn('id', $answeredIds)
            ->orderBy('display_order')
            ->first();

        return $next?->display_order ?? $questionnaire->questions()->max('display_order');
    }
}
