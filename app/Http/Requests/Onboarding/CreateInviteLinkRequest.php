<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * An invitation with no email address: the sender shares the link themselves.
 */
class CreateInviteLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->user()->partner_id !== null) {
                $validator->errors()->add('link', 'You are already connected to a partner. Disconnect first to invite someone else.');
            }
        });
    }
}
