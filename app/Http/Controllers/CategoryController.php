<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\GameResource;
use App\Models\Category;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    public function show(Request $request, string $slug): Response
    {
        $category = Category::active()
            ->where('slug', $slug)
            ->withCount('publishedGames')
            ->firstOrFail();

        $search = $request->string('search')->trim()->toString();
        $sort = $request->string('sort')->trim()->toString() ?: 'newest';

        $games = $category->publishedGames()
            ->with(['category', 'tags'])
            ->search($search)
            ->sortBy($sort)
            ->paginate(18)
            ->withQueryString();

        return Inertia::render('Categories/Show', [
            'category' => new CategoryResource($category),
            'games' => GameResource::collection($games),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'meta' => $this->seoService->forCategory($category),
        ]);
    }
}
