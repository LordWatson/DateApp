<?php

namespace App\Http\Requests\Moment;

use App\Models\DateNightPlan;
use App\Models\Moment;
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
            'photos' => [
                'nullable',
                'array',
                // On an edit the photos that stay count towards the limit too.
                function (string $attribute, mixed $value, Closure $fail): void {
                    $moment = $this->route('moment');
                    $kept = collect($moment instanceof Moment ? $moment->photos ?? [] : [])->except($this->input('remove_photos', []));

                    if (is_array($value) && $kept->count() + count($value) > Moment::MAX_PHOTOS) {
                        $fail('A moment can have up to '.Moment::MAX_PHOTOS.' photos.');
                    }
                },
            ],
            'photos.*' => ['image', 'max:5120'],
            // Positions of saved photos to delete (edit only).
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['integer', 'min:0'],
            'date' => ['required', 'date'],
            'is_favourite' => ['boolean'],
            'private_notes' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
