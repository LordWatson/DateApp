<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $emoji
 * @property string $category
 * @property int $points
 * @property bool $hidden
 * @property string $unlock_condition
 * @property int $unlock_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'description',
    'emoji',
    'category',
    'points',
    'hidden',
    'unlock_condition',
    'unlock_value',
])]
class Achievement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hidden' => 'boolean',
            'points' => 'integer',
            'unlock_value' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
