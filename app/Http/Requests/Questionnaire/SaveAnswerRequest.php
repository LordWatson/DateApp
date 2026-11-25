<?php

namespace App\Http\Requests\Questionnaire;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Questionnaire;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAnswerRequest extends FormRequest
{
    private const TEXT_MAX_LENGTH = 255;

    private const TEXTAREA_MAX_LENGTH = 500;

    private ?Question $question = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The question being answered, from the `{questionnaire}` and `{order}` route parameters.
     */
    public function question(): Question
    {
        $questionnaire = $this->route('questionnaire');
        abort_unless($questionnaire instanceof Questionnaire, 404);

        return $this->question ??= $questionnaire
            ->questions()
            ->with('options')
            ->where('display_order', $this->route('order'))
            ->firstOrFail();
    }

    /**
     * An empty value clears a choice or text answer; `finish` is what insists
     * on required questions, so autosave never fights the user mid-edit.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $question = $this->question();
        $optionValues = $question->options->pluck('value')->all();

        return match ($question->type) {
            QuestionType::SingleChoice => [
                'value' => ['bail', 'required', 'string', function (string $attribute, mixed $value, Closure $fail) use ($optionValues): void {
                    $isCustomAmount = in_array('custom_amount', $optionValues, true)
                        && preg_match('/^custom_amount:\d{1,7}$/', (string) $value) === 1;

                    if (! $isCustomAmount && ! in_array($value, $optionValues, true)) {
                        $fail('Choose one of the options.');
                    }
                }],
            ],
            QuestionType::MultipleChoice => [
                'value' => ['present', 'array'],
                'value.*' => ['string', 'distinct', Rule::in($optionValues)],
            ],
            QuestionType::Slider => [
                'value' => ['bail', 'required', 'numeric', $this->sliderRule($question)],
            ],
            QuestionType::Text => [
                'value' => ['present', 'nullable', 'string', 'max:'.self::TEXT_MAX_LENGTH],
            ],
            QuestionType::TextArea => [
                'value' => ['present', 'nullable', 'string', 'max:'.self::TEXTAREA_MAX_LENGTH],
            ],
        };
    }

    /**
     * Within min and max and on a step, using the same defaults as the slider component (1, 10, 1).
     */
    private function sliderRule(Question $question): Closure
    {
        $min = $question->minimum_value ?? 1;
        $max = $question->maximum_value ?? 10;
        $step = max(1, $question->step_value ?? 1);

        return function (string $attribute, mixed $value, Closure $fail) use ($min, $max, $step): void {
            $steps = ((float) $value - $min) / $step;

            if ($value < $min || $value > $max || abs($steps - round($steps)) > 1e-9) {
                $fail("Choose a value between {$min} and {$max}.");
            }
        };
    }
}
