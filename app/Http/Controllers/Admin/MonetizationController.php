<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ads\AdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonetizationController extends Controller
{
    public function __construct(
        protected AdService $adService
    ) {}

    /**
     * Display the monetization and advertising control panel.
     */
    public function index(): Response
    {
        $overview = $this->adService->getAdminOverview();

        return Inertia::render('Admin/Monetization/Index', [
            'monetization' => $overview,
        ]);
    }

    /**
     * Update advertising provider settings.
     */
    public function updateProvider(Request $request, string $key): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'publisher_id' => ['nullable', 'string', 'max:100'],
            'test_mode' => ['required', 'boolean'],
            'settings' => ['nullable', 'array'],
        ]);

        $provider = $this->adService->updateProvider($key, $validated);

        return redirect()->back()->with('success', "Provider '{$provider->name}' configuration updated successfully.");
    }

    /**
     * Update an individual ad placement.
     */
    public function updatePlacement(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'provider' => ['required', 'string', 'in:adsense,h5_games_ads'],
            'slot_id' => ['nullable', 'string', 'max:100'],
            'placement_type' => ['required', 'string', 'in:display,banner,interstitial,rewarded'],
            'is_enabled' => ['required', 'boolean'],
            'show_on_desktop' => ['required', 'boolean'],
            'show_on_mobile' => ['required', 'boolean'],
            'frequency_limit' => ['required', 'integer', 'min:0', 'max:86400'],
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
            'provider_config' => ['nullable', 'array'],
        ]);

        $placement = $this->adService->updatePlacement($id, $validated);

        return redirect()->back()->with('success', "Placement '{$placement->name}' updated successfully.");
    }
}
