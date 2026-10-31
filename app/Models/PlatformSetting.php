<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $group
 * @property string|null $value
 * @property string $type
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlatformSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'group',
        'value',
        'type',
        'description',
    ];

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Get typed value.
     */
    public function getTypedValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json' => json_decode($this->value, true),
            default => (string) $this->value,
        };
    }
}
