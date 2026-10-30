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
        $statsData = Game::selectRaw("
            COUNT(*) as total_games,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_games,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_games,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_games,
            COALESCE(SUM(plays_count), 0) as total_plays
        ")->first();

        $stats = [
            'total_games' => (int) ($statsData->total_games ?? 0),
            'published_games' => (int) ($statsData->published_games ?? 0),
            'draft_games' => (int) ($statsData->draft_games ?? 0),
            'archived_games' => (int) ($statsData->archived_games ?? 0),
            'total_categories' => Category::count(),
            'total_tags' => Tag::count(),
            'total_plays' => (int) ($statsData->total_plays ?? 0),
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
