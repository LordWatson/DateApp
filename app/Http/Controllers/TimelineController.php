<?php

namespace App\Http\Controllers;

use App\Services\TimelineBuilderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    public function __construct(
        private readonly TimelineBuilderService $timeline,
    ) {}

    public function index(Request $request): Response
    {
        $filter = (string) $request->get('filter', 'all');

        return Inertia::render('timeline/Index', [
            'entries' => $this->timeline->build($request->user(), $filter),
            'filter' => $filter,
            'filters' => [
                ['value' => 'all', 'label' => 'All'],
                ['value' => 'questionnaires', 'label' => 'Questionnaires'],
                ['value' => 'plans', 'label' => 'Plans'],
                ['value' => 'moments', 'label' => 'Moments'],
                ['value' => 'love_notes', 'label' => 'Love Notes'],
                ['value' => 'calendar', 'label' => 'Calendar'],
                ['value' => 'achievements', 'label' => 'Achievements'],
            ],
        ]);
    }
}
