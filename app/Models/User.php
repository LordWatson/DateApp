<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\NotificationCategory;
use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
 * @property int|null $role_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Gender|null $gender
 * @property int|null $partner_id
 * @property Carbon|null $connected_at
 * @property Carbon|null $connection_paused_at
 * @property int|null $connection_paused_by
 * @property int|null $former_partner_id
 * @property Carbon|null $date_of_birth
 * @property string|null $avatar
 * @property string $timezone
 * @property Carbon|null $last_completed_questionnaire_at
 * @property int $current_streak
 * @property int $longest_streak
 * @property int $monthly_completion_count
 * @property int $challenge_streak
 * @property int $longest_challenge_streak
 * @property Carbon|null $challenge_streak_date
 * @property bool $email_notifications
 * @property array{emails?: array<string, bool>, reminder_frequency?: string}|null $notification_preferences
 * @property bool $push_notifications
 * @property bool $dark_mode
 * @property bool $onboarding_completed
 * @property bool $is_suspended
 * @property Carbon|null $suspended_at
 * @property string|null $suspension_reason
 * @property string|null $display_name
 * @property Carbon|null $terms_accepted_at
 * @property Carbon|null $ai_consent_at
 * @property Carbon|null $ai_consent_declined_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role|null $role
 */
#[Fillable([
    'role_id',
    'name',
    'display_name',
    'email',
    'password',
    'gender',
    'partner_id',
    'connected_at',
    'connection_paused_at',
    'connection_paused_by',
    'former_partner_id',
    'date_of_birth',
    'avatar',
    'timezone',
    'last_completed_questionnaire_at',
    'current_streak',
    'longest_streak',
    'monthly_completion_count',
    'challenge_streak',
    'longest_challenge_streak',
    'challenge_streak_date',
    'email_notifications',
    'notification_preferences',
    'push_notifications',
    'dark_mode',
    'onboarding_completed',
    'is_suspended',
    'suspended_at',
    'suspension_reason',
    'terms_accepted_at',
    'ai_consent_at',
    'ai_consent_declined_at',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
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
            'connected_at' => 'datetime',
            'connection_paused_at' => 'datetime',
            'challenge_streak_date' => 'date',
            'last_completed_questionnaire_at' => 'datetime',
            'email_notifications' => 'boolean',
            'notification_preferences' => 'array',
            'push_notifications' => 'boolean',
            'dark_mode' => 'boolean',
            'onboarding_completed' => 'boolean',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'ai_consent_at' => 'datetime',
            'ai_consent_declined_at' => 'datetime',
        ];
    }

    /**
     * Whether this person's content may be sent to the AI provider.
     * No answer yet counts as no.
     */
    public function hasAiConsent(): bool
    {
        return $this->ai_consent_at !== null;
    }

    /**
     * True = agreed, false = declined, null = not asked yet.
     */
    public function aiConsent(): ?bool
    {
        return match (true) {
            $this->ai_consent_at !== null => true,
            $this->ai_consent_declined_at !== null => false,
            default => null,
        };
    }

    public function recordAiConsent(bool $agreed): void
    {
        $this->update([
            'ai_consent_at' => $agreed ? now() : null,
            'ai_consent_declined_at' => $agreed ? null : now(),
        ]);
    }

    /**
     * The start of the current day where the user lives. The app runs in UTC,
     * so use this for anything the user would call "today" or "this month".
     * Call `->utc()` on the result before comparing it with a timestamp column.
     */
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone ?: 'UTC')->startOfDay();
    }

    /**
     * Whether to email this person about this kind of thing. `email_notifications`
     * is the master switch; a category is on unless it was switched off.
     */
    public function wantsEmail(NotificationCategory $category): bool
    {
        return $this->email_notifications
            && ($this->notification_preferences['emails'][$category->value] ?? true);
    }

    public function setEmailPreference(NotificationCategory $category, bool $wanted): void
    {
        $preferences = $this->notification_preferences ?? [];
        $preferences['emails'][$category->value] = $wanted;

        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * How often to be reminded to answer a questionnaire: weekly (the default), monthly or off.
     */
    public function reminderFrequency(): string
    {
        return $this->notification_preferences['reminder_frequency'] ?? 'weekly';
    }

    /**
     * Days in a row on which both partners completed the daily challenge.
     * The stored number is only current if the last shared day was today or
     * yesterday (the challenge's own UTC day), so read the streak through this.
     */
    public function currentChallengeStreak(): int
    {
        if ($this->partner_id === null || $this->challenge_streak_date === null) {
            return 0;
        }

        // A paused connection freezes the streak as it stood when the pause began.
        $asOf = $this->connection_paused_at ?? now();

        return $this->challenge_streak_date->toDateString() >= $asOf->subDay()->toDateString()
            ? $this->challenge_streak
            : 0;
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function adminRole(): ?UserRole
    {
        return $this->role ? UserRole::tryFrom($this->role->name) : null;
    }

    public function isAdmin(): bool
    {
        return $this->adminRole()?->isAdmin() ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->adminRole()?->isSuperAdmin() ?? false;
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->adminRole() === $role;
    }

    /** @return BelongsTo<User, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    /**
     * The person this user was last connected to (kept after a disconnect).
     *
     * @return BelongsTo<User, $this>
     */
    public function formerPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'former_partner_id');
    }

    /**
     * A paused couple stays linked and keeps its shared history, but nothing new passes between them.
     */
    public function isConnectionPaused(): bool
    {
        return $this->partner_id !== null && $this->connection_paused_at !== null;
    }

    /**
     * The partner to notify, share with or plan with right now: nobody while the connection is paused.
     * Use `partner` for reading shared history, and this for anything that does something to them.
     */
    public function activePartner(): ?User
    {
        return $this->isConnectionPaused() ? null : $this->partner;
    }

    /** @return HasMany<PartnerInvitation, $this> */
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

    /** @return HasMany<Response, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /** @return HasMany<SavedProfile, $this> */
    public function savedProfiles(): HasMany
    {
        return $this->hasMany(SavedProfile::class);
    }

    /** @return HasMany<CalendarEvent, $this> */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /** @return HasMany<Moment, $this> */
    public function moments(): HasMany
    {
        return $this->hasMany(Moment::class);
    }

    /** @return BelongsToMany<Achievement, $this> */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
