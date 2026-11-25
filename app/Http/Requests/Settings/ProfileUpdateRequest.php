<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            // The limits onboarding uses (CompleteProfileRequest), but optional here:
            // people who joined from an invitation never filled these in.
            'display_name' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'timezone' => ['sometimes', 'required', 'string', 'timezone:all'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ];
    }
}
