<?php

namespace App\Http\Controllers\Admin;

use App\Models\LoveNote;
use App\Models\Moment;
use App\Models\Response;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminAnalyticsController extends AdminController
{
    public function index(): InertiaResponse
    {
        $dau = User::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get();

        $mau = User::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))->orderBy('month')->get();

        $completions = Response::select(DB::raw('DATE(completed_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(completed_at)'))->orderBy('date')->get();

        $dropoff = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->select('questions.display_order', 'questions.title', DB::raw('COUNT(DISTINCT answers.response_id) as count'))
            ->groupBy('questions.display_order', 'questions.title')
            ->orderBy('questions.display_order')
            ->get();

        $avg_compatibility = DB::table('responses')
            ->whereNotNull('compatibility_score')
            ->avg('compatibility_score');

        $love_note_activity = LoveNote::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get();

        $moment_activity = Moment::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get();

        return Inertia::render('admin/analytics/Index', [
            'dau' => $dau,
            'mau' => $mau,
            'completions' => $completions,
            'dropoff' => $dropoff,
            'avg_compatibility' => round($avg_compatibility ?? 0, 1),
            'love_note_activity' => $love_note_activity,
            'moment_activity' => $moment_activity,
        ]);
    }
}
