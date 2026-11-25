<?php

namespace App\Models;

use App\Enums\CalendarRecurrence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property string|null $emoji
 * @property Carbon $date
 * @property string|null $time
 * @property string|null $location
 * @property string|null $notes
 * @property string $colour
 * @property string|null $reminder
 * @property bool $reminder_sent
 * @property CalendarRecurrence|null $recurrence
 * @property Carbon|null $reminded_for_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'title',
    'description',
    'type',
    'emoji',
    'date',
    'time',
    'location',
    'notes',
    'colour',
    'reminder',
    'reminder_sent',
    'recurrence',
    'reminded_for_date',
])]
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reminder_sent' => 'boolean',
            'recurrence' => CalendarRecurrence::class,
            'reminded_for_date' => 'date',
        ];
    }

    /**
     * The date this event next falls on, counting from `$today` (inclusive).
     * A one-off event is always its own date; a yearly one moves to this year
     * or next. 29 February falls on 28 February in other years.
     */
    public function nextOccurrence(CarbonInterface $today): CarbonImmutable
    {
        // Plain calendar dates: compared without any timezone shifting.
        $date = CarbonImmutable::parse($this->date->toDateString());
        $from = CarbonImmutable::parse($today->toDateString());

        if ($this->recurrence !== CalendarRecurrence::Yearly || $date->gte($from)) {
            return $date;
        }

        $years = $from->year - $date->year;
        $thisYear = $date->addYearsNoOverflow($years);

        return $thisYear->gte($from) ? $thisYear : $date->addYearsNoOverflow($years + 1);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
