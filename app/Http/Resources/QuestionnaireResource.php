<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QuestionnaireResource extends JsonResource
{
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
