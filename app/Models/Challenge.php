<?php

namespace App\Models;

use App\Enums\ChallengeDifficulty;
use Database\Factories\ChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $emoji
 * @property ChallengeDifficulty $difficulty
 * @property bool $active
 * @property int $display_order
 */
class Challenge extends Model
{
    /** @use HasFactory<ChallengeFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'emoji',
        'difficulty',
        'category',
        'season',
        'weight',
        'active',
        'archived',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => ChallengeDifficulty::class,
            'active' => 'boolean',
            'archived' => 'boolean',
            'weight' => 'integer',
        ];
    }

    public function dailyChallenges(): HasMany
    {
        return $this->hasMany(DailyChallenge::class);
    }
}
