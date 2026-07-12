<?php

namespace App\Http\Requests\LoveNote;

use Illuminate\Foundation\Http\FormRequest;

class SendLoveNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:200'],
        ];
    }
}
