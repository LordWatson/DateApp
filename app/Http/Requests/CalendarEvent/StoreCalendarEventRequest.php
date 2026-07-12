<?php

namespace App\Http\Requests\CalendarEvent;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:date_night,anniversary,birthday,holiday,weekend_away,movie_night,dinner_reservation,custom'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'colour' => ['nullable', 'string', 'max:20'],
            'reminder' => ['nullable', 'string', 'in:same_day,1_day,3_days,1_week'],
        ];
    }
}
