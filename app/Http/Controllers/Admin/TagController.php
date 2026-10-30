<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    /**
     * Display a listing of tags with associated game counts.
     */
    public function index(): Response
    {
        $tags = Tag::withCount('games')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Admin/Tags/Index', [
            'tags' => $tags,
        ]);
    }

    /**
     * Show the form for creating a new tag.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Tags/Create');
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tags,slug'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        Tag::create($validated);

        return redirect()->route('admin.tags.index')
            ->with('success', "Tag '#{$validated['name']}' created successfully.");
    }

    /**
     * Show the form for editing an existing tag.
     */
    public function edit(Tag $tag): Response
    {
        return Inertia::render('Admin/Tags/Edit', [
            'tag' => $tag,
        ]);
    }

    /**
     * Update an existing tag.
     */
    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tags,slug,'.$tag->id],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        $tag->update($validated);

        return redirect()->route('admin.tags.index')
            ->with('success', "Tag '#{$tag->name}' updated successfully.");
    }

    /**
     * Safely delete a tag by detaching it from associated games first.
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tagName = $tag->name;
        $tag->games()->detach();
        $tag->delete();

        return redirect()->route('admin.tags.index')
            ->with('success', "Tag '#{$tagName}' deleted successfully.");
    }
}

