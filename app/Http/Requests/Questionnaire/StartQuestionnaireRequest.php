<?php

namespace App\Http\Requests\Questionnaire;

use App\Models\Questionnaire;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StartQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|Closure>>
     */
    public function rules(): array
    {
        return [
            'mode' => [
                'nullable',
                'in:solo,couple',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === 'couple' && $this->user()->partner_id === null) {
                        $fail('Connect with your partner before planning together.');
                    }
                },
            ],
        ];
    }

    /**
     * The mode chosen for this attempt, or null when the questionnaire does not offer a choice.
     */
    public function solo(): ?bool
    {
        $questionnaire = $this->route('questionnaire');

        if (! $questionnaire instanceof Questionnaire || ! $questionnaire->partner_optional) {
            return null;
        }

        return match ($this->input('mode')) {
            'solo' => true,
            'couple' => false,
            default => null,
        };
    }
}
