<?php

namespace App\Models;

use App\Enums\CompletionStatus;
use Database\Factories\ResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $questionnaire_id
 * @property CompletionStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property int|null $compatibility_score
 * @property string|null $location_label
 * @property string|null $location_city
 * @property string|null $location_region
 * @property string|null $location_country
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Response extends Model
{
    /** @use HasFactory<ResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'questionnaire_id',
        'status',
        'started_at',
        'completed_at',
        'compatibility_score',
        'location_label',
        'location_city',
        'location_region',
        'location_country',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompletionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
