<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:deepseek,null'],
            'model' => ['required', 'string', 'max:120'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['required', 'integer', 'min:1', 'max:8000'],
            'timeout' => ['required', 'integer', 'min:1', 'max:300'],
            'retry_attempts' => ['required', 'integer', 'min:0', 'max:10'],
        ];
    }
}
