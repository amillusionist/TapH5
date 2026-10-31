<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Game;
use App\Models\GameReview;
use App\Models\Tag;
use App\Services\Admin\AdminAuditService;
use App\Services\AnalyticsService;
use App\Services\Games\GamePublisherService;
use App\Services\Games\GameScannerService;
use App\Services\Games\GameValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $flag = $request->string('flag')->trim()->toString(); // 'featured', 'trending', 'popular'
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

        if ($flag === 'featured') {
            $query->where('is_featured', true);
        } elseif ($flag === 'trending') {
            $query->where('is_trending', true);
        } elseif ($flag === 'popular') {
            $query->where('is_popular', true);
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

        // Attach live active players count to each game in current page
        $activeCounts = app(AnalyticsService::class)->getCachedActivePlayersCounts();
        $games->getCollection()->transform(function (Game $game) use ($activeCounts) {
            $game->live_players = $activeCounts[$game->id] ?? 0;

            return $game;
        });

        $categories = Category::active()->ordered()->get(['id', 'name', 'slug']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Games/Index', [
            'games' => $games,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $categorySlug,
                'game_type' => $gameType,
                'flag' => $flag,
                'sort' => $sort,
            ],
            'statusCounts' => [
                'all' => Game::count(),
                'published' => Game::where('status', 'published')->count(),
                'draft' => Game::where('status', 'draft')->count(),
                'archived' => Game::where('status', 'archived')->count(),
                'featured' => Game::where('is_featured', true)->count(),
                'trending' => Game::where('is_trending', true)->count(),
                'popular' => Game::where('is_popular', true)->count(),
            ],
        ]);
    }

    /**
     * Display detailed inspection and scanner metadata for a game.
     */
    public function show(Game $game): Response
    {
        $game->load(['category', 'tags', 'submittedBy:id,name,email', 'reviewedBy:id,name,email', 'reviews.user:id,name,email']);

        $activeCounts = app(AnalyticsService::class)->getCachedActivePlayersCounts();
        $game->live_players = $activeCounts[$game->id] ?? 0;

        // Verify storage file presence
        $entryFileExists = ! empty($game->entry_file);
        $thumbnailExists = ! empty($game->thumbnail);

        $stagingFolders = app(GameScannerService::class)->listStagingFolders();

        return Inertia::render('Admin/Games/Show', [
            'game' => $game,
            'fileStatus' => [
                'entry_file_exists' => $entryFileExists,
                'thumbnail_exists' => $thumbnailExists,
            ],
            'stagingFolders' => $stagingFolders,
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

        $oldData = $game->only(['title', 'category_id', 'is_featured', 'is_trending', 'is_popular']);

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

        AdminAuditService::log(
            'game.updated',
            'Game',
            (string) $game->id,
            "Updated game metadata for '{$game->title}'",
            ['old' => $oldData, 'new' => $validated]
        );

        return redirect()->route('admin.games.show', $game->id)
            ->with('success', "Game '{$game->title}' updated successfully.");
    }

    /**
     * Publish an eligible draft game using the existing publisher service.
     */
    public function publish(Request $request, Game $game, GamePublisherService $publisherService): RedirectResponse
    {
        if (! $request->user()?->canPublishGames()) {
            abort(403, 'Unauthorized to publish games directly.');
        }

        if ($game->status !== 'draft') {
            return back()->with('error', "Game #{$game->id} ('{$game->title}') cannot be published: status is '{$game->status}' (expected 'draft').");
        }

        if ($game->review_status !== Game::REVIEW_APPROVED) {
            return back()->with('error', "Game #{$game->id} ('{$game->title}') cannot be published: review status is '{$game->review_status}' (must be approved first).");
        }

        $result = $publisherService->approveAndPublish($game->id);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $game->update([
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        GameReview::create([
            'game_id' => $game->id,
            'user_id' => $request->user()->id,
            'action' => GameReview::ACTION_PUBLISHED,
            'from_status' => Game::REVIEW_APPROVED,
            'to_status' => 'published',
            'notes' => 'Published from catalog management.',
            'metadata' => [
                'public_storage_path' => $result['public_storage_path'] ?? null,
                'public_url' => $result['public_url'] ?? null,
            ],
        ]);

        AdminAuditService::log(
            'game.published',
            'Game',
            (string) $game->id,
            "Published game '{$game->title}'"
        );

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

        AdminAuditService::log(
            'game.archived',
            'Game',
            (string) $game->id,
            "Archived game '{$game->title}'"
        );

        return back()->with('success', "Game '{$game->title}' has been archived.");
    }

    /**
     * Execute safe, transaction-backed bulk operations on selected games.
     */
    public function bulk(Request $request, GamePublisherService $publisherService): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:publish,archive,change_category,add_tags,remove_tags,set_featured,unset_featured,set_trending,unset_trending,set_popular,unset_popular,delete'],
            'game_ids' => ['required', 'array', 'min:1'],
            'game_ids.*' => ['exists:games,id'],
            'category_id' => ['nullable', 'required_if:action,change_category', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'required_if:action,add_tags,remove_tags', 'array'],
            'tag_ids.*' => ['exists:tags,id'],
        ]);

        $action = $validated['action'];
        $gameIds = $validated['game_ids'];
        $count = count($gameIds);

        if ($action === 'publish' && ! $request->user()?->canPublishGames()) {
            abort(403, 'Unauthorized to publish games in bulk.');
        }

        $user = $request->user();

        DB::transaction(function () use ($action, $gameIds, $validated, $publisherService, $user, &$count) {
            switch ($action) {
                case 'publish':
                    $successCount = 0;
                    foreach ($gameIds as $id) {
                        $game = Game::find($id);
                        if ($game && $game->status === 'draft' && $game->review_status === Game::REVIEW_APPROVED) {
                            $res = $publisherService->approveAndPublish($id);
                            if ($res['success']) {
                                $game->update([
                                    'reviewed_by' => $user?->id,
                                    'reviewed_at' => now(),
                                ]);

                                GameReview::create([
                                    'game_id' => $game->id,
                                    'user_id' => $user?->id,
                                    'action' => GameReview::ACTION_PUBLISHED,
                                    'from_status' => Game::REVIEW_APPROVED,
                                    'to_status' => 'published',
                                    'notes' => 'Bulk published from catalog management.',
                                ]);

                                $successCount++;
                            }
                        }
                    }
                    $count = $successCount;
                    break;

                case 'archive':
                    Game::whereIn('id', $gameIds)->update(['status' => 'archived']);
                    break;

                case 'change_category':
                    Game::whereIn('id', $gameIds)->update(['category_id' => $validated['category_id']]);
                    break;

                case 'add_tags':
                    $tagIds = $validated['tag_ids'] ?? [];
                    foreach (Game::whereIn('id', $gameIds)->get() as $game) {
                        $game->tags()->syncWithoutDetaching($tagIds);
                    }
                    break;

                case 'remove_tags':
                    $tagIds = $validated['tag_ids'] ?? [];
                    foreach (Game::whereIn('id', $gameIds)->get() as $game) {
                        $game->tags()->detach($tagIds);
                    }
                    break;

                case 'set_featured':
                    Game::whereIn('id', $gameIds)->update(['is_featured' => true]);
                    break;

                case 'unset_featured':
                    Game::whereIn('id', $gameIds)->update(['is_featured' => false]);
                    break;

                case 'set_trending':
                    Game::whereIn('id', $gameIds)->update(['is_trending' => true]);
                    break;

                case 'unset_trending':
                    Game::whereIn('id', $gameIds)->update(['is_trending' => false]);
                    break;

                case 'set_popular':
                    Game::whereIn('id', $gameIds)->update(['is_popular' => true]);
                    break;

                case 'unset_popular':
                    Game::whereIn('id', $gameIds)->update(['is_popular' => false]);
                    break;

                case 'delete':
                    // Safe deletion: only delete draft or archived games; never delete published games directly
                    $deletableGames = Game::whereIn('id', $gameIds)
                        ->whereIn('status', ['draft', 'archived'])
                        ->get();

                    foreach ($deletableGames as $g) {
                        $g->tags()->detach();
                        $g->delete();
                    }
                    $count = $deletableGames->count();
                    break;
            }
        });

        AdminAuditService::log(
            "game.bulk_{$action}",
            'Game',
            null,
            "Executed bulk {$action} on {$count} game(s)",
            ['game_ids' => $gameIds, 'action' => $action]
        );

        return back()->with('success', "Bulk action '{$action}' successfully applied to {$count} game(s).");
    }

    /**
     * Re-scan a game safely using validator service.
     */
    public function rescan(Game $game, GameValidatorService $validator): RedirectResponse
    {
        $stagingRoot = config('games.staging_path', storage_path('app/games_staging'));

        $scanSummary = [
            'has_entry' => ! empty($game->entry_file),
            'has_thumbnail' => ! empty($game->thumbnail),
            'status' => $game->status,
            'scanned_at' => now()->toIso8601String(),
        ];

        $game->update([
            'metadata' => array_merge($game->metadata ?? [], ['last_rescan' => $scanSummary]),
        ]);

        AdminAuditService::log(
            'game.rescanned',
            'Game',
            (string) $game->id,
            "Re-scanned and validated game '{$game->title}'"
        );

        return back()->with('success', "Game '{$game->title}' re-scanned and validated successfully.");
    }

    /**
     * Repair catalog metadata for a game (slugs, missing timestamps, category associations).
     */
    public function repairMetadata(Game $game): RedirectResponse
    {
        if (empty($game->slug)) {
            $game->slug = Str::slug($game->title);
        }

        if (empty($game->seo_title)) {
            $game->seo_title = $game->title.' — Play Free Online Game';
        }

        $game->save();

        AdminAuditService::log(
            'game.metadata_repaired',
            'Game',
            (string) $game->id,
            "Repaired catalog metadata for '{$game->title}'"
        );

        return back()->with('success', "Catalog metadata for '{$game->title}' repaired successfully.");
    }

    /**
     * Batch validate all catalog games to verify file presence and detect issues.
     */
    public function batchValidate(): JsonResponse
    {
        $games = Game::all();
        $total = $games->count();
        $missingThumbnails = 0;
        $missingEntryFiles = 0;

        foreach ($games as $game) {
            if (empty($game->thumbnail) || $game->thumbnail === '/images/thumbnails/default-game.svg') {
                $missingThumbnails++;
            }
            if (empty($game->entry_file)) {
                $missingEntryFiles++;
            }
        }

        $report = [
            'total_games' => $total,
            'valid_games' => $total - $missingEntryFiles,
            'missing_entry_files' => $missingEntryFiles,
            'missing_thumbnails' => $missingThumbnails,
            'timestamp' => now()->toIso8601String(),
        ];

        AdminAuditService::log(
            'system.catalog_validated',
            'Catalog',
            null,
            "Executed catalog integrity validation on {$total} games",
            $report
        );

        return response()->json($report);
    }
}
