<?php

namespace App\Models;

use App\Http\Controllers\HomeController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $instructions
 * @property string|null $thumbnail
 * @property int|null $category_id
 * @property string $game_type
 * @property string $entry_file
 * @property string|null $storage_path
 * @property string|null $fingerprint
 * @property array<string, mixed>|null $scan_metadata
 * @property string $status
 * @property bool $is_featured
 * @property bool $is_trending
 * @property bool $is_popular
 * @property int $plays_count
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Game extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'instructions',
        'thumbnail',
        'category_id',
        'game_type',
        'entry_file',
        'storage_path',
        'fingerprint',
        'scan_metadata',
        'status',
        'is_featured',
        'is_trending',
        'is_popular',
        'plays_count',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_popular' => 'boolean',
            'plays_count' => 'integer',
            'scan_metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            HomeController::clearCache();
        });

        static::deleted(function () {
            HomeController::clearCache();
        });
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * @return HasMany<AnalyticsEvent, $this>
     */
    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    /**
     * @return HasMany<AnalyticsDailyAggregate, $this>
     */
    public function analyticsDailyAggregates(): HasMany
    {
        return $this->hasMany(AnalyticsDailyAggregate::class);
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true);
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->where('is_popular', true);
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopeFilterCategory(Builder $query, ?string $categorySlug): Builder
    {
        if (blank($categorySlug)) {
            return $query;
        }

        return $query->whereHas('category', function (Builder $q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'alpha' => $query->orderBy('title', 'asc'),
            'popular' => $query->orderBy('plays_count', 'desc')->orderBy('created_at', 'desc'),
            'featured' => $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
