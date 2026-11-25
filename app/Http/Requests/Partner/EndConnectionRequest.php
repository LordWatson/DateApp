<?php

namespace App\Http\Requests\Partner;

/**
 * Ending a connection asks for the password, like deleting the account does.
 */
class EndConnectionRequest extends ConnectionChoicesRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
