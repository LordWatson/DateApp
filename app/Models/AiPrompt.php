<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string $type
 * @property string $content
 * @property string|null $description
 * @property int $version
 * @property bool $active
 */
class AiPrompt extends Model
{
    protected $fillable = ['key', 'label', 'type', 'content', 'description', 'version', 'active'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'version' => 'integer',
        ];
    }
}
