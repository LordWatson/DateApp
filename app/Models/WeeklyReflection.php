<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $partner_id
 * @property Carbon $week_start
 * @property Carbon $week_end
 * @property string $headline
 * @property string $summary
 * @property array<int, string>|null $highlights
 * @property string|null $gentle_suggestion
 * @property string|null $encouragement
 * @property array<string, mixed>|null $metrics
 * @property bool $fallback_used
 * @property Carbon|null $generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'partner_id',
    'week_start',
    'week_end',
    'headline',
    'summary',
    'highlights',
    'gentle_suggestion',
    'encouragement',
    'metrics',
    'fallback_used',
    'generated_at',
])]
class WeeklyReflection extends Model
{
    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'highlights' => 'array',
            'metrics' => 'array',
            'fallback_used' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
