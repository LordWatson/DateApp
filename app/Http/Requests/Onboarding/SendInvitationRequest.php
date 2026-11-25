<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('email') === $this->user()->email) {
                $validator->errors()->add('email', 'You cannot invite yourself.');
            }

            if ($this->user()->partner_id !== null) {
                $validator->errors()->add('email', 'You are already connected to a partner. Disconnect first to invite someone else.');
            }
        });
    }
}
