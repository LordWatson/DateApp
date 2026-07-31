<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $search,
    ) {}

    public function index(Request $request): Response
    {
        $query = trim((string) $request->get('q', ''));

        return Inertia::render('search/Index', [
            'query' => $query,
            'results' => $this->search->search($request->user(), $query),
        ]);
    }
}
