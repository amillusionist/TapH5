<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
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

    // Games Management
    Route::get('/games', [AdminGameController::class, 'index'])->name('games.index');
    Route::get('/games/{game:id}', [AdminGameController::class, 'show'])->name('games.show');
    Route::get('/games/{game:id}/edit', [AdminGameController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game:id}', [AdminGameController::class, 'update'])->name('games.update');
    Route::post('/games/{game:id}/publish', [AdminGameController::class, 'publish'])->name('games.publish');
    Route::post('/games/{game:id}/archive', [AdminGameController::class, 'archive'])->name('games.archive');

    // Category Management
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category:id}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category:id}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category:id}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // Tag Management
    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::get('/tags/create', [AdminTagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{tag:id}/edit', [AdminTagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{tag:id}', [AdminTagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag:id}', [AdminTagController::class, 'destroy'])->name('tags.destroy');
});

require __DIR__.'/auth.php';
