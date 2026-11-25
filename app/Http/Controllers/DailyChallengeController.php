<?php

namespace App\Http\Controllers;

use App\Actions\CompleteDailyChallengeAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DailyChallengeController extends Controller
{
    public function complete(Request $request, CompleteDailyChallengeAction $action): RedirectResponse
    {
        return $action->execute($request->user())
            ? back()->with('success', 'Challenge done!')
            : back();
    }
}
