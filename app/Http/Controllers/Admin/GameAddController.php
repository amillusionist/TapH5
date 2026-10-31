<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Game;
use App\Models\GameReview;
use App\Models\Tag;
use App\Services\Admin\AdminAuditService;
use App\Services\Games\GamePublisherService;
use App\Services\Games\GameScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GameAddController extends Controller
{
    public function __construct(
        protected GameScannerService $scannerService,
        protected GamePublisherService $publisherService,
    ) {}

    /**
     * Show the Add Game multi-step workflow.
     */
    public function index(): Response
    {
        $stagingFolders = $this->scannerService->listStagingFolders();
        $categories = Category::ordered()->get(['id', 'name', 'slug']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Games/Add', [
            'stagingFolders' => $stagingFolders,
            'categories' => $categories,
            'tags' => $tags,
            'canPublish' => auth()->user()?->canPublishGames() ?? false,
        ]);
    }

    /**
     * Inspect a staging folder asynchronously (Validation, Extracted Metadata, Duplicate Check).
     */
    public function inspect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
        ]);

        $folder = basename(trim($validated['folder']));
        $inspection = $this->scannerService->inspectStagingFolder($folder);

        return response()->json($inspection);
    }

    /**
     * Store the game record from the Add Game workflow.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['exists:tags,id'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'action_type' => ['required', 'string', 'in:draft,submit_for_review,publish'],
        ]);

        $folder = basename(trim($validated['folder']));
        $inspection = $this->scannerService->inspectStagingFolder($folder);

        if (! $inspection['is_valid']) {
            return back()->withErrors([
                'folder' => 'Cannot import game: '.implode('; ', $inspection['errors'] ?? ['Validation failed.']),
            ]);
        }

        $user = $request->user();
        $actionType = $validated['action_type'];

        // If publish requested, verify permission
        if ($actionType === 'publish' && ! $user->canPublishGames()) {
            abort(403, 'Unauthorized to publish games directly.');
        }

        $finalSlug = Str::slug($validated['slug']);
        // Check unique slug among other existing games
        $existingWithSlug = Game::where('slug', $finalSlug)
            ->where('storage_path', '!=', "staging/{$folder}")
            ->first();

        if ($existingWithSlug) {
            $finalSlug = "{$finalSlug}-".Str::random(5);
        }

        $reviewStatus = match ($actionType) {
            'submit_for_review' => Game::REVIEW_PENDING,
            'publish' => Game::REVIEW_APPROVED,
            default => Game::REVIEW_NOT_SUBMITTED,
        };

        $scanMeta = [
            'folder' => $folder,
            'entry_file' => $inspection['entry_file'],
            'game_type' => $inspection['game_type'],
            'file_count' => $inspection['file_count'],
            'total_size_bytes' => $inspection['total_size_bytes'],
            'fingerprint' => $inspection['fingerprint'],
            'added_at' => now()->toIso8601String(),
            'warnings' => $inspection['warnings'] ?? [],
        ];

        /** @var Game $game */
        $game = DB::transaction(function () use ($folder, $validated, $finalSlug, $inspection, $reviewStatus, $user, $actionType, $scanMeta) {
            $game = Game::updateOrCreate(
                ['storage_path' => "staging/{$folder}"],
                [
                    'title' => $validated['title'],
                    'slug' => $finalSlug,
                    'description' => $validated['description'] ?? null,
                    'instructions' => $validated['instructions'] ?? null,
                    'category_id' => $validated['category_id'] ?? $inspection['suggested_category_id'] ?? null,
                    'thumbnail' => ($validated['thumbnail'] ?? null) ?: ($inspection['thumbnail_url'] ?? null),
                    'game_type' => $inspection['game_type'],
                    'entry_file' => $inspection['entry_file'],
                    'fingerprint' => $inspection['fingerprint'],
                    'status' => 'draft',
                    'review_status' => $reviewStatus,
                    'submitted_at' => $actionType === 'submit_for_review' ? now() : null,
                    'submitted_by' => $actionType === 'submit_for_review' ? $user->id : null,
                    'reviewed_at' => $actionType === 'publish' ? now() : null,
                    'reviewed_by' => $actionType === 'publish' ? $user->id : null,
                    'scan_metadata' => $scanMeta,
                ]
            );

            if (! empty($validated['tag_ids'])) {
                $game->tags()->sync($validated['tag_ids']);
            }

            // Log review action
            $reviewAction = match ($actionType) {
                'submit_for_review' => GameReview::ACTION_SUBMITTED,
                'publish' => GameReview::ACTION_APPROVED,
                default => 'created_draft',
            };

            GameReview::create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'action' => $reviewAction,
                'from_status' => null,
                'to_status' => $reviewStatus,
                'notes' => "Added game from staging folder '{$folder}'.",
                'metadata' => [
                    'folder' => $folder,
                    'action_type' => $actionType,
                ],
            ]);

            return $game;
        });

        AdminAuditService::log(
            'game.added_from_staging',
            'Game',
            (string) $game->id,
            "Added game '{$game->title}' from staging folder '{$folder}' ({$actionType})",
            ['action_type' => $actionType, 'review_status' => $reviewStatus]
        );

        // If publish requested, proceed to publishing pipeline
        if ($actionType === 'publish') {
            $pubRes = $this->publisherService->approveAndPublish($game->id);
            if ($pubRes['success']) {
                GameReview::create([
                    'game_id' => $game->id,
                    'user_id' => $user->id,
                    'action' => GameReview::ACTION_PUBLISHED,
                    'from_status' => Game::REVIEW_APPROVED,
                    'to_status' => 'published',
                    'notes' => 'Published game immediately upon import.',
                ]);

                return redirect()->route('admin.games.show', $game->id)
                    ->with('success', "Game '{$game->title}' imported and published successfully!");
            }

            return redirect()->route('admin.games.show', $game->id)
                ->with('warning', "Game imported as draft, but publishing failed: {$pubRes['message']}");
        }

        if ($actionType === 'submit_for_review') {
            return redirect()->route('admin.game_reviews.index')
                ->with('success', "Game '{$game->title}' imported and submitted for content review.");
        }

        return redirect()->route('admin.games.show', $game->id)
            ->with('success', "Game '{$game->title}' saved as draft.");
    }
}
