<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Moment;
use App\Models\Response;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminAnalyticsController extends AdminController
{
    public function index(): InertiaResponse
    {
        // "Active" means the user did something the activity log records (finished a
        // questionnaire, saved a moment, sent a love note, unlocked an achievement,
        // connected a partner). Logins are not recorded anywhere.
        $dau = ActivityLog::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(DISTINCT user_id) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get();

        // One small query per month keeps this free of database-specific date functions.
        $mau = collect(range(11, 0))->map(function (int $monthsAgo): array {
            $start = now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'month' => $start->format('Y-m'),
                'count' => $this->activeUsersBetween($start, $start->endOfMonth()),
            ];
        });

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

        // The score lives on the plan. Solo plans have no second person to compare with.
        $avg_compatibility = DateNightPlan::where('is_solo', false)->avg('compatibility_score');

        $love_note_activity = LoveNote::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get();

        $moment_activity = Moment::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get();

        return Inertia::render('admin/analytics/Index', [
            'dau' => $dau,
            'mau' => $mau,
            'active_users' => $this->activeUsersBetween(now()->subDays(30), now()),
            'completions' => $completions,
            'dropoff' => $dropoff,
            'avg_compatibility' => round((float) ($avg_compatibility ?? 0), 1),
            'love_note_activity' => $love_note_activity,
            'moment_activity' => $moment_activity,
        ]);
    }

    private function activeUsersBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return ActivityLog::whereBetween('created_at', [$from, $to])->distinct()->count('user_id');
    }
}
