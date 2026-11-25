<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ExportUserDataJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    /**
     * Queue an export of the user's data, at most once an hour.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $key = 'data-export:'.$user->id;

        // Checked here rather than with throttle middleware so the page gets a message, not a 429.
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "You can request one export an hour. Try again in {$minutes} minutes.",
            ]);

            return to_route('profile.edit');
        }

        RateLimiter::hit($key, 3600);

        ExportUserDataJob::dispatch($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'We are preparing your data. You will get a notification when it is ready.',
        ]);

        return to_route('profile.edit');
    }

    /**
     * Download the user's own export. The path is built from the signed-in user, so nobody else's file is reachable.
     */
    public function download(Request $request): StreamedResponse
    {
        $path = ExportUserDataJob::pathFor($request->user());

        abort_unless(ExportUserDataJob::isAvailable($path), 404);

        return Storage::disk(ExportUserDataJob::DISK)->download($path, 'date-night-export.json');
    }
}
