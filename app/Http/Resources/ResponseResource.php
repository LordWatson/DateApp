<?php

namespace App\Http\Resources;

use App\Models\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Response
 */
final class ResponseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'questionnaire' => new QuestionnaireResource($this->whenLoaded('questionnaire')),
        ];
    }
}
