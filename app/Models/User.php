<?php

namespace App\Models;

use App\Enums\Gender;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Gender|null $gender
 * @property int|null $partner_id
 * @property Carbon|null $date_of_birth
 * @property string|null $avatar
 * @property string $timezone
 * @property Carbon|null $last_completed_questionnaire_at
 * @property int $current_streak
 * @property int $longest_streak
 * @property int $monthly_completion_count
 * @property bool $email_notifications
 * @property bool $push_notifications
 * @property bool $dark_mode
 * @property bool $onboarding_completed
 * @property string|null $display_name
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'display_name',
    'email',
    'password',
    'gender',
    'partner_id',
    'date_of_birth',
    'avatar',
    'timezone',
    'last_completed_questionnaire_at',
    'current_streak',
    'longest_streak',
    'monthly_completion_count',
    'email_notifications',
    'push_notifications',
    'dark_mode',
    'onboarding_completed',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'gender' => Gender::class,
            'date_of_birth' => 'date',
            'last_completed_questionnaire_at' => 'datetime',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'dark_mode' => 'boolean',
            'onboarding_completed' => 'boolean',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(PartnerInvitation::class, 'sender_id');
    }

    public function pendingInvitation(): ?PartnerInvitation
    {
        return $this->sentInvitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function savedProfiles(): HasMany
    {
        return $this->hasMany(SavedProfile::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function moments(): HasMany
    {
        return $this->hasMany(Moment::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(TimelineEntry::class);
    }
}
