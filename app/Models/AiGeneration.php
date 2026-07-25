<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $provider
 * @property string|null $model
 * @property string $feature
 * @property string|null $prompt_template
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property int $total_tokens
 * @property int $duration_ms
 * @property bool $successful
 * @property bool $fallback_used
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int|null $user_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AiGeneration extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'feature',
        'prompt_template',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'duration_ms',
        'successful',
        'fallback_used',
        'error_code',
        'error_message',
        'user_id',
        'subject_type',
        'subject_id',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'duration_ms' => 'integer',
            'successful' => 'boolean',
            'fallback_used' => 'boolean',
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
