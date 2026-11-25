<?php

namespace App\Models;

use Database\Factories\DateNightPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
 * @property string|null $last_steer
 * @property int $regeneration_count
 * @property-read bool|null $is_done Whether anyone has marked it done; only set by scopeWithDone.
 * @property-read bool|null $is_favourite Per viewer; only set by scopeWithFavouriteFor or loadExists.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DateNightPlan extends Model
{
    /** @use HasFactory<DateNightPlanFactory> */
    use HasFactory;

    /** How many times a plan can be rewritten with a steer. */
    public const MAX_REGENERATIONS = 3;

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
        'last_steer',
        'regeneration_count',
    ];

    protected function casts(): array
    {
        return [
            'ai_enhanced' => 'boolean',
            'fallback_used' => 'boolean',
            'is_solo' => 'boolean',
            'local_suggestions' => 'array',
            'compatibility_score' => 'integer',
            'regeneration_count' => 'integer',
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

        return $query->notHiddenFor($user)->where(fn (Builder $q) => $q
            ->whereIn('partner_one_response_id', $responseIds)
            ->orWhereIn('partner_two_response_id', $responseIds)
            ->orWhere('partner_user_id', $user->id));
    }

    /**
     * Leaves out plans this person took out of their own account after a connection ended.
     * Add it to any listing or count that does not already use `visibleTo`.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotHiddenFor(Builder $query, User $user): Builder
    {
        return $query->whereNotIn(
            'date_night_plans.id',
            DB::table('date_night_plan_hidden')->where('user_id', $user->id)->select('date_night_plan_id'),
        );
    }

    public function isHiddenFor(User $user): bool
    {
        return DB::table('date_night_plan_hidden')
            ->where('date_night_plan_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * People who marked the plan as done, each with their own rating.
     *
     * @return BelongsToMany<User, $this>
     */
    public function completedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'date_night_plan_completions')
            ->withPivot(['rating', 'note', 'completed_on'])
            ->withTimestamps();
    }

    /**
     * Adds a boolean `is_done` attribute: true once anyone has marked the plan done.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithDone(Builder $query): Builder
    {
        return $query->withExists('completedBy as is_done');
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
