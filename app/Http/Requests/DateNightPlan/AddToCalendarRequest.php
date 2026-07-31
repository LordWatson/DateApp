<?php

namespace App\Http\Requests\DateNightPlan;

use Illuminate\Foundation\Http\FormRequest;

class AddToCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
