<?php

namespace App\Http\Requests\Moment;

use Illuminate\Foundation\Http\FormRequest;

class StoreMomentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'mood' => ['nullable', 'string', 'max:50'],
            'date_night_plan_id' => ['nullable', 'integer', 'exists:date_night_plans,id'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'date' => ['required', 'date'],
            'is_favourite' => ['boolean'],
            'private_notes' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
