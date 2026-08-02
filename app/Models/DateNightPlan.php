<?php

namespace App\Models;

use Database\Factories\DateNightPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $questionnaire_id
 * @property int $partner_one_response_id
 * @property int $partner_two_response_id
 * @property int|null $date_night_theme_id
 * @property int|null $partner_user_id
 * @property int $compatibility_score
 * @property string $theme
 * @property string|null $theme_emoji
 * @property string $summary
 * @property string|null $meal_suggestion
 * @property string|null $atmosphere
 * @property string|null $activity
 * @property string|null $conversation_prompt
 * @property string|null $romantic_challenge
 * @property bool $is_solo
 * @property array<int, array<string, string>>|null $local_suggestions
 * @property string|null $location_label
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
        'partner_user_id',
        'compatibility_score',
        'theme',
        'theme_emoji',
        'summary',
        'meal_suggestion',
        'atmosphere',
        'activity',
        'conversation_prompt',
        'romantic_challenge',
        'is_solo',
        'local_suggestions',
        'location_label',
        'is_favourite',
        'ai_enhanced',
        'fallback_used',
    ];

    protected function casts(): array
    {
        return [
            'is_favourite' => 'boolean',
            'ai_enhanced' => 'boolean',
            'fallback_used' => 'boolean',
            'is_solo' => 'boolean',
            'local_suggestions' => 'array',
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

    public function partnerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    public function scopeFavourites(Builder $query): Builder
    {
        return $query->where('is_favourite', true);
    }

    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'date_night_plan_likes')
            ->withTimestamps();
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likedBy()->whereKey($user->id)->exists();
    }
}
