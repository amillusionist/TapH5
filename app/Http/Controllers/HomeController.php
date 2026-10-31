<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\AnalyticsService;
use App\Services\HomepageContentService;
use App\Services\PlatformSettingService;
use App\Services\SeoService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public const CACHE_KEY = 'taph5_home_page_payload';

    public const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        protected SeoService $seoService,
        protected HomepageContentService $homepageService,
        protected PlatformSettingService $settingService
    ) {}

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function __invoke(): Response
    {
        $payload = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $data = $this->homepageService->getPublicHomepagePayload();

            return [
                'heroGames' => $data['heroGames'],
                'featuredGames' => $data['sectionGames']['featured'] ?? [],
                'trendingGames' => $data['sectionGames']['trending'] ?? [],
                'popularGames' => $data['sectionGames']['popular'] ?? [],
                'newGames' => $data['sectionGames']['new'] ?? [],
                'recentlyAdded' => $data['sectionGames']['recently_added'] ?? [],
                'allGamesPreview' => $data['sectionGames']['new'] ?? [],
                'categories' => $data['categories'],
                'sections' => $data['sections'],
                'sectionGames' => $data['sectionGames'],
            ];
        });

        // Overlay fresh active player counts (15s cache) onto cached game listings
        $activeCounts = app(AnalyticsService::class)->getCachedActivePlayersCounts();
        $this->overlayLiveCounts($payload, $activeCounts);

        return Inertia::render('Home', array_merge($payload, [
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
