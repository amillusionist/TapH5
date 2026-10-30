<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
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

// XML Sitemap
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Optional Auth Scaffolding (preserved for future CRM)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
