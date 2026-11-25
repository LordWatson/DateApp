<?php

namespace App\Http\Requests\DateNightPlan;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDateNightPlanRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'between:1,5'],
            'note' => ['nullable', 'string', 'max:500'],
            // "Today" is the user's own day, which can be ahead of the server's.
            'completed_on' => ['nullable', 'date', 'before_or_equal:'.$this->user()->today()->toDateString()],
        ];
    }
}
