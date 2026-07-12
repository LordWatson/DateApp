<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'emoji' => $this->emoji,
            'type' => $this->type->value,
            'required' => $this->required,
            'display_order' => $this->display_order,
            'minimum_value' => $this->minimum_value,
            'maximum_value' => $this->maximum_value,
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'description' => $o->description,
                'emoji' => $o->emoji,
                'value' => $o->value,
                'display_order' => $o->display_order,
            ])),
        ];
    }
}
