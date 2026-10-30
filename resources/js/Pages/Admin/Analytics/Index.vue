<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';

interface OverviewMetrics {
    total_views: number;
    total_play_clicks: number;
    total_game_loads: number;
    total_plays: number;
    unique_players: number;
    returning_players: number;
    total_engagement_seconds: number;
    total_engagement_formatted: string;
    avg_duration_seconds: number;
    avg_duration_formatted: string;
    conversion_rate: number;
}

interface TrendPoint {
    date: string;
    label: string;
    views: number;
    plays: number;
    duration_minutes: number;
}

interface TopGameItem {
    id: number;
    title: string;
    slug: string;
    thumbnail: string | null;
    category: string;
    category_slug: string;
    views_count: number;
    play_clicks_count: number;
    game_loads_count: number;
    plays_count: number;
    conversion_rate: number;
    total_duration_seconds: number;
    total_duration_formatted: string;
    avg_duration_seconds: number;
    avg_duration_formatted: string;
}

interface CategoryBreakdownItem {
    id: number;
    name: string;
    slug: string;
    games_count: number;
    views: number;
    plays: number;
    duration_seconds: number;
    duration_formatted: string;
    share_percentage: number;
}

interface DeviceItem {
    count: number;
    percentage: number;
}

interface AnalyticsData {
    range: string;
    start_date: string;
    end_date: string;
    overview: OverviewMetrics;
    trend: TrendPoint[];
    top_games: TopGameItem[];
    category_breakdown: CategoryBreakdownItem[];
    device_breakdown: Record<string, DeviceItem>;
}

const props = defineProps<{
    analytics: AnalyticsData;
}>();

const ranges = [
    { label: 'Today', value: 'today' },
    { label: 'Yesterday', value: 'yesterday' },
    { label: 'Last 7 Days', value: '7d' },
    { label: 'Last 30 Days', value: '30d' },
    { label: 'Last 90 Days', value: '90d' },
    { label: 'All Time', value: 'all' },
];

const selectRange = (rangeValue: string) => {
    if (rangeValue === props.analytics.range) return;
    router.get(route('admin.analytics.index'), { range: rangeValue }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// SVG Trend chart scaling
const maxTrendValue = computed(() => {
    let max = 5;
    for (const point of props.analytics.trend) {
        if (point.views > max) max = point.views;
        if (point.plays > max) max = point.plays;
    }
    return Math.ceil(max * 1.15);
});

const chartWidth = 900;
const chartHeight = 240;
const paddingX = 40;
const paddingY = 30;

const viewsPolyline = computed(() => {
    const points = props.analytics.trend;
    if (points.length === 0) return '';
    const step = (chartWidth - paddingX * 2) / Math.max(1, points.length - 1);

    return points.map((p, i) => {
        const x = paddingX + i * step;
        const y = chartHeight - paddingY - (p.views / maxTrendValue.value) * (chartHeight - paddingY * 2);
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
});

const playsPolyline = computed(() => {
    const points = props.analytics.trend;
    if (points.length === 0) return '';
    const step = (chartWidth - paddingX * 2) / Math.max(1, points.length - 1);

    return points.map((p, i) => {
        const x = paddingX + i * step;
        const y = chartHeight - paddingY - (p.plays / maxTrendValue.value) * (chartHeight - paddingY * 2);
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
});

const hasAnyActivity = computed(() => {
    return props.analytics.overview.total_views > 0 || props.analytics.overview.total_plays > 0;
});
</script>

<template>
    <AdminLayout title="Player Analytics & Engagement">
        <Head title="Player Analytics & Engagement — TapH5 Admin" />

        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Header with Title & Date Range Filter Pills -->
            <AdminPageHeader
                title="Player Analytics & Engagement"
                description="Privacy-focused, first-party telemetry tracking views, game loads, conversion rates, and session duration."
                :breadcrumbs="[{ label: 'Admin', href: route('admin.dashboard') }, { label: 'Analytics' }]"
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-1.5 p-1 rounded-xl bg-slate-900/90 border border-slate-800">
                        <button
                            v-for="r in ranges"
                            :key="r.value"
                            @click="selectRange(r.value)"
                            type="button"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="analytics.range === r.value
                                ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                        >
                            {{ r.label }}
                        </button>
                    </div>
                </template>
            </AdminPageHeader>

            <!-- Privacy Architecture Notice -->
            <div class="p-4 rounded-xl bg-emerald-950/20 border border-emerald-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-300">
                <div class="flex items-center gap-2.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>
                        <strong class="text-white font-semibold">100% First-Party & Cookieless Tracking:</strong>
                        All telemetry runs through internal APIs with zero third-party scripts, zero IP storage, and GDPR-compliant anonymous identifiers.
                    </span>
                </div>
                <div class="shrink-0 text-slate-400 text-[11px] font-mono">
                    {{ analytics.start_date }} → {{ analytics.end_date }}
                </div>
            </div>

            <!-- Overview KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                <!-- 1. Total Game Views -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span>Total Views</span>
                        <div class="w-7 h-7 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-white tracking-tight">
                        {{ analytics.overview.total_views.toLocaleString() }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Game stage impressions</div>
                </div>

                <!-- 2. Total Play Clicks -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span>Play Clicks</span>
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <polygon points="6 3 20 12 6 21 6 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-400 tracking-tight">
                        {{ analytics.overview.total_play_clicks.toLocaleString() }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Confirmed play attempts</div>
                </div>

                <!-- 3. Conversion Rate -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span>Conversion</span>
                        <div class="w-7 h-7 rounded-lg bg-teal-500/10 text-teal-400 border border-teal-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-white tracking-tight">
                        {{ analytics.overview.conversion_rate }}%
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Views to active plays</div>
                </div>

                <!-- 4. Unique Players -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span>Unique Players</span>
                        <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-white tracking-tight">
                        {{ analytics.overview.unique_players.toLocaleString() }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">
                        {{ analytics.overview.returning_players }} returning player{{ analytics.overview.returning_players === 1 ? '' : 's' }}
                    </div>
                </div>

                <!-- 5. Total Engagement Time -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span>Total Playtime</span>
                        <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                <polyline points="12 6 12 12 16 14" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-amber-300 tracking-tight">
                        {{ analytics.overview.total_engagement_formatted }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Accumulated in-game time</div>
                </div>

                <!-- 6. Average Play Duration -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span>Avg Session</span>
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-white tracking-tight">
                        {{ analytics.overview.avg_duration_formatted }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Active time per player</div>
                </div>
            </div>

            <!-- Trend Activity Chart Section -->
            <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>Engagement Activity Trends</span>
                            <span class="text-xs font-normal text-slate-400">({{ analytics.range }})</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Timeline of page views and active gameplay sessions</p>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-cyan-400"></span>
                            <span class="text-slate-300">Views</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                            <span class="text-slate-300">Plays</span>
                        </div>
                    </div>
                </div>

                <!-- SVG Interactive Chart -->
                <div v-if="hasAnyActivity" class="relative w-full overflow-x-auto">
                    <svg
                        class="w-full h-56 sm:h-64 overflow-visible"
                        :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                        preserveAspectRatio="none"
                    >
                        <!-- Grid Lines -->
                        <line
                            :x1="paddingX"
                            :y1="paddingY"
                            :x2="chartWidth - paddingX"
                            :y2="paddingY"
                            stroke="#1e293b"
                            stroke-dasharray="4"
                        />
                        <line
                            :x1="paddingX"
                            :y1="chartHeight / 2"
                            :x2="chartWidth - paddingX"
                            :y2="chartHeight / 2"
                            stroke="#1e293b"
                            stroke-dasharray="4"
                        />
                        <line
                            :x1="paddingX"
                            :y1="chartHeight - paddingY"
                            :x2="chartWidth - paddingX"
                            :y2="chartHeight - paddingY"
                            stroke="#334155"
                        />

                        <!-- Views Line (Cyan) -->
                        <polyline
                            v-if="viewsPolyline"
                            fill="none"
                            stroke="#06b6d4"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            :points="viewsPolyline"
                        />

                        <!-- Plays Line (Emerald) -->
                        <polyline
                            v-if="playsPolyline"
                            fill="none"
                            stroke="#10b981"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            :points="playsPolyline"
                        />

                        <!-- Points for Plays -->
                        <template v-for="(p, i) in analytics.trend" :key="`pt-${i}`">
                            <circle
                                :cx="paddingX + i * ((chartWidth - paddingX * 2) / Math.max(1, analytics.trend.length - 1))"
                                :cy="chartHeight - paddingY - (p.plays / maxTrendValue) * (chartHeight - paddingY * 2)"
                                r="4"
                                fill="#10b981"
                                class="hover:r-6 transition-all cursor-pointer"
                            >
                                <title>{{ p.label }}: {{ p.plays }} plays, {{ p.views }} views</title>
                            </circle>
                        </template>

                        <!-- X Axis Labels (selected steps) -->
                        <template v-for="(p, i) in analytics.trend" :key="`lbl-${i}`">
                            <text
                                v-if="i % Math.ceil(analytics.trend.length / 8) === 0 || i === analytics.trend.length - 1"
                                :x="paddingX + i * ((chartWidth - paddingX * 2) / Math.max(1, analytics.trend.length - 1))"
                                :y="chartHeight - 6"
                                fill="#64748b"
                                font-size="11"
                                text-anchor="middle"
                            >
                                {{ p.date }}
                            </text>
                        </template>
                    </svg>
                </div>

                <!-- Clean Zero State -->
                <div v-else class="py-12 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 text-slate-500 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-300">No Gameplay Events in this Period</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm">
                        Telemetry updates dynamically when visitors view catalog pages and initiate games. Try selecting another date range.
                    </p>
                </div>
            </div>

            <!-- Two-Column Grid: Top Games & Categories/Devices -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left 2 Cols: Top Games Performance Table -->
                <div class="lg:col-span-2 p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-lg space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-white">Top Games by Engagement</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Ranked by actual play sessions and conversion rate</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">
                            {{ analytics.top_games.length }} games tracked
                        </span>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-800/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="py-3 px-3">Rank & Game</th>
                                    <th class="py-3 px-3 text-right">Views</th>
                                    <th class="py-3 px-3 text-right">Plays</th>
                                    <th class="py-3 px-3 text-right">Conv. %</th>
                                    <th class="py-3 px-3 text-right">Playtime</th>
                                    <th class="py-3 px-3 text-right">Avg Session</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/40 text-xs">
                                <tr
                                    v-for="(game, index) in analytics.top_games"
                                    :key="game.id"
                                    class="hover:bg-slate-900/50 transition-colors group"
                                >
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-mono font-bold text-slate-500 w-4">
                                                #{{ index + 1 }}
                                            </span>
                                            <div class="w-9 h-9 rounded-lg overflow-hidden bg-slate-900 border border-slate-800 shrink-0">
                                                <img
                                                    :src="game.thumbnail || '/images/thumbnails/neon-pulse.svg'"
                                                    :alt="game.title"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <div class="truncate max-w-[180px]">
                                                <Link
                                                    :href="route('admin.games.edit', { game: game.id })"
                                                    class="font-bold text-white group-hover:text-emerald-400 transition-colors truncate block"
                                                >
                                                    {{ game.title }}
                                                </Link>
                                                <span class="text-[10px] text-slate-400">{{ game.category }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-slate-300">
                                        {{ game.views_count.toLocaleString() }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-emerald-400">
                                        {{ game.plays_count.toLocaleString() }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-semibold" :class="game.conversion_rate > 20 ? 'text-teal-400' : 'text-slate-400'">
                                        {{ game.conversion_rate }}%
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-amber-300">
                                        {{ game.total_duration_formatted }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-slate-300">
                                        {{ game.avg_duration_formatted }}
                                    </td>
                                </tr>

                                <tr v-if="analytics.top_games.length === 0">
                                    <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                        No game activity recorded yet in this time window.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Col: Category Share & Devices -->
                <div class="space-y-6">
                    <!-- Category Breakdown -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-lg space-y-4">
                        <div>
                            <h2 class="text-base font-bold text-white">Category Distribution</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Share of total gameplay sessions</p>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="cat in analytics.category_breakdown"
                                :key="cat.id"
                                class="space-y-1.5"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold text-slate-200">{{ cat.name }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-400 font-mono">{{ cat.plays }} plays</span>
                                        <span class="font-bold text-emerald-400 font-mono">{{ cat.share_percentage }}%</span>
                                    </div>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-500"
                                        :style="`width: ${cat.share_percentage}%`"
                                    ></div>
                                </div>
                            </div>

                            <div v-if="analytics.category_breakdown.length === 0" class="py-4 text-center text-xs text-slate-400">
                                No category plays recorded yet.
                            </div>
                        </div>
                    </div>

                    <!-- Device Breakdown -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-lg space-y-4">
                        <div>
                            <h2 class="text-base font-bold text-white">Device Breakdown</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Player hardware distribution</p>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <!-- Desktop -->
                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-center">
                                <div class="w-6 h-6 mx-auto mb-1.5 text-slate-400">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/>
                                        <line x1="8" y1="21" x2="16" y2="21" stroke-width="2"/>
                                        <line x1="12" y1="17" x2="12" y2="21" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="text-base font-black text-white">
                                    {{ analytics.device_breakdown['desktop']?.percentage ?? 0 }}%
                                </div>
                                <div class="text-[10px] uppercase font-bold text-slate-400 mt-0.5">Desktop</div>
                            </div>

                            <!-- Mobile -->
                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-center">
                                <div class="w-6 h-6 mx-auto mb-1.5 text-slate-400">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="5" y="2" width="14" height="20" rx="2" stroke-width="2"/>
                                        <line x1="12" y1="18" x2="12.01" y2="18" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="text-base font-black text-emerald-400">
                                    {{ analytics.device_breakdown['mobile']?.percentage ?? 0 }}%
                                </div>
                                <div class="text-[10px] uppercase font-bold text-slate-400 mt-0.5">Mobile</div>
                            </div>

                            <!-- Tablet -->
                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-center">
                                <div class="w-6 h-6 mx-auto mb-1.5 text-slate-400">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/>
                                        <line x1="12" y1="18" x2="12.01" y2="18" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="text-base font-black text-white">
                                    {{ analytics.device_breakdown['tablet']?.percentage ?? 0 }}%
                                </div>
                                <div class="text-[10px] uppercase font-bold text-slate-400 mt-0.5">Tablet</div>
                            </div>
                        </div>
                    </div>

                    <!-- Automated Data Retention Notice -->
                    <div class="p-4 rounded-xl bg-slate-900/40 border border-slate-800/80 text-xs text-slate-400 space-y-1">
                        <div class="font-bold text-slate-300">Automated Data Retention</div>
                        <p class="text-[11px] leading-relaxed">
                            Raw events are automatically aggregated into daily metrics and pruned after 90 days via <code class="text-emerald-400 bg-slate-950 px-1 py-0.5 rounded">php artisan analytics:prune</code>, protecting hosting storage while preserving lifetime analytics.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

