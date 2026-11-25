<?php

namespace App\Http\Requests\LoveNote;

use App\Models\LoveNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReactToLoveNoteRequest extends FormRequest
{
    /**
     * Only the person the note was sent to can react to it.
     */
    public function authorize(): bool
    {
        $note = $this->route('loveNote');

        return $note instanceof LoveNote && $note->recipient_id === $this->user()->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Null clears the reaction.
            'reaction' => ['present', 'nullable', 'string', Rule::in(LoveNote::REACTIONS)],
        ];
    }
}
