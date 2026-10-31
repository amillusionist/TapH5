<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\GameResource;
use App\Models\Category;
use App\Models\Game;
use App\Services\AnalyticsService;
use App\Services\SeoService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public const CACHE_KEY = 'taph5_home_page_payload';

    public const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        protected SeoService $seoService
    ) {}

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function __invoke(): Response
    {
        $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $categories = Category::active()
                ->ordered()
                ->withCount('publishedGames')
                ->get();

            // Query featured games (up to 6); slice first 3 for hero banner
            $featuredGames = Game::published()
                ->featured()
                ->with(['category:id,name,slug'])
                ->latest()
                ->take(6)
                ->get();

            $heroGames = $featuredGames->take(3);

            // Fallback if no games are flagged featured
            if ($heroGames->isEmpty()) {
                $fallback = Game::published()
                    ->with(['category:id,name,slug'])
                    ->latest()
                    ->take(3)
                    ->get();
                $heroGames = $fallback;
                if ($featuredGames->isEmpty()) {
                    $featuredGames = $fallback;
                }
            }

            // Trending games
            $trendingGames = Game::published()
                ->trending()
                ->with(['category:id,name,slug'])
                ->latest()
                ->take(6)
                ->get();

            // Popular games
            $popularGames = Game::published()
                ->popular()
                ->with(['category:id,name,slug'])
                ->latest()
                ->take(6)
                ->get();

            // Single query for latest games (take 12):
            // Slice 0..6 for newGames, 6..12 for recentlyAdded, and full 12 for allGamesPreview
            $latestGames = Game::published()
                ->with(['category:id,name,slug'])
                ->latest()
                ->take(12)
                ->get();

            $newGames = $latestGames->slice(0, 6)->values();
            $recentlyAdded = $latestGames->count() > 6
                ? $latestGames->slice(6, 6)->values()
                : $newGames;

            return [
                'heroGames' => GameResource::collection($heroGames)->resolve(),
                'featuredGames' => GameResource::collection($featuredGames)->resolve(),
                'trendingGames' => GameResource::collection($trendingGames)->resolve(),
                'popularGames' => GameResource::collection($popularGames)->resolve(),
                'newGames' => GameResource::collection($newGames)->resolve(),
                'recentlyAdded' => GameResource::collection($recentlyAdded)->resolve(),
                'allGamesPreview' => GameResource::collection($latestGames)->resolve(),
                'categories' => CategoryResource::collection($categories)->resolve(),
            ];
        });

        // Overlay fresh active player counts (15s cache) onto cached game listings
        $activeCounts = app(AnalyticsService::class)->getCachedActivePlayersCounts();
        $this->overlayLiveCounts($data, $activeCounts);

        return Inertia::render('Home', array_merge($data, [
            'meta' => $this->seoService->defaultMeta(),
        ]));
    }

    /**
     * Overlay fresh live playing player counts onto cached game collections.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $activeCounts
     */
    protected function overlayLiveCounts(array &$data, array $activeCounts): void
    {
        $keys = ['heroGames', 'featuredGames', 'trendingGames', 'popularGames', 'newGames', 'recentlyAdded', 'allGamesPreview'];

        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                foreach ($data[$key] as &$game) {
                    if (is_array($game) && isset($game['id'])) {
                        $game['playing_now'] = (int) ($activeCounts[$game['id']] ?? 0);
                    }
                }
                unset($game);
            }
        }
    }
}
