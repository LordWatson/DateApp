<?php

namespace App\Http\Controllers;

use App\Services\InsightsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    public function __construct(private readonly InsightsService $insightsService) {}

    public function index(Request $request): Response
    {
        $insights = $this->insightsService->getInsights($request->user());

        return Inertia::render('insights/Index', [
            'insights' => $insights,
        ]);
    }
}
