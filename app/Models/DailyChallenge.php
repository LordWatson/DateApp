<?php

namespace App\Models;

use Database\Factories\DailyChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $challenge_id
 * @property Carbon $date
 */
class DailyChallenge extends Model
{
    /** @use HasFactory<DailyChallengeFactory> */
    use HasFactory;

    protected $fillable = [
        'challenge_id',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
