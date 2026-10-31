<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAuditService;
use App\Services\PlatformSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected PlatformSettingService $settingService
    ) {}

    /**
     * Display the Platform Settings editor.
     */
    public function index(): Response
    {
        $groupedSettings = $this->settingService->getGrouped();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $groupedSettings,
        ]);
    }

    /**
     * Update runtime platform settings by group.
     */
    public function update(Request $request): RedirectResponse
    {
        $group = $request->string('group')->toString();

        if (! in_array($group, ['general', 'seo', 'gameplay', 'analytics', 'monetization'], true)) {
            return back()->with('error', 'Invalid settings group specified.');
        }

        $rules = match ($group) {
            'general' => [
                'site_name' => ['required', 'string', 'max:100'],
                'site_description' => ['required', 'string', 'max:500'],
                'contact_email' => ['required', 'email', 'max:255'],
                'default_language' => ['required', 'string', 'max:10'],
                'default_timezone' => ['required', 'string', 'max:50'],
                'social_twitter' => ['nullable', 'url', 'max:255'],
                'social_discord' => ['nullable', 'url', 'max:255'],
                'social_youtube' => ['nullable', 'url', 'max:255'],
            ],
            'seo' => [
                'default_meta_title' => ['required', 'string', 'max:150'],
                'default_meta_description' => ['required', 'string', 'max:300'],
                'og_image' => ['required', 'string', 'max:255'],
                'canonical_url' => ['nullable', 'url', 'max:255'],
                'robots_default' => ['required', 'string', 'max:50'],
            ],
            'gameplay' => [
                'default_games_per_page' => ['required', 'integer', 'min:6', 'max:60'],
                'homepage_game_limit' => ['required', 'integer', 'min:2', 'max:24'],
                'related_games_limit' => ['required', 'integer', 'min:2', 'max:24'],
                'enable_recommendations' => ['required', 'boolean'],
            ],
            'analytics' => [
                'analytics_enabled' => ['required', 'boolean'],
                'data_retention_days' => ['required', 'integer', 'min:7', 'max:365'],
                'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            ],
            'monetization' => [
                'ads_enabled' => ['required', 'boolean'],
                'consent_mode_default' => ['required', 'string', 'in:opt-in,opt-out,strict'],
            ],
        };

        $validated = $request->validate($rules);

        foreach ($validated as $key => $val) {
            $type = is_bool($val) ? 'boolean' : (is_int($val) ? 'integer' : 'string');
            $this->settingService->set($key, $val, $group, $type);
        }

        AdminAuditService::log(
            'settings.updated',
            'PlatformSetting',
            $group,
            "Updated platform settings for group '{$group}'",
            $validated
        );

        return back()->with('success', "Settings for '".ucfirst($group)."' saved successfully.");
    }
}
