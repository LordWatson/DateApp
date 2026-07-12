<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AchievementController extends Controller
{
    public function __construct(private readonly AchievementService $achievementService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $unlockedIds = $user->achievements()->pluck('achievement_id')->toArray();

        $achievements = Achievement::orderBy('category')->orderBy('points')->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'description' => $a->description,
                'emoji' => $a->emoji,
                'category' => $a->category,
                'points' => $a->points,
                'hidden' => $a->hidden,
                'unlocked' => in_array($a->id, $unlockedIds),
                'unlocked_at' => in_array($a->id, $unlockedIds)
                    ? $user->achievements()->where('achievement_id', $a->id)->first()?->pivot?->unlocked_at
                    : null,
            ]);

        $progress = $this->achievementService->getUserProgress($user);

        return Inertia::render('achievements/Index', [
            'achievements' => $achievements,
            'progress' => $progress,
        ]);
    }
}
