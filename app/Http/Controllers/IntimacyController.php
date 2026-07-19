<?php

namespace App\Http\Controllers;

use App\Enums\QuestionnaireStatus;
use App\Models\IntimacyGame;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntimacyController extends Controller
{
    public function index(Request $request): Response
    {
        $questionnaires = Questionnaire::query()
            ->where('is_intimacy', true)
            ->where('status', QuestionnaireStatus::Active)
            ->orderBy('display_order')
            ->get()
            ->map(fn (Questionnaire $q): array => [
                'id' => $q->id,
                'title' => $q->title,
                'slug' => $q->slug,
                'description' => $q->description,
                'emoji' => $q->emoji,
                'estimated_minutes' => $q->estimated_minutes,
            ]);

        $games = IntimacyGame::query()
            ->active()
            ->orderBy('display_order')
            ->get()
            ->map(fn (IntimacyGame $g): array => [
                'id' => $g->id,
                'title' => $g->title,
                'slug' => $g->slug,
                'emoji' => $g->emoji,
                'tagline' => $g->tagline,
                'intensity' => $g->intensity->value,
                'intensity_label' => $g->intensity->label(),
                'intensity_emoji' => $g->intensity->emoji(),
                'category' => $g->category->value,
                'category_label' => $g->category->label(),
                'estimated_minutes' => $g->estimated_minutes,
                'players' => $g->players,
            ]);

        return Inertia::render('intimacy/Index', [
            'questionnaires' => $questionnaires,
            'games' => $games,
        ]);
    }

    public function show(Request $request, IntimacyGame $intimacyGame): Response
    {
        abort_unless($intimacyGame->is_active, 404);

        return Inertia::render('intimacy/Game', [
            'game' => [
                'id' => $intimacyGame->id,
                'title' => $intimacyGame->title,
                'slug' => $intimacyGame->slug,
                'emoji' => $intimacyGame->emoji,
                'tagline' => $intimacyGame->tagline,
                'description' => $intimacyGame->description,
                'how_to_play' => $intimacyGame->how_to_play,
                'players' => $intimacyGame->players,
                'estimated_minutes' => $intimacyGame->estimated_minutes,
                'intensity' => $intimacyGame->intensity->value,
                'intensity_label' => $intimacyGame->intensity->label(),
                'intensity_emoji' => $intimacyGame->intensity->emoji(),
                'category' => $intimacyGame->category->value,
                'category_label' => $intimacyGame->category->label(),
                'prompts' => $intimacyGame->prompts ?? [],
            ],
        ]);
    }
}
