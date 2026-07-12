<?php

namespace App\Models;

use Database\Factories\DateNightPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $questionnaire_id
 * @property int $partner_one_response_id
 * @property int $partner_two_response_id
 * @property int|null $date_night_theme_id
 * @property int $compatibility_score
 * @property string $theme
 * @property string|null $theme_emoji
 * @property string $summary
 * @property string|null $meal_suggestion
 * @property string|null $drink_suggestion
 * @property string|null $music_vibe
 * @property string|null $atmosphere
 * @property string|null $activity
 * @property string|null $conversation_prompt
 * @property string|null $romantic_challenge
 * @property bool $is_favourite
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DateNightPlan extends Model
{
    /** @use HasFactory<DateNightPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'partner_one_response_id',
        'partner_two_response_id',
        'date_night_theme_id',
        'compatibility_score',
        'theme',
        'theme_emoji',
        'summary',
        'meal_suggestion',
        'drink_suggestion',
        'music_vibe',
        'atmosphere',
        'activity',
        'conversation_prompt',
        'romantic_challenge',
        'is_favourite',
    ];

    protected function casts(): array
    {
        return [
            'is_favourite' => 'boolean',
            'compatibility_score' => 'integer',
        ];
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function partnerOneResponse(): BelongsTo
    {
        return $this->belongsTo(Response::class, 'partner_one_response_id');
    }

    public function partnerTwoResponse(): BelongsTo
    {
        return $this->belongsTo(Response::class, 'partner_two_response_id');
    }

    public function dateNightTheme(): BelongsTo
    {
        return $this->belongsTo(DateNightTheme::class, 'date_night_theme_id');
    }

    public function scopeFavourites(Builder $query): Builder
    {
        return $query->where('is_favourite', true);
    }
}
