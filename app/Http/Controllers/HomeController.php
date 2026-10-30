<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\GameResource;
use App\Models\Category;
use App\Models\Game;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    public function __invoke(): Response
    {
        $categories = Category::active()
            ->ordered()
            ->withCount('publishedGames')
            ->get();

        $heroGames = Game::published()
            ->featured()
            ->with(['category'])
            ->latest()
            ->take(3)
            ->get();

        if ($heroGames->isEmpty()) {
            $heroGames = Game::published()
                ->with(['category'])
                ->latest()
                ->take(3)
                ->get();
        }

        $featuredGames = Game::published()
            ->featured()
            ->with(['category'])
            ->latest()
            ->take(6)
            ->get();

        $trendingGames = Game::published()
            ->trending()
            ->with(['category'])
            ->latest()
            ->take(6)
            ->get();

        $popularGames = Game::published()
            ->popular()
            ->with(['category'])
            ->latest()
            ->take(6)
            ->get();

        $newGames = Game::published()
            ->with(['category'])
            ->latest()
            ->take(6)
            ->get();

        $recentlyAdded = Game::published()
            ->with(['category'])
            ->latest()
            ->skip(6)
            ->take(6)
            ->get();

        if ($recentlyAdded->isEmpty()) {
            $recentlyAdded = $newGames;
        }

        $allGamesPreview = Game::published()
            ->with(['category'])
            ->latest()
            ->take(12)
            ->get();

        return Inertia::render('Home', [
            'heroGames' => GameResource::collection($heroGames),
            'featuredGames' => GameResource::collection($featuredGames),
            'trendingGames' => GameResource::collection($trendingGames),
            'popularGames' => GameResource::collection($popularGames),
            'newGames' => GameResource::collection($newGames),
            'recentlyAdded' => GameResource::collection($recentlyAdded),
            'allGamesPreview' => GameResource::collection($allGamesPreview),
            'categories' => CategoryResource::collection($categories),
            'meta' => $this->seoService->defaultMeta(),
        ]);
    }
}
