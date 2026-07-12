<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class MomentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'mood' => $this->mood,
            'photo' => $this->photo ? Storage::url($this->photo) : null,
            'date' => $this->date->toDateString(),
            'is_favourite' => $this->is_favourite,
            'tags' => $this->tags ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
