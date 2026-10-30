<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property bool $is_enabled
 * @property string|null $publisher_id
 * @property bool $test_mode
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AdProvider extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name',
        'is_enabled',
        'publisher_id',
        'test_mode',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'test_mode' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * @param  Builder<AdProvider>  $query
     * @return Builder<AdProvider>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Determine whether the provider has required credentials configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->publisher_id);
    }
}
