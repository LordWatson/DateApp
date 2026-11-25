<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AiConsentUpdateRequest;
use Illuminate\Http\RedirectResponse;

class AiConsentController extends Controller
{
    /**
     * Record the user's answer to "may your content be sent to the AI provider?".
     */
    public function __invoke(AiConsentUpdateRequest $request): RedirectResponse
    {
        $agreed = $request->boolean('agreed');

        $request->user()->recordAiConsent($agreed);

        return back()->with('success', $agreed ? 'AI suggestions are on.' : 'AI suggestions are off.');
    }
}
