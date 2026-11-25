<?php

namespace App\Http\Resources;

use App\Models\DateNightPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DateNightPlan
 */
final class PlanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'theme' => $this->theme,
            'theme_emoji' => $this->theme_emoji,
            'summary' => $this->summary,
            'activities' => $this->activity,
            'compatibility_score' => $this->compatibility_score,
            'is_favourite' => $this->is_favourite,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
