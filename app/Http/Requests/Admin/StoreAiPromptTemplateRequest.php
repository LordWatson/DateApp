<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAiPromptTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:ai_prompt_templates,name'],
            'system_prompt' => ['required', 'string'],
            'user_prompt_template' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:32000'],
        ];
    }
}
