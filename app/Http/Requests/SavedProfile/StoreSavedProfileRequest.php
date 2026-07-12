<?php

namespace App\Http\Requests\SavedProfile;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'colour' => ['nullable', 'string', 'max:20'],
            'questionnaire_id' => ['required', 'integer', 'exists:questionnaires,id'],
        ];
    }
}
