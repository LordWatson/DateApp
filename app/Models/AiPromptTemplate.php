<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AiPromptTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $system_prompt
 * @property string $user_prompt_template
 * @property string|null $description
 * @property int $version
 * @property bool $active
 */
class AiPromptTemplate extends Model
{
    /** @use HasFactory<AiPromptTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'system_prompt',
        'user_prompt_template',
        'description',
        'version',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): AiPromptTemplateFactory
    {
        return AiPromptTemplateFactory::new();
    }
}
