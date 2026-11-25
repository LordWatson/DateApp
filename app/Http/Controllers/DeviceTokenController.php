<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceTokenController extends Controller
{
    /**
     * Called by the mobile app on every launch. A token belongs to one phone,
     * so it moves to whoever is signed in on that phone now.
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'in:android,ios'],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            ['user_id' => $request->user()->id, 'platform' => $validated['platform']],
        );

        return response()->noContent();
    }
}
