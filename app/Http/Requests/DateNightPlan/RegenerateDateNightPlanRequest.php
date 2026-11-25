<?php

namespace App\Http\Requests\DateNightPlan;

use Illuminate\Foundation\Http\FormRequest;

class RegenerateDateNightPlanRequest extends FormRequest
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
            'steer' => ['required', 'string', 'min:3', 'max:200'],
        ];
    }
}
