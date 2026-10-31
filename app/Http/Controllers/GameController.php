<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\GameResource;
use App\Models\Category;
use App\Models\Game;
use App\Services\AnalyticsService;
use App\Services\SeoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    /**
     * Return instant lightweight autocomplete suggestions for search.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();
        if ($term === '') {
            $term = $request->string('query')->trim()->toString();
        }

        // Return empty payload immediately for queries shorter than 2 characters
        if (mb_strlen($term) < 2) {
            return response()->json([
                'query' => $term,
                'total' => 0,
                'results' => [],
            ]);
        }

        // Limit query length to 100 characters for safety
        $sanitizedTerm = mb_substr($term, 0, 100);

        // Parameterized search across published games only
        $games = Game::published()
            ->where(function (Builder $query) use ($sanitizedTerm) {
                $query->where('title', 'like', "%{$sanitizedTerm}%")
                    ->orWhere('slug', 'like', "%{$sanitizedTerm}%")
                    ->orWhereHas('category', function (Builder $cq) use ($sanitizedTerm) {
                        $cq->where('name', 'like', "%{$sanitizedTerm}%");
                    });
            })
            ->with(['category:id,name,slug'])
            ->select(['id', 'title', 'slug', 'thumbnail', 'category_id', 'game_type'])
            ->orderByRaw('
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN title LIKE ? THEN 2
                    WHEN slug LIKE ? THEN 3
                    ELSE 4
                END, plays_count DESC
            ', ["{$sanitizedTerm}%", "%{$sanitizedTerm}%", "{$sanitizedTerm}%"])
            ->limit(8)
            ->get();

        return response()->json([
            'query' => $sanitizedTerm,
            'total' => $games->count(),
            'results' => $games->map(fn (Game $game) => [
                'id' => $game->id,
                'title' => $game->title,
                'slug' => $game->slug,
                'url' => route('games.show', ['slug' => $game->slug]),
                'thumbnail' => $game->thumbnail ?: '/images/thumbnails/default-game.svg',
                'category_name' => $game->category?->name ?? 'Arcade',
                'category_slug' => $game->category?->slug,
                'game_type' => $game->game_type,
            ]),
        ]);
    }

    /**
     * Return live active playing player counts for requested or active games.
     */
    public function livePlayers(Request $request): JsonResponse
    {
        $rawIds = $request->query('ids');
        $gameIds = [];

        if (is_string($rawIds) && trim($rawIds) !== '') {
            $gameIds = array_filter(
                array_map('intval', explode(',', $rawIds)),
                fn (int $id) => $id > 0
            );
        }

        $analyticsService = app(AnalyticsService::class);
        $counts = empty($gameIds)
            ? $analyticsService->getCachedActivePlayersCounts()
            : $analyticsService->getActivePlayersCounts($gameIds);

        return response()->json([
            'live_players' => $counts,
        ]);
    }

    /**
     * Return popular games for the All Games Mega Menu.
     */
    public function megaMenu(): JsonResponse
    {
        $games = Cache::remember('taph5_mega_menu_games', 300, function () {
            $popular = Game::published()
                ->popular()
                ->with('category:id,name,slug')
                ->latest()
                ->take(10)
                ->get();

            if ($popular->count() < 6) {
                $fallback = Game::published()
                    ->with('category:id,name,slug')
                    ->latest()
                    ->take(10)
                    ->get();
                $popular = $fallback;
            }

            return GameResource::collection($popular)->resolve();
        });

        return response()->json([
            'games' => $games,
            'data' => $games,
        ]);
    }

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

        $activeCategory = $categorySlug ? $categories->firstWhere('slug', $categorySlug) : null;

        return Inertia::render('Games/Index', [
            'games' => GameResource::collection($games),
            'categories' => CategoryResource::collection($categories),
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'sort' => $sort,
            ],
            'meta' => $this->seoService->forGamesIndex(
                $search ?: null,
                $categorySlug ?: null,
                $sort ?: null,
                $activeCategory
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
