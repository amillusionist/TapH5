<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { AdminAuditLog } from '@/types';

defineProps<{
    system: {
        app_name: string;
        app_env: string;
        app_debug: boolean;
        laravel_version: string;
        php_version: string;
        db_driver: string;
        db_status: string;
        cache_driver: string;
        cache_status: string;
        queue_driver: string;
        storage: {
            free_gb: number | null;
            total_gb: number | null;
            is_writable: boolean;
        };
        total_games: number;
        published_games: number;
        total_audit_events: number;
    };
    recentLogs: AdminAuditLog[];
}>();

const isClearingCache = ref(false);

const clearCache = (type: string) => {
    isClearingCache.value = true;
    router.post(route('admin.system.clear_cache'), {
        cache_type: type,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isClearingCache.value = false;
        },
    });
};

const runScanner = () => {
    if (!confirm('Run catalog scanner across staging folder?')) return;
    router.post(route('admin.system.run_scanner'), {}, { preserveScroll: true });
};

const runAnalyticsAggregation = () => {
    router.post(route('admin.system.run_analytics_aggregation'), {}, { preserveScroll: true });
};

const pruneAnalytics = () => {
    if (!confirm('Prune raw analytics events older than configured retention period?')) return;
    router.post(route('admin.system.prune_analytics'), {}, { preserveScroll: true });
};
</script>

<template>
    <AdminLayout title="System Health & Maintenance">
        <div class="space-y-6">
            <!-- Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        System Health & Operations
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Operational diagnostics, infrastructure statuses, allowlisted maintenance tools, and audit monitoring.
                    </p>
                </div>

                <Link
                    :href="route('admin.system.logs')"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs border border-slate-700 flex items-center gap-1.5 transition-colors self-start sm:self-auto"
                >
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>View Audit Trail ({{ system.total_audit_events }})</span>
                </Link>
            </div>

            <!-- Health Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Environment & Engine -->
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-lg flex flex-col justify-between">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Environment & Runtime</div>
                    <div class="mt-2 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Laravel Version:</span>
                            <span class="font-mono font-bold text-white">{{ system.laravel_version }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">PHP Version:</span>
                            <span class="font-mono font-bold text-white">{{ system.php_version }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Environment:</span>
                            <span class="font-semibold text-emerald-400 uppercase text-[10px]">{{ system.app_env }}</span>
                        </div>
                    </div>
                </div>

                <!-- Database Status -->
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-lg flex flex-col justify-between">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Database Engine</div>
                    <div class="mt-2 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Connection Status:</span>
                            <span :class="system.db_status === 'healthy' ? 'text-emerald-400' : 'text-rose-400'" class="font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full" :class="system.db_status === 'healthy' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                                {{ system.db_status.toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Driver:</span>
                            <span class="font-mono font-bold text-white uppercase">{{ system.db_driver }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Published Games:</span>
                            <span class="font-bold text-white">{{ system.published_games }} / {{ system.total_games }}</span>
                        </div>
                    </div>
                </div>

                <!-- Cache & Storage Status -->
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-lg flex flex-col justify-between">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cache & Storage</div>
                    <div class="mt-2 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Cache Driver:</span>
                            <span class="font-mono font-bold text-white">{{ system.cache_driver }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Storage Writable:</span>
                            <span :class="system.storage.is_writable ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold'">
                                {{ system.storage.is_writable ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs" v-if="system.storage.free_gb">
                            <span class="text-slate-400">Free Disk Space:</span>
                            <span class="font-mono font-bold text-white">{{ system.storage.free_gb }} GB</span>
                        </div>
                    </div>
                </div>

                <!-- Queue & Operations -->
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-lg flex flex-col justify-between">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Security & Auditing</div>
                    <div class="mt-2 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Audit Logs Count:</span>
                            <span class="font-mono font-bold text-emerald-400">{{ system.total_audit_events }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Debug Mode:</span>
                            <span class="font-semibold" :class="system.app_debug ? 'text-amber-400' : 'text-slate-400'">
                                {{ system.app_debug ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Queue Driver:</span>
                            <span class="font-mono text-white">{{ system.queue_driver }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Tools Card -->
            <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl space-y-6">
                <div>
                    <h2 class="text-base font-bold text-white">Allowlisted Maintenance Tools</h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Safely trigger catalog operations, clear runtime caches, and invoke backend routines without shell access.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Clear Application Cache -->
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 flex flex-col justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-xs text-white">Application Cache</h4>
                            <p class="text-[11px] text-slate-400 mt-1">Flush application payload cache, homepage payloads, and analytics transients.</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="clearCache('app')"
                                :disabled="isClearingCache"
                                class="flex-1 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition-colors"
                            >
                                Clear App Cache
                            </button>
                            <button
                                type="button"
                                @click="clearCache('all')"
                                :disabled="isClearingCache"
                                class="px-3 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition-colors"
                            >
                                Flush All
                            </button>
                        </div>
                    </div>

                    <!-- Clear Views & Routes -->
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 flex flex-col justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-xs text-white">Views & Routes</h4>
                            <p class="text-[11px] text-slate-400 mt-1">Recompile Blade templates and clear route caches after modifications.</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="clearCache('view')"
                                :disabled="isClearingCache"
                                class="flex-1 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition-colors"
                            >
                                Clear Views
                            </button>
                            <button
                                type="button"
                                @click="clearCache('route')"
                                :disabled="isClearingCache"
                                class="flex-1 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition-colors"
                            >
                                Clear Routes
                            </button>
                        </div>
                    </div>

                    <!-- Catalog Scanner Routine -->
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 flex flex-col justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-xs text-white">Game Catalog Scanner</h4>
                            <p class="text-[11px] text-slate-400 mt-1">Scan staging folder for new HTML5 games and import draft records.</p>
                        </div>
                        <button
                            type="button"
                            @click="runScanner"
                            class="w-full py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition-colors"
                        >
                            Execute Scanner
                        </button>
                    </div>

                    <!-- Daily Analytics Aggregation -->
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 flex flex-col justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-xs text-white">Daily Analytics Aggregation</h4>
                            <p class="text-[11px] text-slate-400 mt-1">Aggregate today's raw analytics events into persistent summary tables.</p>
                        </div>
                        <button
                            type="button"
                            @click="runAnalyticsAggregation"
                            class="w-full py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                        >
                            Aggregate Today's Data
                        </button>
                    </div>

                    <!-- Prune Analytics Data -->
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 flex flex-col justify-between gap-3">
                        <div>
                            <h4 class="font-bold text-xs text-white">Prune Analytics Events</h4>
                            <p class="text-[11px] text-slate-400 mt-1">Delete raw event logs older than configured retention period.</p>
                        </div>
                        <button
                            type="button"
                            @click="pruneAnalytics"
                            class="w-full py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-bold transition-colors"
                        >
                            Prune Expired Events
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Audit Card -->
            <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                        Recent Administrative Activity
                    </h3>
                    <Link
                        :href="route('admin.system.logs')"
                        class="text-xs font-semibold text-emerald-400 hover:text-emerald-300"
                    >
                        View Full History &rarr;
                    </Link>
                </div>

                <div class="divide-y divide-slate-800/70">
                    <div
                        v-for="log in recentLogs"
                        :key="log.id"
                        class="p-4 flex items-center justify-between text-xs hover:bg-slate-800/30 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <div>
                                <span class="font-bold text-white">{{ log.description }}</span>
                                <span class="text-slate-400 ml-2">by {{ log.user_email || 'System' }}</span>
                            </div>
                        </div>
                        <div class="text-slate-500 text-[11px] font-mono">
                            {{ new Date(log.created_at).toLocaleString() }}
                        </div>
                    </div>

                    <div v-if="recentLogs.length === 0" class="p-6 text-center text-slate-500 text-xs">
                        No administrative operations recorded yet.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

