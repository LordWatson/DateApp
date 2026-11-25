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
 * @property-read bool|null $is_favourite Per viewer; only set by scopeWithFavouriteFor or loadExists.
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
        'ai_enhanced',
        'fallback_used',
    ];

    protected function casts(): array
    {
        return [
            'ai_enhanced' => 'boolean',
            'fallback_used' => 'boolean',
            'is_solo' => 'boolean',
            'local_suggestions' => 'array',
            'compatibility_score' => 'integer',
        ];
    }

    /** @return BelongsTo<Questionnaire, $this> */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    /** @return BelongsTo<Response, $this> */
    public function partnerOneResponse(): BelongsTo
    {
        return $this->belongsTo(Response::class, 'partner_one_response_id');
    }

    /** @return BelongsTo<Response, $this> */
    public function partnerTwoResponse(): BelongsTo
    {
        return $this->belongsTo(Response::class, 'partner_two_response_id');
    }

    /** @return BelongsTo<DateNightTheme, $this> */
    public function dateNightTheme(): BelongsTo
    {
        return $this->belongsTo(DateNightTheme::class, 'date_night_theme_id');
    }

    /** @return BelongsTo<User, $this> */
    public function partnerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function favouritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'date_night_plan_favourites')
            ->withTimestamps();
    }

    /**
     * Favourites are per person: only plans this user saved.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFavouritedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('favouritedBy', fn (Builder $q) => $q->whereKey($user->id));
    }

    /**
     * Adds a boolean `is_favourite` attribute for this viewer.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithFavouriteFor(Builder $query, User $user): Builder
    {
        return $query->withExists(['favouritedBy as is_favourite' => fn (Builder $q) => $q->whereKey($user->id)]);
    }

    /**
     * Plans this user took part in: one of the two responses is theirs, or a solo plan was shared with them.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $responseIds = Response::where('user_id', $user->id)->select('id');

        return $query->where(fn (Builder $q) => $q
            ->whereIn('partner_one_response_id', $responseIds)
            ->orWhereIn('partner_two_response_id', $responseIds)
            ->orWhere('partner_user_id', $user->id));
    }

    /** @return BelongsToMany<User, $this> */
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
