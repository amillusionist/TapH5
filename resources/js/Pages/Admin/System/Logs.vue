<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import type { AdminAuditLog, PaginatedData } from '@/types';

const props = defineProps<{
    logs: PaginatedData<AdminAuditLog>;
    filters: {
        search: string;
        action: string;
    };
    actionTypes: string[];
}>();

const search = ref(props.filters.search || '');
const action = ref(props.filters.action || '');

const applyFilters = () => {
    router.get(route('admin.system.logs'), {
        search: search.value || undefined,
        action: action.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    action.value = '';
    applyFilters();
};

// Details Modal
const activeLogDetails = ref<AdminAuditLog | null>(null);

const viewDetails = (log: AdminAuditLog) => {
    activeLogDetails.value = log;
};

const closeDetails = () => {
    activeLogDetails.value = null;
};
</script>

<template>
    <AdminLayout title="Administrative Activity Logs">
        <div class="space-y-6">
            <!-- Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Administrative Audit Trail
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Comprehensive immutable record of administrative operations, catalog changes, and access modifications.
                    </p>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px]">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search logs by description, actor email, or resource..."
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs placeholder:text-slate-500 focus:ring-1 focus:ring-emerald-400"
                    />
                </div>

                <div class="w-48">
                    <select
                        v-model="action"
                        @change="applyFilters"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                    >
                        <option value="">All Action Types</option>
                        <option v-for="act in actionTypes" :key="act" :value="act">
                            {{ act }}
                        </option>
                    </select>
                </div>

                <button
                    type="button"
                    @click="applyFilters"
                    class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition-colors"
                >
                    Filter
                </button>

                <button
                    v-if="search || action"
                    type="button"
                    @click="resetFilters"
                    class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                >
                    Reset
                </button>
            </div>

            <!-- Logs Table -->
            <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/70 border-b border-slate-800 text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                            <tr>
                                <th class="p-4">Timestamp</th>
                                <th class="p-4">Actor</th>
                                <th class="p-4">Action</th>
                                <th class="p-4">Resource</th>
                                <th class="p-4">Description</th>
                                <th class="p-4 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="p-4 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-white">{{ log.user_email || 'System / CLI' }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono" v-if="log.ip_address">{{ log.ip_address }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-slate-800 text-emerald-400 border border-slate-700">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-400">
                                    <span v-if="log.resource_type">{{ log.resource_type }} #{{ log.resource_id || '-' }}</span>
                                    <span v-else class="text-slate-600">-</span>
                                </td>
                                <td class="p-4 text-slate-200">
                                    {{ log.description }}
                                </td>
                                <td class="p-4 text-right">
                                    <button
                                        v-if="log.details"
                                        type="button"
                                        @click="viewDetails(log)"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300"
                                    >
                                        Payload
                                    </button>
                                    <span v-else class="text-slate-600">-</span>
                                </td>
                            </tr>

                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    No audit logs found matching criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logs.links.length > 3" class="p-4 border-t border-slate-800 flex justify-center">
                    <Pagination :links="logs.links" />
                </div>
            </div>

            <!-- Payload Details Modal -->
            <div
                v-if="activeLogDetails"
                class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="closeDetails"
            >
                <div class="w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-white">
                            Audit Details: {{ activeLogDetails.action }}
                        </h3>
                        <button @click="closeDetails" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Recorded At:</span>
                            <span class="font-mono text-white">{{ new Date(activeLogDetails.created_at).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Actor:</span>
                            <span class="font-bold text-white">{{ activeLogDetails.user_email || 'System' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Description:</span>
                            <span class="text-slate-200">{{ activeLogDetails.description }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Payload JSON</div>
                        <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] font-mono text-emerald-400 overflow-x-auto max-h-60">{{ JSON.stringify(activeLogDetails.details, null, 2) }}</pre>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="button"
                            @click="closeDetails"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

