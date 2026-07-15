<?php

namespace App\Http\Controllers;

use App\Enums\CompletionStatus;
use App\Models\Challenge;
use App\Models\Response as QuestionnaireResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->load('partner', 'savedProfiles');

        $recentQuestionnaires = collect();

        if ($user->partner) {
            $recentQuestionnaires = QuestionnaireResponse::with('questionnaire')
                ->where('user_id', $user->id)
                ->where('status', CompletionStatus::Completed)
                ->whereHas('questionnaire.responses', function ($query) use ($user) {
                    $query->where('user_id', $user->partner->id)
                        ->where('status', CompletionStatus::Completed);
                })
                ->latest('completed_at')
                ->take(5)
                ->get()
                ->map(fn (QuestionnaireResponse $response) => [
                    'id' => $response->questionnaire->id,
                    'title' => $response->questionnaire->title,
                    'emoji' => $response->questionnaire->emoji ?? '📋',
                    'slug' => $response->questionnaire->slug,
                    'completed_at' => $response->completed_at?->toISOString(),
                ]);
        }

        $todayChallenge = Challenge::inRandomOrder()->first();

        $pendingInvitation = $user->pendingInvitation();

        return Inertia::render('Dashboard', [
            'partner' => $user->partner,
            'pendingInvitation' => $pendingInvitation ? [
                'email' => $pendingInvitation->email,
                'expires_at' => $pendingInvitation->expires_at->toISOString(),
            ] : null,
            'stats' => [
                'current_streak' => $user->current_streak,
                'longest_streak' => $user->longest_streak,
                'monthly_completions' => $user->monthly_completion_count,
            ],
            'todayChallenge' => $todayChallenge ? [
                'id' => $todayChallenge->id,
                'title' => $todayChallenge->title,
                'description' => $todayChallenge->description,
                'emoji' => $todayChallenge->emoji ?? '✨',
                'difficulty' => $todayChallenge->difficulty->value,
            ] : null,
            'recentQuestionnaires' => $recentQuestionnaires,
            'savedProfiles' => $user->savedProfiles->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'emoji' => $p->emoji ?? '💕',
                'colour' => $p->colour ?? '#EC4899',
            ]),
        ]);
    }
}
