<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with game counts.
     */
    public function index(): Response
    {
        $categories = Category::withCount(['games', 'publishedGames'])
            ->ordered()
            ->paginate(15);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$validated['name']}' created successfully.");
    }

    /**
     * Show the form for editing an existing category.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug,'.$category->id],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' updated successfully.");
    }

    /**
     * Delete a category if no games are associated with it.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $gamesCount = $category->games()->count();

        if ($gamesCount > 0) {
            return back()->with('error', "Cannot delete category '{$category->name}' because {$gamesCount} game(s) are assigned to it. Please reassign or update those games first.");
        }

        $categoryName = $category->name;
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$categoryName}' deleted successfully.");
    }
}
