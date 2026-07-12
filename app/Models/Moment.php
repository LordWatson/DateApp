<?php

namespace App\Models;

use Database\Factories\MomentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $date_night_plan_id
 * @property string $title
 * @property string|null $description
 * @property string|null $mood
 * @property string|null $photo
 * @property Carbon $date
 * @property bool $is_favourite
 * @property string|null $private_notes
 * @property array|null $tags
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'date_night_plan_id',
    'title',
    'description',
    'mood',
    'photo',
    'date',
    'is_favourite',
    'private_notes',
    'tags',
])]
class Moment extends Model
{
    /** @use HasFactory<MomentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_favourite' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dateNightPlan(): BelongsTo
    {
        return $this->belongsTo(DateNightPlan::class);
    }
}
