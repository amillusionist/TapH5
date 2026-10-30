<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Display the analytics and engagement dashboard.
     */
    public function index(Request $request): Response
    {
        $range = $request->query('range', '30d');
        if (! in_array($range, ['today', 'yesterday', '7d', '30d', '90d', 'all'], true)) {
            $range = '30d';
        }

        $analytics = $this->analyticsService->getDashboardMetrics($range);

        return Inertia::render('Admin/Analytics/Index', [
            'analytics' => $analytics,
        ]);
    }
}
