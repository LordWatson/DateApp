<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array|null $metadata
 */
#[Fillable([
    'user_id',
    'action',
    'subject_type',
    'subject_id',
    'metadata',
])]
class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
