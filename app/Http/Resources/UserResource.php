<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'gender' => $this->gender?->value,
            'avatar' => $this->avatar,
            'timezone' => $this->timezone,
            'current_streak' => $this->current_streak,
            'longest_streak' => $this->longest_streak,
            'monthly_completion_count' => $this->monthly_completion_count,
            'onboarding_completed' => $this->onboarding_completed,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
