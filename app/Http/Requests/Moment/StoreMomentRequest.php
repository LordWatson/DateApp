<?php

namespace App\Http\Requests\Moment;

use App\Models\DateNightPlan;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreMomentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'mood' => ['nullable', 'string', 'max:50'],
            'date_night_plan_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $plan = DateNightPlan::find($value);

                    if (! $plan || ! $this->user()->can('view', $plan)) {
                        $fail('The selected date night plan is invalid.');
                    }
                },
            ],
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['boolean'],
            'date' => ['required', 'date'],
            'is_favourite' => ['boolean'],
            'private_notes' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
