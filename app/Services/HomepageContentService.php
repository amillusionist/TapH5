<?php

namespace App\Services;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\GameResource;
use App\Models\Category;
use App\Models\Game;
use App\Models\HomepageSection;
use App\Models\HomepageSlide;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class HomepageContentService
{
    public const CACHE_KEY = 'taph5_homepage_sections_and_slides';

    public const CACHE_TTL = 300; // 5 minutes

    /**
     * Default section configurations mirroring existing homepage.
     */
    protected const DEFAULT_SECTIONS = [
        [
            'section_key' => 'genres',
            'title' => 'Explore Game Genres',
            'subtitle' => 'Browse our collection organized by gameplay mechanics and genres',
            'sort_order' => 1,
            'items_limit' => 6,
            'is_enabled' => true,
        ],
        [
            'section_key' => 'featured',
            'title' => 'Featured Spotlight',
            'subtitle' => 'Handpicked premiere titles curated for immediate in-browser play',
            'sort_order' => 2,
            'items_limit' => 6,
            'is_enabled' => true,
        ],
        [
            'section_key' => 'trending',
            'title' => 'Trending Now',
            'subtitle' => 'Top games capturing player attention and active gameplay today',
            'sort_order' => 3,
            'items_limit' => 6,
            'is_enabled' => true,
        ],
        [
            'section_key' => 'new',
            'title' => 'New Releases',
            'subtitle' => 'Fresh arrivals recently published to the TapH5 catalog',
            'sort_order' => 4,
            'items_limit' => 6,
            'is_enabled' => true,
        ],
        [
            'section_key' => 'popular',
            'title' => 'Popular Games',
            'subtitle' => 'Community favorites across all genres and platforms',
            'sort_order' => 5,
            'items_limit' => 6,
            'is_enabled' => true,
        ],
        [
            'section_key' => 'recently_added',
            'title' => 'Recently Cataloged',
            'subtitle' => 'Latest verified HTML5 titles added to our gaming library',
            'sort_order' => 6,
            'items_limit' => 6,
            'is_enabled' => true,
        ],
        [
            'section_key' => 'catalog_cta',
            'title' => 'Browse Our Complete Game Catalog',
            'subtitle' => 'Filter by gameplay genre, search by title, sort by popularity or release date, and enjoy instant HTML5 games across all devices.',
            'sort_order' => 7,
            'items_limit' => 0,
            'is_enabled' => true,
        ],
    ];

    /**
     * Get all homepage sections ordered by sort order.
     */
    public function getSections(): Collection
    {
        $this->seedDefaultsIfEmpty();

        return HomepageSection::ordered()->get();
    }

    /**
     * Get active custom slides with their associated game model.
     */
    public function getSlides(): Collection
    {
        return HomepageSlide::with('game.category')->ordered()->get();
    }

    /**
     * Get resolved homepage content payload ready for Inertia rendering.
     *
     * @return array<string, mixed>
     */
    public function getPublicHomepagePayload(): array
    {
        $this->seedDefaultsIfEmpty();

        $sections = HomepageSection::ordered()->get();

        // 1. Prepare Categories
        $categories = Category::active()
            ->ordered()
            ->withCount('publishedGames')
            ->get();

        // 2. Prepare Hero Slides
        $customSlides = HomepageSlide::enabled()
            ->with('game.category:id,name,slug')
            ->ordered()
            ->get();

        $heroGames = collect();
        if ($customSlides->isNotEmpty()) {
            foreach ($customSlides as $slide) {
                if ($slide->game && $slide->game->status === 'published') {
                    $heroGames->push($slide->game);
                }
            }
        }

        // If no custom slides with published games, fallback to featured games
        if ($heroGames->isEmpty()) {
            $heroGames = Game::published()
                ->featured()
                ->with(['category:id,name,slug'])
                ->latest()
                ->take(3)
                ->get();

            if ($heroGames->isEmpty()) {
                $heroGames = Game::published()
                    ->with(['category:id,name,slug'])
                    ->latest()
                    ->take(3)
                    ->get();
            }
        }

        // 3. Prepare Games for Sections
        $sectionGames = [];
        foreach ($sections as $section) {
            if (! $section->is_enabled) {
                $sectionGames[$section->section_key] = [];

                continue;
            }

            $limit = $section->items_limit > 0 ? $section->items_limit : 6;

            // Check if section has manually curated game IDs
            if (! empty($section->curated_game_ids)) {
                $curated = Game::published()
                    ->whereIn('id', $section->curated_game_ids)
                    ->with(['category:id,name,slug'])
                    ->get();

                if ($curated->isNotEmpty()) {
                    $orderMap = array_flip($section->curated_game_ids);
                    $sorted = $curated->sortBy(fn (Game $g) => $orderMap[$g->id] ?? 99999)->values()->take($limit);
                    $sectionGames[$section->section_key] = GameResource::collection($sorted)->resolve();

                    continue;
                }
            }

            // Automatic query based on section_key
            $games = match ($section->section_key) {
                'featured' => Game::published()->featured()->with(['category:id,name,slug'])->latest()->take($limit)->get(),
                'trending' => Game::published()->trending()->with(['category:id,name,slug'])->latest()->take($limit)->get(),
                'popular' => Game::published()->popular()->with(['category:id,name,slug'])->latest()->take($limit)->get(),
                'new' => Game::published()->with(['category:id,name,slug'])->latest()->take($limit)->get(),
                'recently_added' => Game::published()->with(['category:id,name,slug'])->latest()->skip(6)->take($limit)->get(),
                default => collect(),
            };

            // If empty for featured/popular, use fallback published
            if ($games->isEmpty() && in_array($section->section_key, ['featured', 'popular', 'trending'], true)) {
                $games = Game::published()->with(['category:id,name,slug'])->latest()->take($limit)->get();
            }

            $sectionGames[$section->section_key] = GameResource::collection($games)->resolve();
        }

        return [
            'heroGames' => GameResource::collection($heroGames)->resolve(),
            'sections' => $sections->keyBy('section_key')->toArray(),
            'sectionGames' => $sectionGames,
            'categories' => CategoryResource::collection($categories)->resolve(),
        ];
    }

    /**
     * Invalidate homepage cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('taph5_home_page_payload');
    }

    /**
     * Seed initial default sections if none exist.
     */
    public function seedDefaultsIfEmpty(): void
    {
        if (HomepageSection::count() === 0) {
            foreach (self::DEFAULT_SECTIONS as $sec) {
                HomepageSection::create($sec);
            }
        }
    }
}
