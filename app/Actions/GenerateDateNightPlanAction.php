<?php

namespace App\Actions;

use App\Mail\DateNightReadyMail;
use App\Models\DateNightPlan;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use App\Services\CompatibilityService;
use App\Services\DateNightGeneratorService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

class GenerateDateNightPlanAction
{
    public function __construct(
        private readonly CompatibilityService $compatibilityService,
        private readonly DateNightGeneratorService $generatorService,
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(
        User $userOne,
        User $userTwo,
        Questionnaire $questionnaire,
    ): DateNightPlan {
        $responseOne = Response::with('answers.questionOption')
            ->where('user_id', $userOne->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->firstOrFail();

        $responseTwo = Response::with('answers.questionOption')
            ->where('user_id', $userTwo->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->firstOrFail();

        $compatibility = $this->compatibilityService->calculate($userOne, $questionnaire);

        $plan = $this->generatorService->generate(
            $responseOne,
            $responseTwo,
            $compatibility ?? ['percentage' => 0, 'matched' => [], 'different' => []],
            $questionnaire,
        );

        $this->notificationService->notifyDateNightPlanReady($userOne, $plan);
        $this->notificationService->notifyDateNightPlanReady($userTwo, $plan);

        Mail::to($userOne->email)->queue(new DateNightReadyMail($userOne, $plan));
        Mail::to($userTwo->email)->queue(new DateNightReadyMail($userTwo, $plan));

        return $plan;
    }
}
