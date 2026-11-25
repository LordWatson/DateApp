<?php

namespace App\Http\Requests\Settings;

use App\Enums\NotificationCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email_notifications' => ['required', 'boolean'],
            // Keys are limited to the categories that exist; each one is on or off.
            'emails' => ['sometimes', 'array:'.implode(',', array_column(NotificationCategory::cases(), 'value'))],
            'emails.*' => ['boolean'],
            'reminder_frequency' => ['sometimes', Rule::in(['weekly', 'monthly', 'off'])],
        ];
    }
}
