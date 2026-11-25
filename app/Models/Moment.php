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
 * @property list<string>|null $photos Paths on the uploads disk, at most MAX_PHOTOS; the first is the cover.
 * @property Carbon $date
 * @property bool $is_favourite
 * @property string|null $private_notes
 * @property array<int, string>|null $tags
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'date_night_plan_id',
    'title',
    'description',
    'mood',
    'photos',
    'date',
    'is_favourite',
    'private_notes',
    'tags',
])]
class Moment extends Model
{
    /** @use HasFactory<MomentFactory> */
    use HasFactory;

    public const MAX_PHOTOS = 3;

    /**
     * One `moments.photo` URL per photo. The file name is in the query string,
     * so a replaced photo is not served from the browser cache.
     *
     * @return list<string>
     */
    public function photoUrls(): array
    {
        $photos = $this->photos ?? [];

        return array_map(
            fn (string $path, int $index): string => route('moments.photo', ['moment' => $this, 'index' => $index, 'v' => basename($path)]),
            $photos,
            array_keys($photos),
        );
    }

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'date' => 'date',
            'is_favourite' => 'boolean',
            'tags' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<DateNightPlan, $this> */
    public function dateNightPlan(): BelongsTo
    {
        return $this->belongsTo(DateNightPlan::class);
    }
}
