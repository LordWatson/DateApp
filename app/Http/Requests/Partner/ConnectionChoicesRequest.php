<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

/**
 * What a person keeps of the history they shared with a former partner.
 */
class ConnectionChoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'keep_plans' => ['required', 'boolean'],
            'keep_notes' => ['required', 'boolean'],
        ];
    }
}
