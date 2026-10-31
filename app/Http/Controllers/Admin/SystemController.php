<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\AnalyticsEvent;
use App\Models\Game;
use App\Services\Admin\AdminAuditService;
use App\Services\AnalyticsService;
use App\Services\Games\GameScannerService;
use App\Services\PlatformSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    /**
     * Display System Overview, Health status and Maintenance actions.
     */
    public function index(): Response
    {
        // 1. Database Connection check
        $dbStatus = 'healthy';
        $dbDriver = config('database.default', 'sqlite');
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'disconnected';
        }

        // 2. Storage availability check
        $storagePath = storage_path();
        $isStorageWritable = is_writable($storagePath);
        $freeDiskSpace = @disk_free_space($storagePath);
        $totalDiskSpace = @disk_total_space($storagePath);

        $diskUsageFormatted = [
            'free_gb' => $freeDiskSpace ? round($freeDiskSpace / (1024 * 1024 * 1024), 2) : null,
            'total_gb' => $totalDiskSpace ? round($totalDiskSpace / (1024 * 1024 * 1024), 2) : null,
            'is_writable' => $isStorageWritable,
        ];

        // 3. Cache verification
        $cacheStatus = 'healthy';
        try {
            Cache::put('taph5_health_check_ping', 'ok', 10);
            $ping = Cache::get('taph5_health_check_ping');
            if ($ping !== 'ok') {
                $cacheStatus = 'degraded';
            }
        } catch (\Throwable $e) {
            $cacheStatus = 'error';
        }

        // 4. System info summary
        $systemInfo = [
            'app_name' => config('app.name', 'TapH5 Games'),
            'app_env' => config('app.env', 'production'),
            'app_debug' => config('app.debug', false),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'db_driver' => $dbDriver,
            'db_status' => $dbStatus,
            'cache_driver' => config('cache.default', 'file'),
            'cache_status' => $cacheStatus,
            'queue_driver' => config('queue.default', 'database'),
            'storage' => $diskUsageFormatted,
            'total_games' => Game::count(),
            'published_games' => Game::where('status', 'published')->count(),
            'total_audit_events' => AdminAuditLog::count(),
        ];

        // 5. Recent audit logs preview (last 6)
        $recentLogs = AdminAuditLog::recent()->limit(6)->get();

        return Inertia::render('Admin/System/Index', [
            'system' => $systemInfo,
            'recentLogs' => $recentLogs,
        ]);
    }

    /**
     * Clear application caches safely via allowlisted commands.
     */
    public function clearCache(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cache_type' => ['required', 'string', 'in:app,config,route,view,all'],
        ]);

        $type = $validated['cache_type'];

        match ($type) {
            'app' => Artisan::call('cache:clear'),
            'config' => Artisan::call('config:clear'),
            'route' => Artisan::call('route:clear'),
            'view' => Artisan::call('view:clear'),
            'all' => (function () {
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
            })(),
        };

        AdminAuditService::log(
            'system.cache_cleared',
            'Cache',
            $type,
            "Cleared platform cache (type: {$type})"
        );

        return back()->with('success', "Cache '".strtoupper($type)."' cleared successfully.");
    }

    /**
     * Trigger Game Catalog Scanner service safely.
     */
    public function runScanner(GameScannerService $scanner): RedirectResponse
    {
        $result = $scanner->scan();

        $discovered = $result['discovered'] ?? 0;
        $drafts = $result['drafts_created'] ?? 0;
        $rejected = $result['rejected'] ?? 0;

        AdminAuditService::log(
            'system.scanner_run',
            'GameScanner',
            null,
            "Executed Game Scanner (Discovered: {$discovered}, Created: {$drafts}, Rejected: {$rejected})",
            $result
        );

        return back()->with('success', "Scanner completed: {$discovered} discovered, {$drafts} drafts created, {$rejected} rejected.");
    }

    /**
     * Trigger Daily Analytics aggregation.
     */
    public function runAnalyticsAggregation(AnalyticsService $analyticsService): RedirectResponse
    {
        $result = $analyticsService->aggregateAndPrune();
        $days = $result['aggregated_days'] ?? 0;

        AdminAuditService::log(
            'system.analytics_aggregated',
            'Analytics',
            null,
            "Manually triggered analytics aggregation (Aggregated Days: {$days})"
        );

        return back()->with('success', "Analytics aggregation completed ({$days} aggregate days calculated).");
    }

    /**
     * Prune expired analytics events according to retention policy.
     */
    public function pruneAnalytics(PlatformSettingService $settings): RedirectResponse
    {
        $retentionDays = (int) $settings->get('data_retention_days', 90);
        $cutoff = now()->subDays($retentionDays);

        $deletedCount = AnalyticsEvent::where('created_at', '<', $cutoff)->delete();

        AdminAuditService::log(
            'system.analytics_pruned',
            'AnalyticsEvent',
            null,
            "Pruned {$deletedCount} raw analytics events older than {$retentionDays} days",
            ['cutoff' => $cutoff->toIso8601String(), 'deleted' => $deletedCount]
        );

        return back()->with('success', "Pruned {$deletedCount} expired analytics events older than {$retentionDays} days.");
    }

    /**
     * View searchable and filterable Administrative Audit Trail.
     */
    public function logs(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $action = $request->string('action')->trim()->toString();

        $query = AdminAuditLog::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('resource_type', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if ($action !== '') {
            $query->where('action', 'like', "{$action}%");
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $actionTypes = AdminAuditLog::distinct()->pluck('action')->all();

        return Inertia::render('Admin/System/Logs', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'action' => $action,
            ],
            'actionTypes' => $actionTypes,
        ]);
    }
}
