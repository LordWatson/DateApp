<?php

namespace App\Http\Resources;

use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Questionnaire
 */
final class QuestionnaireResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'emoji' => $this->emoji,
            'estimated_minutes' => $this->estimated_minutes,
            'status' => $this->status->value,
            'display_order' => $this->display_order,
        ];
    }
}
