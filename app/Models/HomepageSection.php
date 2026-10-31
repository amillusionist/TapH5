<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $section_key
 * @property string $title
 * @property string|null $subtitle
 * @property bool $is_enabled
 * @property int $sort_order
 * @property int $items_limit
 * @property array<int>|null $curated_game_ids
 * @property array<string, mixed>|null $display_options
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HomepageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_key',
        'title',
        'subtitle',
        'is_enabled',
        'sort_order',
        'items_limit',
        'curated_game_ids',
        'display_options',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'items_limit' => 'integer',
            'curated_game_ids' => 'array',
            'display_options' => 'array',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
