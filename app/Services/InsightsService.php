<?php

namespace App\Services;

use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Response;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InsightsService
{
    public function getInsights(User $user): array
    {
        $partner = $user->partner;

        $responsesQuery = Response::where('user_id', $user->id)->where('status', 'completed');
        $totalQuestionnaires = $responsesQuery->count();

        $plansQuery = DateNightPlan::where(function ($q) use ($user) {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        });

        $averageCompatibility = round($plansQuery->avg('compatibility_score') ?? 0);
        $totalPlans = $plansQuery->count();

        $loveNotesSent = LoveNote::where('sender_id', $user->id)->count();
        $loveNotesReceived = $partner ? LoveNote::where('sender_id', $partner->id)->where('recipient_id', $user->id)->count() : 0;

        $mostCompletedMonth = Response::where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw($this->extractMonth('created_at') . " as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderByDesc('count')
            ->first();

        $favouriteTheme = DateNightPlan::where(function ($q) use ($user) {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        })
            ->selectRaw('theme, COUNT(*) as count')
            ->groupBy('theme')
            ->orderByDesc('count')
            ->first();

        $mostSelectedAtmosphere = DateNightPlan::where(function ($q) use ($user) {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        })
            ->whereNotNull('atmosphere')
            ->selectRaw('atmosphere, COUNT(*) as count')
            ->groupBy('atmosphere')
            ->orderByDesc('count')
            ->first();

        $compatibilityOverTime = DateNightPlan::where(function ($q) use ($user) {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        })
            ->selectRaw($this->extractYearMonth('created_at') . " as month, AVG(compatibility_score) as avg_score")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'score' => round($row->avg_score),
            ]);

        return [
            'total_questionnaires' => $totalQuestionnaires,
            'total_plans' => $totalPlans,
            'average_compatibility' => $averageCompatibility,
            'love_notes_sent' => $loveNotesSent,
            'love_notes_received' => $loveNotesReceived,
            'current_streak' => $user->current_streak,
            'longest_streak' => $user->longest_streak,
            'most_completed_month' => $mostCompletedMonth ? $this->monthName((int) $mostCompletedMonth->month) : null,
            'favourite_theme' => $favouriteTheme?->theme,
            'most_selected_atmosphere' => $mostSelectedAtmosphere?->atmosphere,
            'compatibility_over_time' => $compatibilityOverTime,
            'moments_count' => $user->moments()->count(),
            'calendar_events_count' => $user->calendarEvents()->count(),
        ];
    }

    private function monthName(int|string $month): string
    {
        return Carbon::create()->month((int) $month)->format('F');
    }

    private function extractMonth(string $column): string
    {
        $driver = DB::getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%m', {$column})"
            : "MONTH({$column})";
    }

    private function extractYearMonth(string $column): string
    {
        $driver = DB::getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }
}
