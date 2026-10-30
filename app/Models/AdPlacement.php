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
 * @property string|null $description
 * @property string $provider
 * @property string|null $slot_id
 * @property string $placement_type
 * @property bool $is_enabled
 * @property bool $show_on_desktop
 * @property bool $show_on_mobile
 * @property int $frequency_limit
 * @property int $priority
 * @property array<string, mixed>|null $provider_config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AdPlacement extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'provider',
        'slot_id',
        'placement_type',
        'is_enabled',
        'show_on_desktop',
        'show_on_mobile',
        'frequency_limit',
        'priority',
        'provider_config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'show_on_desktop' => 'boolean',
            'show_on_mobile' => 'boolean',
            'frequency_limit' => 'integer',
            'priority' => 'integer',
            'provider_config' => 'array',
        ];
    }

    /**
     * @param  Builder<AdPlacement>  $query
     * @return Builder<AdPlacement>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * @param  Builder<AdPlacement>  $query
     * @return Builder<AdPlacement>
     */
    public function scopeDesktop(Builder $query): Builder
    {
        return $query->where('show_on_desktop', true);
    }

    /**
     * @param  Builder<AdPlacement>  $query
     * @return Builder<AdPlacement>
     */
    public function scopeMobile(Builder $query): Builder
    {
        return $query->where('show_on_mobile', true);
    }

    /**
     * @param  Builder<AdPlacement>  $query
     * @return Builder<AdPlacement>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('id');
    }
}
