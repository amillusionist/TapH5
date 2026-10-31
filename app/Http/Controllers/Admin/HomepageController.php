<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\HomepageSection;
use App\Models\HomepageSlide;
use App\Services\Admin\AdminAuditService;
use App\Services\HomepageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomepageController extends Controller
{
    public function __construct(
        protected HomepageContentService $homepageService
    ) {}

    /**
     * Display the Homepage Content Manager.
     */
    public function index(): Response
    {
        $sections = $this->homepageService->getSections();
        $slides = $this->homepageService->getSlides();

        $publishedGames = Game::published()
            ->with('category:id,name,slug')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'thumbnail', 'category_id', 'is_featured', 'is_trending', 'is_popular']);

        return Inertia::render('Admin/Homepage/Index', [
            'sections' => $sections,
            'slides' => $slides,
            'availableGames' => $publishedGames,
        ]);
    }

    /**
     * Update an individual homepage section configuration.
     */
    public function updateSection(Request $request, HomepageSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'is_enabled' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'items_limit' => ['required', 'integer', 'min:0', 'max:30'],
            'curated_game_ids' => ['nullable', 'array'],
            'curated_game_ids.*' => ['exists:games,id'],
        ]);

        $oldData = $section->only(['title', 'is_enabled', 'sort_order', 'items_limit', 'curated_game_ids']);

        $section->update($validated);

        $this->homepageService->clearCache();

        AdminAuditService::log(
            'homepage.section_updated',
            'HomepageSection',
            (string) $section->id,
            "Updated homepage section '{$section->title}' ({$section->section_key})",
            ['old' => $oldData, 'new' => $validated]
        );

        return back()->with('success', "Homepage section '{$section->title}' updated successfully.");
    }

    /**
     * Batch reorder sections.
     */
    public function reorderSections(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['exists:homepage_sections,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            HomepageSection::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        $this->homepageService->clearCache();

        AdminAuditService::log(
            'homepage.sections_reordered',
            'HomepageSection',
            null,
            'Reordered homepage content sections',
            ['order' => $validated['ordered_ids']]
        );

        return back()->with('success', 'Homepage sections reordered successfully.');
    }

    /**
     * Store a new custom hero slide.
     */
    public function storeSlide(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:50'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'background_image' => ['nullable', 'string', 'max:500'],
            'game_id' => ['nullable', 'exists:games,id'],
            'is_enabled' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $maxOrder = (int) HomepageSlide::max('sort_order');
        $validated['sort_order'] = $validated['sort_order'] ?? ($maxOrder + 1);
        $validated['is_enabled'] = $validated['is_enabled'] ?? true;
        $validated['cta_text'] = ! empty($validated['cta_text']) ? $validated['cta_text'] : 'Play Now';

        $slide = HomepageSlide::create($validated);

        $this->homepageService->clearCache();

        AdminAuditService::log(
            'homepage.slide_created',
            'HomepageSlide',
            (string) $slide->id,
            "Created new hero slide '{$slide->title}'",
            $validated
        );

        return back()->with('success', "Hero slide '{$slide->title}' created successfully.");
    }

    /**
     * Update an existing custom hero slide.
     */
    public function updateSlide(Request $request, HomepageSlide $slide): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:50'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'background_image' => ['nullable', 'string', 'max:500'],
            'game_id' => ['nullable', 'exists:games,id'],
            'is_enabled' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $slide->update($validated);

        $this->homepageService->clearCache();

        AdminAuditService::log(
            'homepage.slide_updated',
            'HomepageSlide',
            (string) $slide->id,
            "Updated hero slide '{$slide->title}'",
            $validated
        );

        return back()->with('success', "Hero slide '{$slide->title}' updated successfully.");
    }

    /**
     * Delete a custom hero slide.
     */
    public function destroySlide(HomepageSlide $slide): RedirectResponse
    {
        $title = $slide->title;
        $id = $slide->id;

        $slide->delete();

        $this->homepageService->clearCache();

        AdminAuditService::log(
            'homepage.slide_deleted',
            'HomepageSlide',
            (string) $id,
            "Deleted hero slide '{$title}'"
        );

        return back()->with('success', "Hero slide '{$title}' removed successfully.");
    }
}
