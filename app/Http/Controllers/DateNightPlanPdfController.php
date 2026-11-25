<?php

namespace App\Http\Controllers;

use App\Models\DateNightPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class DateNightPlanPdfController extends Controller
{
    public function export(Request $request, DateNightPlan $dateNightPlan): Response
    {
        $this->authorize('view', $dateNightPlan);

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
