<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Game;
use App\Services\Games\GameOperationsService;
use App\Services\Games\GameReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameReviewController extends Controller
{
    public function __construct(
        protected GameReviewService $reviewService,
        protected GameOperationsService $operationsService,
    ) {}

    /**
     * Display the Game Review Queue with filter tabs and counts.
     */
    public function index(Request $request): Response
    {
        $status = $request->string('status')->trim()->toString();
        $search = $request->string('search')->trim()->toString();
        $categorySlug = $request->string('category')->trim()->toString();

        $query = Game::where('status', 'draft')
            ->with(['category:id,name,slug', 'submittedBy:id,name,email', 'reviewedBy:id,name,email']);

        // Default tab is 'pending' if not specified
        if ($status === '' || $status === 'pending') {
            $query->where('review_status', Game::REVIEW_PENDING);
        } elseif ($status === 'changes_requested') {
            $query->where('review_status', Game::REVIEW_CHANGES_REQUESTED);
        } elseif ($status === 'approved') {
            $query->where('review_status', Game::REVIEW_APPROVED);
        } elseif ($status === 'rejected') {
            $query->where('review_status', Game::REVIEW_REJECTED);
        } elseif ($status === 'all') {
            // All draft games regardless of review status
        } else {
            $query->where('review_status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($categorySlug !== '') {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $games = $query->latest('submitted_at')->paginate(15)->withQueryString();

        $counts = [
            'pending' => Game::where('status', 'draft')->where('review_status', Game::REVIEW_PENDING)->count(),
            'changes_requested' => Game::where('status', 'draft')->where('review_status', Game::REVIEW_CHANGES_REQUESTED)->count(),
            'approved' => Game::where('status', 'draft')->where('review_status', Game::REVIEW_APPROVED)->count(),
            'rejected' => Game::where('status', 'draft')->where('review_status', Game::REVIEW_REJECTED)->count(),
            'all' => Game::where('status', 'draft')->count(),
        ];

        $categories = Category::ordered()->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Reviews/Index', [
            'games' => $games,
            'filters' => [
                'status' => $status ?: 'pending',
                'search' => $search,
                'category' => $categorySlug,
            ],
            'counts' => $counts,
            'categories' => $categories,
            'canPublish' => auth()->user()?->canPublishGames() ?? false,
        ]);
    }

    /**
     * Display a game review inspection and decision screen.
     */
    public function show(Game $game): Response
    {
        $game->load([
            'category:id,name,slug',
            'tags:id,name,slug',
            'submittedBy:id,name,email',
            'reviewedBy:id,name,email',
            'reviews.user:id,name,email',
        ]);

        $validation = $this->operationsService->revalidate($game);
        $duplicates = $this->operationsService->findDuplicates($game);

        return Inertia::render('Admin/Reviews/Show', [
            'game' => $game,
            'validation' => $validation,
            'duplicates' => $duplicates,
            'reviewHistory' => $game->reviews,
            'canPublish' => auth()->user()?->canPublishGames() ?? false,
            'previewUrl' => route('admin.games.serve_staged', ['game' => $game->id, 'path' => $game->entry_file ?: 'index.html']),
        ]);
    }

    /**
     * Submit a game for review.
     */
    public function submit(Request $request, Game $game): RedirectResponse
    {
        $notes = $request->string('notes')->trim()->toString();
        $res = $this->reviewService->submitForReview($game, $request->user(), $notes ?: null);

        if (! $res['success']) {
            return back()->with('error', $res['message']);
        }

        return back()->with('success', $res['message']);
    }

    /**
     * Approve a game in the review queue.
     */
    public function approve(Request $request, Game $game): RedirectResponse
    {
        $notes = $request->string('notes')->trim()->toString();
        $res = $this->reviewService->approve($game, $request->user(), $notes ?: null);

        if (! $res['success']) {
            return back()->with('error', $res['message']);
        }

        return back()->with('success', $res['message']);
    }

    /**
     * Approve and publish a game to the public catalog (Super Admin / Admin).
     */
    public function publish(Request $request, Game $game): RedirectResponse
    {
        if (! $request->user()?->canPublishGames()) {
            abort(403, 'Unauthorized to publish games directly.');
        }

        $notes = $request->string('notes')->trim()->toString();
        $res = $this->reviewService->approveAndPublish($game, $request->user(), $notes ?: null);

        if (! $res['success']) {
            return back()->with('error', $res['message']);
        }

        return redirect()->route('admin.games.show', $game->id)
            ->with('success', $res['message']);
    }

    /**
     * Request changes for a submitted game with review notes.
     */
    public function requestChanges(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $res = $this->reviewService->requestChanges($game, $request->user(), $validated['notes']);

        return back()->with('success', $res['message']);
    }

    /**
     * Reject a submitted game with a documented reason.
     */
    public function reject(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $res = $this->reviewService->reject($game, $request->user(), $validated['reason']);

        return back()->with('success', $res['message']);
    }

    /**
     * Return a game back to draft status.
     */
    public function returnToDraft(Request $request, Game $game): RedirectResponse
    {
        $notes = $request->string('notes')->trim()->toString();
        $res = $this->reviewService->returnToDraft($game, $request->user(), $notes ?: null);

        return back()->with('success', $res['message']);
    }
}
