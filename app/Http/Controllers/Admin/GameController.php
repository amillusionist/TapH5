<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use App\Services\Games\GamePublisherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    /**
     * Display a paginated, filterable listing of games for administration.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $categorySlug = $request->string('category')->trim()->toString();
        $gameType = $request->string('game_type')->trim()->toString();
        $sort = $request->string('sort')->trim()->toString() ?: 'newest';

        $query = Game::query()->with(['category', 'tags']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($categorySlug !== '') {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        if ($gameType !== '') {
            $query->where('game_type', $gameType);
        }

        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'plays_desc' => $query->orderBy('plays_count', 'desc'),
            'status' => $query->orderBy('status', 'asc')->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $games = $query->paginate(15)->withQueryString();

        $categories = Category::active()->ordered()->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Games/Index', [
            'games' => $games,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $categorySlug,
                'game_type' => $gameType,
                'sort' => $sort,
            ],
            'statusCounts' => [
                'all' => Game::count(),
                'published' => Game::where('status', 'published')->count(),
                'draft' => Game::where('status', 'draft')->count(),
                'archived' => Game::where('status', 'archived')->count(),
            ],
        ]);
    }

    /**
     * Display detailed inspection and scanner metadata for a game.
     */
    public function show(Game $game): Response
    {
        $game->load(['category', 'tags']);

        return Inertia::render('Admin/Games/Show', [
            'game' => $game,
        ]);
    }

    /**
     * Show the form for editing game metadata and settings.
     */
    public function edit(Game $game): Response
    {
        $game->load(['category', 'tags']);

        $categories = Category::ordered()->get(['id', 'name', 'slug']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Games/Edit', [
            'game' => $game,
            'categories' => $categories,
            'tags' => $tags,
            'selectedTagIds' => $game->tags->pluck('id')->all(),
        ]);
    }

    /**
     * Update game metadata in storage.
     */
    public function update(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:games,slug,'.$game->id],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['boolean'],
            'is_trending' => ['boolean'],
            'is_popular' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        // Explicitly update only permitted metadata fields (no arbitrary path or file updates)
        $game->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'is_trending' => (bool) ($validated['is_trending'] ?? false),
            'is_popular' => (bool) ($validated['is_popular'] ?? false),
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ]);

        if (array_key_exists('tags', $validated)) {
            $game->tags()->sync($validated['tags'] ?? []);
        }

        return redirect()->route('admin.games.show', $game->id)
            ->with('success', "Game '{$game->title}' updated successfully.");
    }

    /**
     * Publish an eligible draft game using the existing publisher service.
     */
    public function publish(Game $game, GamePublisherService $publisherService): RedirectResponse
    {
        if ($game->status !== 'draft') {
            return back()->with('error', "Game #{$game->id} ('{$game->title}') cannot be published: status is '{$game->status}' (expected 'draft').");
        }

        $result = $publisherService->approveAndPublish($game->id);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('admin.games.show', $game->id)
            ->with('success', $result['message']);
    }

    /**
     * Safely archive a game without deleting physical files.
     */
    public function archive(Game $game): RedirectResponse
    {
        if ($game->status === 'archived') {
            return back()->with('info', "Game '{$game->title}' is already archived.");
        }

        $game->update(['status' => 'archived']);

        return back()->with('success', "Game '{$game->title}' has been archived.");
    }
}
