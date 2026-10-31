<?php

namespace App\Http\Middleware;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\GameResource;
use App\Models\Category;
use App\Models\Game;
use App\Services\Ads\AdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name', 'TapH5 Games'),
            'auth' => [
                'user' => $request->user(),
            ],
            'categoriesNav' => fn () => CategoryResource::collection(
                Category::active()->ordered()->withCount('publishedGames')->get()
            ),
            'megaMenuGames' => fn () => Cache::remember('taph5_mega_menu_games', 300, function () {
                $games = Game::published()
                    ->popular()
                    ->with('category:id,name,slug')
                    ->latest()
                    ->take(10)
                    ->get();

                if ($games->count() < 6) {
                    $fallback = Game::published()
                        ->with('category:id,name,slug')
                        ->latest()
                        ->take(10)
                        ->get();
                    $games = $fallback;
                }

                return GameResource::collection($games)->resolve();
            }),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'pendingReviewCount' => fn () => ($request->user() && $request->user()->isStaff())
                ? Game::where('status', 'draft')->where('review_status', Game::REVIEW_PENDING)->count()
                : 0,
            'adConfig' => fn () => app(AdService::class)->getPublicAdConfig(),
        ];
    }
}
