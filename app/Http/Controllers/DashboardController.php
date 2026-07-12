<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->load('partner', 'savedProfiles');

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
            'savedProfiles' => $user->savedProfiles->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'emoji' => $p->emoji ?? '💕',
                'colour' => $p->colour ?? '#EC4899',
            ]),
        ]);
    }
}
