<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $questionnaire_id
 * @property string $title
 * @property string|null $description
 * @property string|null $emoji
 * @property QuestionType $type
 * @property bool $required
 * @property int|null $minimum_value
 * @property int|null $maximum_value
 * @property int $display_order
 */
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'title',
        'description',
        'emoji',
        'type',
        'required',
        'minimum_value',
        'maximum_value',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'required' => 'boolean',
        ];
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('display_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
