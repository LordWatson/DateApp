<?php

namespace App\Models;

use App\Enums\IntimacyGameCategory;
use App\Enums\IntimacyGameIntensity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $emoji
 * @property string|null $tagline
 * @property string $description
 * @property string $how_to_play
 * @property int $players
 * @property int|null $estimated_minutes
 * @property IntimacyGameIntensity $intensity
 * @property IntimacyGameCategory $category
 * @property array<int, string>|null $prompts
 * @property int $display_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class IntimacyGame extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'emoji',
        'tagline',
        'description',
        'how_to_play',
        'players',
        'estimated_minutes',
        'intensity',
        'category',
        'prompts',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'intensity' => IntimacyGameIntensity::class,
            'category' => IntimacyGameCategory::class,
            'prompts' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
