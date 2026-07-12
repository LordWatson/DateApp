<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'theme' => $this->theme,
            'theme_emoji' => $this->theme_emoji,
            'summary' => $this->summary,
            'activities' => $this->activities,
            'compatibility_score' => $this->compatibility_score,
            'is_favourite' => $this->is_favourite,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
