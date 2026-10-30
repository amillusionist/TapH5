<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\GameResource;
use App\Models\Category;
use App\Models\Game;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    /**
     * Display a listing of games with search, category filtering, and sorting.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $categorySlug = $request->string('category')->trim()->toString();
        $sort = $request->string('sort')->trim()->toString() ?: 'newest';

        $query = Game::published()
            ->with(['category', 'tags'])
            ->search($search)
            ->filterCategory($categorySlug)
            ->sortBy($sort);

        $games = $query->paginate(18)->withQueryString();

        $categories = Category::active()
            ->ordered()
            ->withCount('publishedGames')
            ->get();

        $pageTitle = 'All Free HTML5 Games';
        if ($categorySlug && $cat = $categories->firstWhere('slug', $categorySlug)) {
            $pageTitle = "{$cat->name} Games";
        } elseif ($search) {
            $pageTitle = "Search: {$search}";
        }

        return Inertia::render('Games/Index', [
            'games' => GameResource::collection($games),
            'categories' => CategoryResource::collection($categories),
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'sort' => $sort,
            ],
            'meta' => $this->seoService->forPage(
                $pageTitle,
                'Explore and play all free HTML5 games on TapH5 Games. Filter by category, search favorites, and play instantly in any browser.',
                route('games.index')
            ),
        ]);
    }

    /**
     * Display the specified game page.
     */
    public function show(string $slug): Response
    {
        $game = Game::published()
            ->where('slug', $slug)
            ->with(['category', 'tags'])
            ->firstOrFail();

        // Increment play count without bumping updated_at
        $game->timestamps = false;
        $game->increment('plays_count');
        $game->timestamps = true;

        $relatedGames = Game::published()
            ->where('id', '!=', $game->id)
            ->where('category_id', $game->category_id)
            ->with(['category'])
            ->latest()
            ->take(6)
            ->get();

        if ($relatedGames->count() < 4) {
            $extraGames = Game::published()
                ->where('id', '!=', $game->id)
                ->whereNotIn('id', $relatedGames->pluck('id'))
                ->with(['category'])
                ->latest()
                ->take(6 - $relatedGames->count())
                ->get();

            $relatedGames = $relatedGames->concat($extraGames);
        }

        return Inertia::render('Games/Show', [
            'game' => new GameResource($game),
            'relatedGames' => GameResource::collection($relatedGames),
            'meta' => $this->seoService->forGame($game),
        ]);
    }
}
