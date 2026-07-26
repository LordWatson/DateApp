<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateWeeklyReflectionAction;
use App\Models\WeeklyReflection;
use App\Services\WeeklyReflectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyReflectionController extends Controller
{
    public function __construct(
        private readonly WeeklyReflectionService $service,
        private readonly GenerateWeeklyReflectionAction $generate,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $current = $this->generate->execute($user);
        $history = $this->service->history($user)
            ->reject(fn ($item) => $item->id === $current->id)
            ->values();

        return Inertia::render('weekly-reflection/Index', [
            'reflection' => $this->present($current),
            'history' => $history->map(fn ($item) => $this->present($item))->values(),
        ]);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $this->generate->execute($request->user(), force: true);

        return redirect()->route('weekly-reflection.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(WeeklyReflection $reflection): array
    {
        return [
            'id' => $reflection->id,
            'week_start' => $reflection->week_start?->toDateString(),
            'week_end' => $reflection->week_end?->toDateString(),
            'headline' => $reflection->headline,
            'summary' => $reflection->summary,
            'highlights' => $reflection->highlights ?? [],
            'gentle_suggestion' => $reflection->gentle_suggestion,
            'encouragement' => $reflection->encouragement,
            'metrics' => $reflection->metrics ?? [],
            'fallback_used' => $reflection->fallback_used,
            'generated_at' => $reflection->generated_at?->toIso8601String(),
        ];
    }
}
