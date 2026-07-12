<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = trim($request->get('q', ''));
        $results = [];

        if (strlen($query) >= 2) {
            $results = $this->search($request->user(), $query);
        }

        return Inertia::render('search/Index', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    private function search(User $user, string $query): array
    {
        $results = [];

        $plans = DateNightPlan::where(function ($q) use ($user) {
            $q->whereHas('partnerOneResponse', fn ($r) => $r->where('user_id', $user->id))
                ->orWhereHas('partnerTwoResponse', fn ($r) => $r->where('user_id', $user->id));
        })
            ->where(fn ($q) => $q->where('theme', 'like', "%{$query}%")->orWhere('summary', 'like', "%{$query}%"))
            ->limit(5)->get();

        if ($plans->isNotEmpty()) {
            $results['plans'] = $plans->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->theme,
                'description' => $p->summary,
                'emoji' => $p->theme_emoji ?? '🌙',
                'url' => route('date-night.show', $p),
            ]);
        }

        $moments = $user->moments()
            ->where(fn ($q) => $q->where('title', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(5)->get();

        if ($moments->isNotEmpty()) {
            $results['moments'] = $moments->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => $m->description,
                'emoji' => '📖',
                'url' => route('moments.index'),
            ]);
        }

        $loveNotes = LoveNote::where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('recipient_id', $user->id))
            ->where('message', 'like', "%{$query}%")
            ->limit(5)->get();

        if ($loveNotes->isNotEmpty()) {
            $results['love_notes'] = $loveNotes->map(fn ($n) => [
                'id' => $n->id,
                'title' => 'Love Note',
                'description' => $n->message,
                'emoji' => '💌',
                'url' => route('love-notes.index'),
            ]);
        }

        $questionnaires = Questionnaire::where('status', 'published')
            ->where(fn ($q) => $q->where('title', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(5)->get();

        if ($questionnaires->isNotEmpty()) {
            $results['questionnaires'] = $questionnaires->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'description' => $q->description,
                'emoji' => $q->emoji ?? '📋',
                'url' => route('questionnaires.show', $q),
            ]);
        }

        $savedProfiles = $user->savedProfiles()
            ->where('name', 'like', "%{$query}%")
            ->limit(5)->get();

        if ($savedProfiles->isNotEmpty()) {
            $results['saved_profiles'] = $savedProfiles->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->name,
                'description' => null,
                'emoji' => $p->emoji ?? '💾',
                'url' => route('saved-profiles.index'),
            ]);
        }

        $calendarEvents = $user->calendarEvents()
            ->where(fn ($q) => $q->where('title', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(5)->get();

        if ($calendarEvents->isNotEmpty()) {
            $results['calendar_events'] = $calendarEvents->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'description' => $e->description,
                'emoji' => $e->emoji ?? '📅',
                'url' => route('calendar.index'),
            ]);
        }

        $achievements = Achievement::where('hidden', false)
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(5)->get();

        if ($achievements->isNotEmpty()) {
            $results['achievements'] = $achievements->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->name,
                'description' => $a->description,
                'emoji' => $a->emoji,
                'url' => route('achievements.index'),
            ]);
        }

        return $results;
    }
}
