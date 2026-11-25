<?php

namespace App\Models;

use Database\Factories\LoveNoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sender_id
 * @property int $recipient_id
 * @property string $message
 * @property Carbon|null $read_at
 * @property string|null $reaction
 * @property Carbon|null $reacted_at
 * @property Carbon|null $hidden_for_sender_at
 * @property Carbon|null $hidden_for_recipient_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LoveNote extends Model
{
    /** @use HasFactory<LoveNoteFactory> */
    use HasFactory;

    /** The reactions a recipient can leave on a note. */
    public const REACTIONS = ['❤️', '🥰', '😂', '😘', '🔥'];

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'message',
        'read_at',
        'reaction',
        'reacted_at',
        'hidden_for_sender_at',
        'hidden_for_recipient_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'reacted_at' => 'datetime',
            'hidden_for_sender_at' => 'datetime',
            'hidden_for_recipient_at' => 'datetime',
        ];
    }

    /**
     * Notes this person sent or received and has not taken out of their own account.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $sent) => $sent->where('sender_id', $user->id)->whereNull('hidden_for_sender_at'))
            ->orWhere(fn (Builder $received) => $received->where('recipient_id', $user->id)->whereNull('hidden_for_recipient_at')));
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => Carbon::now()]);
        }
    }
}
