<?php

use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GameAddController as AdminGameAddController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\GameOperationsController as AdminGameOperationsController;
use App\Http\Controllers\Admin\GameReviewController as AdminGameReviewController;
use App\Http\Controllers\Admin\HomepageController as AdminHomepageController;
use App\Http\Controllers\Admin\MonetizationController as AdminMonetizationController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SystemController as AdminSystemController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AnalyticsEventController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Gaming Portal Routes
Route::get('/', HomeController::class)->name('home');

Route::get('/games', [GameController::class, 'index'])->name('games.index');
Route::get('/games/{slug}', [GameController::class, 'show'])->name('games.show');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// Instant AJAX Autocomplete Search
Route::get('/api/games/autocomplete', [GameController::class, 'autocomplete'])
    ->middleware('throttle:180,1')
    ->name('api.games.autocomplete');

// Batch Live Players Count Endpoint
Route::get('/api/games/live-players', [GameController::class, 'livePlayers'])
    ->middleware('throttle:120,1')
    ->name('api.games.live_players');

// Mega Menu Games Endpoint
Route::get('/api/games/mega-menu', [GameController::class, 'megaMenu'])
    ->middleware('throttle:120,1')
    ->name('api.games.mega_menu');

// First-Party Analytics Event Ingestion
Route::prefix('api')->middleware('throttle:60,1')->group(function () {
    Route::post('/analytics/events', [AnalyticsEventController::class, 'store'])->name('api.analytics.events');
});

// Informational & Legal Routes
Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/cookie-policy', [PageController::class, 'cookiePolicy'])->name('pages.cookie-policy');
Route::get('/data-policy', [PageController::class, 'dataPolicy'])->name('pages.data-policy');

// XML Sitemap & Robots
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

// Authenticated User Dashboard (Redirects Admins to Admin Panel)
Route::get('/dashboard', function () {
    if (auth()->user()?->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Control Panel Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Homepage & Catalog Content Mutation (Super Admin, Admin, Content Manager)
    Route::middleware('admin:manage_content')->group(function () {
        Route::get('/homepage', [AdminHomepageController::class, 'index'])->name('homepage.index');
        Route::put('/homepage/sections/{section}', [AdminHomepageController::class, 'updateSection'])->name('homepage.sections.update');
        Route::post('/homepage/sections/reorder', [AdminHomepageController::class, 'reorderSections'])->name('homepage.sections.reorder');
        Route::post('/homepage/slides', [AdminHomepageController::class, 'storeSlide'])->name('homepage.slides.store');
        Route::put('/homepage/slides/{slide}', [AdminHomepageController::class, 'updateSlide'])->name('homepage.slides.update');
        Route::delete('/homepage/slides/{slide}', [AdminHomepageController::class, 'destroySlide'])->name('homepage.slides.destroy');

        // Add Game from Staging Workflow
        Route::get('/games/add', [AdminGameAddController::class, 'index'])->name('games.add');
        Route::post('/games/inspect-staging', [AdminGameAddController::class, 'inspect'])->name('games.inspect_staging');
        Route::post('/games/store-staged', [AdminGameAddController::class, 'store'])->name('games.store_staged');

        Route::post('/games/bulk', [AdminGameController::class, 'bulk'])->name('games.bulk');
        Route::get('/games/batch-validate', [AdminGameController::class, 'batchValidate'])->name('games.batch_validate');
        Route::get('/games/{game:id}/edit', [AdminGameController::class, 'edit'])->name('games.edit');
        Route::put('/games/{game:id}', [AdminGameController::class, 'update'])->name('games.update');
        Route::post('/games/{game:id}/archive', [AdminGameController::class, 'archive'])->name('games.archive');

        // Publishing routes explicitly restricted to Admin & Super Admin
        Route::middleware('admin:publish_games')->group(function () {
            Route::post('/games/{game:id}/publish', [AdminGameController::class, 'publish'])->name('games.publish');
        });

        // Game Maintenance Operations
        Route::post('/games/{game:id}/rescan', [AdminGameOperationsController::class, 'rescan'])->name('games.rescan');
        Route::post('/games/{game:id}/repair-metadata', [AdminGameController::class, 'repairMetadata'])->name('games.repair_metadata');
        Route::match(['get', 'post'], '/games/{game:id}/revalidate', [AdminGameOperationsController::class, 'revalidate'])->name('games.revalidate');
        Route::post('/games/{game:id}/regenerate-thumbnail', [AdminGameOperationsController::class, 'regenerateThumbnail'])->name('games.regenerate_thumbnail');
        Route::post('/games/{game:id}/refresh-metadata', [AdminGameOperationsController::class, 'refreshMetadata'])->name('games.refresh_metadata');
        Route::post('/games/{game:id}/replace-package', [AdminGameOperationsController::class, 'replacePackage'])->name('games.replace_package');

        Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category:id}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category:id}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category:id}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/tags/create', [AdminTagController::class, 'create'])->name('tags.create');
        Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
        Route::get('/tags/{tag:id}/edit', [AdminTagController::class, 'edit'])->name('tags.edit');
        Route::put('/tags/{tag:id}', [AdminTagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag:id}', [AdminTagController::class, 'destroy'])->name('tags.destroy');
    });

    // Content Review Queue (Super Admin, Admin, Content Manager)
    Route::middleware('admin:review_games')->group(function () {
        Route::get('/game-reviews', [AdminGameReviewController::class, 'index'])->name('game_reviews.index');
        Route::get('/game-reviews/{game:id}', [AdminGameReviewController::class, 'show'])->name('game_reviews.show');
        Route::post('/game-reviews/{game:id}/submit', [AdminGameReviewController::class, 'submit'])->name('game_reviews.submit');
        Route::post('/game-reviews/{game:id}/approve', [AdminGameReviewController::class, 'approve'])->name('game_reviews.approve');
        Route::post('/game-reviews/{game:id}/publish', [AdminGameReviewController::class, 'publish'])
            ->middleware('admin:publish_games')
            ->name('game_reviews.publish');
        Route::post('/game-reviews/{game:id}/request-changes', [AdminGameReviewController::class, 'requestChanges'])->name('game_reviews.request_changes');
        Route::post('/game-reviews/{game:id}/reject', [AdminGameReviewController::class, 'reject'])->name('game_reviews.reject');
        Route::post('/game-reviews/{game:id}/return-to-draft', [AdminGameReviewController::class, 'returnToDraft'])->name('game_reviews.return_to_draft');
    });

    // Staged Asset Streaming for Authenticated Admin Preview (Isolated iframe sandbox)
    Route::get('/games/{game:id}/serve-staged/{path?}', [AdminGameOperationsController::class, 'serveStagedAsset'])
        ->where('path', '.*')
        ->name('games.serve_staged');

    // Games Catalog Read-Only for all administrative staff (Analyst, Content Manager, Admin, Super Admin)
    Route::get('/games', [AdminGameController::class, 'index'])->name('games.index');
    Route::get('/games/{game:id}', [AdminGameController::class, 'show'])->name('games.show');
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');

    // Platform Insights: Analytics & Monetization
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])
        ->middleware('admin:view_analytics')
        ->name('analytics.index');

    Route::middleware('admin:manage_monetization')->group(function () {
        Route::get('/monetization', [AdminMonetizationController::class, 'index'])->name('monetization.index');
        Route::put('/monetization/providers/{key}', [AdminMonetizationController::class, 'updateProvider'])->name('monetization.providers.update');
        Route::put('/monetization/placements/{id}', [AdminMonetizationController::class, 'updatePlacement'])->name('monetization.placements.update');
    });

    // Users & Roles Management (Super Admin only)
    Route::get('/users', [AdminUserController::class, 'index'])
        ->middleware('admin:manage_users')
        ->name('users.index');
    Route::put('/users/{user:id}/role', [AdminUserController::class, 'updateRole'])
        ->middleware('admin:manage_users')
        ->name('users.role.update');
    Route::post('/users/{user:id}/toggle-status', [AdminUserController::class, 'toggleStatus'])
        ->middleware('admin:manage_users')
        ->name('users.status.toggle');

    // Platform Settings (Super Admin only)
    Route::get('/settings', [AdminSettingController::class, 'index'])
        ->middleware('admin:manage_settings')
        ->name('settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])
        ->middleware('admin:manage_settings')
        ->name('settings.update');

    // System Health & Maintenance Tools (Super Admin only)
    Route::middleware('admin:super_admin')->group(function () {
        Route::get('/system', [AdminSystemController::class, 'index'])->name('system.index');
        Route::post('/system/maintenance/clear-cache', [AdminSystemController::class, 'clearCache'])->name('system.clear_cache');
        Route::post('/system/maintenance/run-scanner', [AdminSystemController::class, 'runScanner'])->name('system.run_scanner');
        Route::post('/system/maintenance/run-analytics-aggregation', [AdminSystemController::class, 'runAnalyticsAggregation'])->name('system.run_analytics_aggregation');
        Route::post('/system/maintenance/prune-analytics', [AdminSystemController::class, 'pruneAnalytics'])->name('system.prune_analytics');
        Route::get('/system/logs', [AdminSystemController::class, 'logs'])->name('system.logs');
    });
});

require __DIR__.'/auth.php';
