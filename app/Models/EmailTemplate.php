<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string $subject
 * @property string $html_body
 * @property string|null $text_body
 * @property array|null $variables
 * @property bool $active
 */
class EmailTemplate extends Model
{
    protected $fillable = ['key', 'label', 'subject', 'html_body', 'text_body', 'variables', 'active'];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'active' => 'boolean',
        ];
    }
}
