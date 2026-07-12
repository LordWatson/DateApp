<?php

namespace App\Http\Controllers\Admin;

use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Response;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminDashboardController extends AdminController
{
    public function index(): InertiaResponse
    {
        $stats = [
            'total_users' => User::count(),
            'couples' => User::whereNotNull('partner_id')->count() / 2,
            'active_questionnaires' => Response::where('status', 'in_progress')->count(),
            'completions_today' => Response::whereDate('completed_at', today())->whereNotNull('completed_at')->count(),
            'plans_today' => DateNightPlan::whereDate('created_at', today())->count(),
            'love_notes_today' => LoveNote::whereDate('created_at', today())->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'queued_jobs' => DB::table('jobs')->count(),
        ];

        $newest_users = User::with('role')
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'created_at', 'role_id']);

        $daily_users = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $daily_completions = Response::select(
            DB::raw('DATE(completed_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('admin/Dashboard', [
            'stats' => $stats,
            'newest_users' => $newest_users,
            'daily_users' => $daily_users,
            'daily_completions' => $daily_completions,
        ]);
    }
}
