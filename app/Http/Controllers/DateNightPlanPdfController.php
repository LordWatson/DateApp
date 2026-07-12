<?php

namespace App\Http\Controllers;

use App\Models\DateNightPlan;
use App\Models\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class DateNightPlanPdfController extends Controller
{
    public function export(Request $request, DateNightPlan $dateNightPlan): \Illuminate\Http\Response
    {
        $user = $request->user();

        $responseIds = Response::where('user_id', $user->id)->pluck('id');

        $isParticipant = $responseIds->contains($dateNightPlan->partner_one_response_id)
            || $responseIds->contains($dateNightPlan->partner_two_response_id);

        if (! $isParticipant) {
            abort(403);
        }

        $dateNightPlan->loadMissing([
            'partnerOneResponse.user',
            'partnerTwoResponse.user',
            'questionnaire',
        ]);

        $html = View::make('pdf.date-night-plan', ['plan' => $dateNightPlan])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="date-night-plan.html"',
        ]);
    }
}
