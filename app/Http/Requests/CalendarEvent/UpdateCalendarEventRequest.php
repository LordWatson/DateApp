<?php

namespace App\Http\Requests\CalendarEvent;

use App\Enums\CalendarEventType;
use App\Enums\CalendarRecurrence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', Rule::enum(CalendarEventType::class)],
            'emoji' => ['nullable', 'string', 'max:10'],
            'date' => ['required', 'date'],
            'recurrence' => ['nullable', Rule::enum(CalendarRecurrence::class)],
            'time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'colour' => ['nullable', 'string', 'max:20'],
            'reminder' => ['nullable', 'string', 'in:same_day,1_day,3_days,1_week'],
        ];
    }
}
