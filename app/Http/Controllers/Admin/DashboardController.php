<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the Admin Control Panel dashboard with actual database metrics.
     */
    public function index(Request $request): Response
    {
        $stats = [
            'total_games' => Game::count(),
            'published_games' => Game::where('status', 'published')->count(),
            'draft_games' => Game::where('status', 'draft')->count(),
            'archived_games' => Game::where('status', 'archived')->count(),
            'total_categories' => Category::count(),
            'total_tags' => Tag::count(),
            'total_plays' => (int) Game::sum('plays_count'),
        ];

        $recentGames = Game::with('category')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentUpdatedGames = Game::with('category')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $pendingReviewGames = Game::with('category')
            ->where('status', 'draft')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentGames' => $recentGames,
            'recentUpdatedGames' => $recentUpdatedGames,
            'pendingReviewGames' => $pendingReviewGames,
        ]);
    }
}

