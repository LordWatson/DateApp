<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string|null $description
 * @property bool $enabled
 * @property string $group
 */
class FeatureFlag extends Model
{
    protected $fillable = ['key', 'label', 'description', 'enabled', 'group'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public static function isEnabled(string $key): bool
    {
        return static::where('key', $key)->value('enabled') ?? false;
    }
}
