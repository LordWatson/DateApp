<?php

namespace App\Http\Resources;

use App\Models\Moment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Moment
 */
final class MomentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'mood' => $this->mood,
            'photo' => $this->photo ? route('moments.photo', $this->id) : null,
            'date' => $this->date->toDateString(),
            'is_favourite' => $this->is_favourite,
            'tags' => $this->tags ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
