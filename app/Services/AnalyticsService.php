<?php

namespace App\Services;

use App\Models\AnalyticsDailyAggregate;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\Game;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class AnalyticsService
{
    /**
     * Permitted analytics event types.
     *
     * @var list<string>
     */
    public const ALLOWED_EVENT_TYPES = [
        'game_viewed',
        'game_play_clicked',
        'game_loaded',
        'game_session_ping',
        'game_session_ended',
    ];

    /**
     * Permitted device types.
     *
     * @var list<string>
     */
    public const ALLOWED_DEVICE_TYPES = [
        'desktop',
        'mobile',
        'tablet',
        'other',
    ];

    /**
     * Record a first-party analytics event with server-side validation and idempotency.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function recordEvent(
        string $eventType,
        int $gameId,
        string $visitorId,
        ?string $sessionId = null,
        string $deviceType = 'desktop',
        int $durationSeconds = 0,
        ?array $metadata = null
    ): ?AnalyticsEvent {
        if (! in_array($eventType, self::ALLOWED_EVENT_TYPES, true)) {
            throw new InvalidArgumentException("Invalid analytics event type: {$eventType}");
        }

        /** @var Game|null $game */
        $game = Game::where('id', $gameId)->published()->first();

        if (! $game) {
            throw new InvalidArgumentException("Game not found or not published: ID {$gameId}");
        }

        $sanitizedDeviceType = in_array($deviceType, self::ALLOWED_DEVICE_TYPES, true) ? $deviceType : 'other';
        $clampedDuration = max(0, min($durationSeconds, 3600));
        $now = Carbon::now();

        // Idempotent play count tracking & spam prevention:
        // When a play is confirmed (game_play_clicked or game_loaded), only increment the public
        // plays_count once per visitor/session per game within a 30-second window.
        if (in_array($eventType, ['game_play_clicked', 'game_loaded'], true)) {
            $throttleKey = "taph5_play_throttle:{$visitorId}:{$gameId}";
            if (Cache::add($throttleKey, true, 30)) {
                $game->timestamps = false;
                $game->increment('plays_count');
                $game->timestamps = true;
            }
        }

        // Record the raw event
        return AnalyticsEvent::create([
            'game_id' => $game->id,
            'event_type' => $eventType,
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'device_type' => $sanitizedDeviceType,
            'duration_seconds' => $clampedDuration,
            'metadata' => $metadata,
            'created_at' => $now,
        ]);
    }

    /**
     * Get approximate live active player counts grouped by game ID.
     * An active session is defined as having sent a heartbeat (game_session_ping or game_loaded)
     * within the last 90 seconds without a subsequent game_session_ended event.
     *
     * @param  array<int>  $gameIds  Optional filter for specific game IDs
     * @return array<int, int> Map of game_id => active_players_count
     */
    public function getActivePlayersCounts(array $gameIds = []): array
    {
        $cutoff = Carbon::now()->subSeconds(90);

        $endedSessionsSubquery = AnalyticsEvent::query()
            ->where('event_type', 'game_session_ended')
            ->where('created_at', '>=', $cutoff)
            ->whereNotNull('session_id');

        if (! empty($gameIds)) {
            $endedSessionsSubquery->whereIn('game_id', $gameIds);
        }

        $query = AnalyticsEvent::query()
            ->whereIn('event_type', ['game_session_ping', 'game_loaded'])
            ->where('created_at', '>=', $cutoff)
            ->whereNotNull('session_id')
            ->whereNotIn('session_id', $endedSessionsSubquery->select('session_id'));

        if (! empty($gameIds)) {
            $query->whereIn('game_id', $gameIds);
        }

        /** @var array<int, int> $results */
        $results = $query
            ->groupBy('game_id')
            ->selectRaw('game_id, COUNT(DISTINCT session_id) as active_count')
            ->pluck('active_count', 'game_id')
            ->map(fn ($count) => (int) $count)
            ->all();

        return $results;
    }

    /**
     * Cached accessor for active player counts across all games (15-second TTL).
     *
     * @return array<int, int>
     */
    public function getCachedActivePlayersCounts(): array
    {
        return Cache::remember('taph5_active_players_counts', 15, function () {
            return $this->getActivePlayersCounts();
        });
    }

    /**
     * Resolve date boundary Carbon instances for a given range string.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function resolveDateRange(string $range): array
    {
        $now = Carbon::now();

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today'],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'yesterday'],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), '7d'],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay(), '90d'],
            'all' => [Carbon::create(2025, 1, 1, 0, 0, 0), $now->copy()->endOfDay(), 'all'],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), '30d'],
        };
    }

    /**
     * Compile comprehensive platform metrics and trends for the Admin Control Panel.
     *
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(string $range = '30d'): array
    {
        [$startDate, $endDate, $resolvedRange] = $this->resolveDateRange($range);

        $eventsQuery = AnalyticsEvent::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Key Metric Counts
        $viewsCount = (clone $eventsQuery)
            ->where('event_type', 'game_viewed')
            ->count();

        $playClicksCount = (clone $eventsQuery)
            ->where('event_type', 'game_play_clicked')
            ->count();

        $gameLoadsCount = (clone $eventsQuery)
            ->where('event_type', 'game_loaded')
            ->count();

        // Effective plays count is determined by play clicks (or loads if no clicks)
        $totalPlays = max($playClicksCount, $gameLoadsCount);

        // Unique players (distinct visitor IDs who initiated gameplay)
        $uniquePlayersCount = (clone $eventsQuery)
            ->whereIn('event_type', ['game_play_clicked', 'game_loaded'])
            ->distinct('visitor_id')
            ->count('visitor_id');

        // Total engagement duration in seconds
        $totalEngagementSeconds = (int) (clone $eventsQuery)
            ->sum('duration_seconds');

        // Conversion Rate: (Play Clicks / Game Views) * 100
        $conversionRate = $viewsCount > 0
            ? round(($playClicksCount / $viewsCount) * 100, 1)
            : 0.0;

        // Average duration per play
        $avgDurationSeconds = $totalPlays > 0
            ? (int) round($totalEngagementSeconds / $totalPlays)
            : 0;

        // Returning players: visitors who played across distinct sessions or days
        $returningPlayersCount = (clone $eventsQuery)
            ->whereIn('event_type', ['game_play_clicked', 'game_loaded'])
            ->select('visitor_id')
            ->groupBy('visitor_id')
            ->havingRaw('COUNT(DISTINCT session_id) > 1')
            ->get()
            ->count();

        // Trend chart series data
        $trend = $this->buildTrendSeries($startDate, $endDate, $resolvedRange);

        // Top Games ranking
        $topGames = $this->getTopGamesPerformance($startDate, $endDate, 10);

        // Category breakdown
        $categoryBreakdown = $this->getCategoryBreakdown($startDate, $endDate);

        // Device breakdown
        $deviceBreakdown = $this->getDeviceBreakdown($startDate, $endDate);

        return [
            'range' => $resolvedRange,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'overview' => [
                'total_views' => $viewsCount,
                'total_play_clicks' => $playClicksCount,
                'total_game_loads' => $gameLoadsCount,
                'total_plays' => $totalPlays,
                'unique_players' => $uniquePlayersCount,
                'returning_players' => $returningPlayersCount,
                'total_engagement_seconds' => $totalEngagementSeconds,
                'total_engagement_formatted' => $this->formatDuration($totalEngagementSeconds),
                'avg_duration_seconds' => $avgDurationSeconds,
                'avg_duration_formatted' => $this->formatDuration($avgDurationSeconds),
                'conversion_rate' => $conversionRate,
            ],
            'trend' => $trend,
            'top_games' => $topGames,
            'category_breakdown' => $categoryBreakdown,
            'device_breakdown' => $deviceBreakdown,
        ];
    }

    /**
     * Build date-grouped trend series for views, plays, and engagement.
     *
     * @return list<array{date: string, label: string, views: int, plays: int, duration_minutes: int}>
     */
    protected function buildTrendSeries(Carbon $startDate, Carbon $endDate, string $range): array
    {
        $trend = [];

        // For today or yesterday, group by 2-hour or 4-hour intervals or hourly
        if (in_array($range, ['today', 'yesterday'], true)) {
            for ($hour = 0; $hour < 24; $hour += 2) {
                $blockStart = $startDate->copy()->addHours($hour);
                $blockEnd = $startDate->copy()->addHours($hour + 2)->subSecond();

                $views = AnalyticsEvent::whereBetween('created_at', [$blockStart, $blockEnd])
                    ->where('event_type', 'game_viewed')
                    ->count();

                $plays = AnalyticsEvent::whereBetween('created_at', [$blockStart, $blockEnd])
                    ->whereIn('event_type', ['game_play_clicked', 'game_loaded'])
                    ->count();

                $duration = (int) AnalyticsEvent::whereBetween('created_at', [$blockStart, $blockEnd])
                    ->sum('duration_seconds');

                $trend[] = [
                    'date' => sprintf('%02d:00', $hour),
                    'label' => sprintf('%02d:00 - %02d:00', $hour, min(24, $hour + 2)),
                    'views' => $views,
                    'plays' => $plays,
                    'duration_minutes' => (int) round($duration / 60),
                ];
            }

            return $trend;
        }

        // For multi-day ranges, iterate day by day
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        foreach ($period as $date) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $views = AnalyticsEvent::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('event_type', 'game_viewed')
                ->count();

            $plays = AnalyticsEvent::whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereIn('event_type', ['game_play_clicked', 'game_loaded'])
                ->count();

            $duration = (int) AnalyticsEvent::whereBetween('created_at', [$dayStart, $dayEnd])
                ->sum('duration_seconds');

            $trend[] = [
                'date' => $date->format('M d'),
                'label' => $date->format('Y-m-d'),
                'views' => $views,
                'plays' => $plays,
                'duration_minutes' => (int) round($duration / 60),
            ];
        }

        return $trend;
    }

    /**
     * Retrieve top performing games with conversion rates and engagement metrics.
     *
     * @return list<array<string, mixed>>
     */
    public function getTopGamesPerformance(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        $games = Game::query()
            ->with('category')
            ->published()
            ->get();

        $performance = [];

        foreach ($games as $game) {
            $gameEvents = AnalyticsEvent::where('game_id', $game->id)
                ->whereBetween('created_at', [$startDate, $endDate]);

            $views = (clone $gameEvents)->where('event_type', 'game_viewed')->count();
            $playClicks = (clone $gameEvents)->where('event_type', 'game_play_clicked')->count();
            $gameLoads = (clone $gameEvents)->where('event_type', 'game_loaded')->count();
            $effectivePlays = max($playClicks, $gameLoads);
            $duration = (int) (clone $gameEvents)->sum('duration_seconds');

            $conversion = $views > 0 ? round(($playClicks / $views) * 100, 1) : 0.0;
            $avgDuration = $effectivePlays > 0 ? (int) round($duration / $effectivePlays) : 0;

            $performance[] = [
                'id' => $game->id,
                'title' => $game->title,
                'slug' => $game->slug,
                'thumbnail' => $game->thumbnail,
                'category' => $game->category?->name ?? 'Uncategorized',
                'category_slug' => $game->category?->slug ?? '',
                'views_count' => $views,
                'play_clicks_count' => $playClicks,
                'game_loads_count' => $gameLoads,
                'plays_count' => $effectivePlays,
                'conversion_rate' => $conversion,
                'total_duration_seconds' => $duration,
                'total_duration_formatted' => $this->formatDuration($duration),
                'avg_duration_seconds' => $avgDuration,
                'avg_duration_formatted' => $this->formatDuration($avgDuration),
            ];
        }

        // Sort by plays desc, then views desc
        usort($performance, function ($a, $b) {
            if ($a['plays_count'] === $b['plays_count']) {
                return $b['views_count'] <=> $a['views_count'];
            }

            return $b['plays_count'] <=> $a['plays_count'];
        });

        return array_slice($performance, 0, $limit);
    }

    /**
     * Compute category share and engagement breakdown.
     *
     * @return list<array<string, mixed>>
     */
    public function getCategoryBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $categories = Category::withCount('games')->get();
        $breakdown = [];
        $grandTotalPlays = 0;

        foreach ($categories as $cat) {
            $catGameIds = Game::where('category_id', $cat->id)->pluck('id')->all();

            if (empty($catGameIds)) {
                continue;
            }

            $catEvents = AnalyticsEvent::whereIn('game_id', $catGameIds)
                ->whereBetween('created_at', [$startDate, $endDate]);

            $views = (clone $catEvents)->where('event_type', 'game_viewed')->count();
            $playClicks = (clone $catEvents)->where('event_type', 'game_play_clicked')->count();
            $gameLoads = (clone $catEvents)->where('event_type', 'game_loaded')->count();
            $plays = max($playClicks, $gameLoads);
            $duration = (int) (clone $catEvents)->sum('duration_seconds');

            $grandTotalPlays += $plays;

            $breakdown[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'games_count' => $cat->games_count,
                'views' => $views,
                'plays' => $plays,
                'duration_seconds' => $duration,
                'duration_formatted' => $this->formatDuration($duration),
            ];
        }

        // Calculate percentage share
        foreach ($breakdown as &$item) {
            $item['share_percentage'] = $grandTotalPlays > 0
                ? round(($item['plays'] / $grandTotalPlays) * 100, 1)
                : 0.0;
        }
        unset($item);

        usort($breakdown, fn ($a, $b) => $b['plays'] <=> $a['plays']);

        return $breakdown;
    }

    /**
     * Compute device distribution.
     *
     * @return array<string, array{count: int, percentage: float}>
     */
    public function getDeviceBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $devices = ['desktop', 'mobile', 'tablet', 'other'];
        $counts = [];
        $total = 0;

        foreach ($devices as $device) {
            $count = AnalyticsEvent::where('device_type', $device)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $counts[$device] = $count;
            $total += $count;
        }

        $result = [];
        foreach ($devices as $device) {
            $result[$device] = [
                'count' => $counts[$device],
                'percentage' => $total > 0 ? round(($counts[$device] / $total) * 100, 1) : 0.0,
            ];
        }

        return $result;
    }

    /**
     * Format seconds into a friendly human-readable duration string.
     */
    public function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Roll up raw events into daily aggregates and prune older events.
     *
     * @return array{aggregated_days: int, deleted_events: int}
     */
    public function aggregateAndPrune(int $retentionDays = 90): array
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays)->startOfDay();
        $distinctDates = AnalyticsEvent::where('created_at', '<', $cutoffDate)
            ->selectRaw('DATE(created_at) as event_date, game_id')
            ->groupBy('event_date', 'game_id')
            ->get();

        $aggregatedDays = 0;

        foreach ($distinctDates as $row) {
            $date = $row->event_date;
            $gameId = (int) $row->game_id;

            $dayStart = Carbon::parse($date)->startOfDay();
            $dayEnd = Carbon::parse($date)->endOfDay();

            $dayEvents = AnalyticsEvent::where('game_id', $gameId)
                ->whereBetween('created_at', [$dayStart, $dayEnd]);

            $views = (clone $dayEvents)->where('event_type', 'game_viewed')->count();
            $playClicks = (clone $dayEvents)->where('event_type', 'game_play_clicked')->count();
            $gameLoads = (clone $dayEvents)->where('event_type', 'game_loaded')->count();
            $uniquePlayers = (clone $dayEvents)->distinct('visitor_id')->count('visitor_id');
            $duration = (int) (clone $dayEvents)->sum('duration_seconds');

            AnalyticsDailyAggregate::updateOrCreate(
                [
                    'date' => $date,
                    'game_id' => $gameId,
                ],
                [
                    'views_count' => $views,
                    'play_clicks_count' => $playClicks,
                    'game_loads_count' => $gameLoads,
                    'unique_players_count' => $uniquePlayers,
                    'total_engagement_seconds' => $duration,
                ]
            );

            $aggregatedDays++;
        }

        $deletedEvents = AnalyticsEvent::where('created_at', '<', $cutoffDate)->delete();

        return [
            'aggregated_days' => $aggregatedDays,
            'deleted_events' => $deletedEvents,
        ];
    }
}
