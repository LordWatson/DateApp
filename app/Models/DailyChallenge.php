<?php

namespace App\Models;

use Database\Factories\DailyChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** @return BelongsTo<Challenge, $this> */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    /**
     * Today's challenge, picked from the active, non-archived pool the first
     * time anyone asks for it. Null only when that pool is empty.
     */
    public static function forToday(): ?self
    {
        $today = now()->toDateString();
        $existing = self::whereDate('date', $today)->first();

        if ($existing) {
            return $existing;
        }

        $challenge = Challenge::where('active', true)->where('archived', false)->inRandomOrder()->first();

        if (! $challenge) {
            return null;
        }

        try {
            return self::create(['challenge_id' => $challenge->id, 'date' => $today]);
        } catch (UniqueConstraintViolationException) {
            // Another request picked today's challenge first.
            return self::whereDate('date', $today)->first();
        }
    }

    /**
     * @return Collection<int, int> ids of the users who have completed this daily challenge
     */
    public function completedUserIds(): Collection
    {
        return DB::table('daily_challenge_completions')
            ->where('daily_challenge_id', $this->id)
            ->pluck('user_id');
    }
}
