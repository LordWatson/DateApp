<?php

namespace App\Http\Requests\Intimacy;

use Illuminate\Foundation\Http\FormRequest;

class GenerateIntimacyCardsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Cards already dealt this session, so the new deck does not repeat them.
            'seen' => ['nullable', 'array', 'max:40'],
            'seen.*' => ['string', 'max:300'],
        ];
    }
}
