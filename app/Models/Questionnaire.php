<?php

namespace App\Models;

use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireVisibility;
use Database\Factories\QuestionnaireFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $emoji
 * @property string|null $cover_image
 * @property QuestionnaireStatus $status
 * @property QuestionnaireVisibility $visibility
 * @property int|null $estimated_minutes
 * @property int $display_order
 * @property Carbon|null $active_from
 * @property Carbon|null $active_until
 * @property string|null $artwork
 * @property bool $is_seasonal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Questionnaire extends Model
{
    /** @use HasFactory<QuestionnaireFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'emoji',
        'cover_image',
        'status',
        'visibility',
        'estimated_minutes',
        'display_order',
        'active_from',
        'active_until',
        'artwork',
        'is_seasonal',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuestionnaireStatus::class,
            'visibility' => QuestionnaireVisibility::class,
            'active_from' => 'datetime',
            'active_until' => 'datetime',
            'is_seasonal' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('display_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }
}
