<?php

namespace App\Http\Requests\Questionnaire;

use Illuminate\Foundation\Http\FormRequest;

class FinishQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'location_label' => ['nullable', 'string', 'max:120'],
            'location_city' => ['nullable', 'string', 'max:80'],
            'location_region' => ['nullable', 'string', 'max:80'],
            'location_country' => ['nullable', 'string', 'max:80'],
            'location_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'travel_radius_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
        ];
    }
}
