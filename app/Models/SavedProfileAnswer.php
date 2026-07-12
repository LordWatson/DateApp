<?php

namespace App\Models;

use Database\Factories\SavedProfileAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $saved_profile_id
 * @property int $question_id
 * @property int|null $question_option_id
 * @property string|null $value
 */
class SavedProfileAnswer extends Model
{
    /** @use HasFactory<SavedProfileAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'saved_profile_id',
        'question_id',
        'question_option_id',
        'value',
    ];

    public function savedProfile(): BelongsTo
    {
        return $this->belongsTo(SavedProfile::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function questionOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class);
    }
}
