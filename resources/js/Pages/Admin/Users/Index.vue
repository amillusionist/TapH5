<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import type { PaginatedData, User } from '@/types';

const props = defineProps<{
    users: PaginatedData<User>;
    filters: {
        search: string;
        role: string;
        status: string;
    };
    roleCounts: Record<string, number>;
    availableRoles: Record<string, string>;
}>();

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');
const status = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(route('admin.users.index'), {
        search: search.value || undefined,
        role: role.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    role.value = '';
    status.value = '';
    applyFilters();
};

// Role Update Modal State
const selectedUser = ref<User | null>(null);
const roleForm = useForm({
    role: 'user',
});

const openRoleModal = (user: User) => {
    selectedUser.value = user;
    roleForm.role = user.role || 'user';
};

const closeRoleModal = () => {
    selectedUser.value = null;
    roleForm.reset();
};

const submitRoleUpdate = () => {
    if (!selectedUser.value) return;
    roleForm.put(route('admin.users.role.update', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => closeRoleModal(),
    });
};

const toggleUserStatus = (user: User) => {
    const action = user.is_active ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${action} user '${user.name}'?`)) return;

    router.post(route('admin.users.status.toggle', user.id), {}, {
        preserveScroll: true,
    });
};

const getRoleBadgeClass = (r?: string) => {
    switch (r) {
        case 'super_admin': return 'bg-purple-500/10 text-purple-400 border-purple-500/30';
        case 'admin': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
        case 'content_manager': return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30';
        case 'analyst': return 'bg-amber-500/10 text-amber-400 border-amber-500/30';
        default: return 'bg-slate-800 text-slate-400 border-slate-700';
    }
};
</script>

<template>
    <AdminLayout title="Users & Access Management">
        <div class="space-y-6">
            <!-- Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Users & Roles Administration
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Control platform administrators, content managers, analysts, user accounts, and security access.
                    </p>
                </div>

                <!-- Counts Badge -->
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300">
                        Total Users: <strong class="text-white">{{ roleCounts.all }}</strong>
                    </span>
                    <span class="px-3 py-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-xs font-bold text-purple-400">
                        Super Admins: <strong>{{ roleCounts.super_admin }}</strong>
                    </span>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px]">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search by name or email..."
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs placeholder:text-slate-500 focus:ring-1 focus:ring-emerald-400"
                    />
                </div>

                <div class="w-44">
                    <select
                        v-model="role"
                        @change="applyFilters"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                    >
                        <option value="">All Roles</option>
                        <option value="super_admin">Super Admin ({{ roleCounts.super_admin }})</option>
                        <option value="admin">Administrator ({{ roleCounts.admin }})</option>
                        <option value="content_manager">Content Manager ({{ roleCounts.content_manager }})</option>
                        <option value="analyst">Analyst ({{ roleCounts.analyst }})</option>
                        <option value="user">User ({{ roleCounts.user }})</option>
                    </select>
                </div>

                <div class="w-36">
                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>

                <button
                    type="button"
                    @click="applyFilters"
                    class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition-colors"
                >
                    Apply
                </button>
                <button
                    v-if="search || role || status"
                    type="button"
                    @click="resetFilters"
                    class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                >
                    Reset
                </button>
            </div>

            <!-- Users Table -->
            <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/70 border-b border-slate-800 text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                            <tr>
                                <th class="p-4">User</th>
                                <th class="p-4">Assigned Role</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Registered</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            <tr
                                v-for="u in users.data"
                                :key="u.id"
                                class="hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="p-4">
                                    <div class="font-bold text-white text-sm">{{ u.name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ u.email }}</div>
                                </td>
                                <td class="p-4">
                                    <span
                                        :class="getRoleBadgeClass(u.role)"
                                        class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider border"
                                    >
                                        {{ u.role || 'user' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span
                                        :class="u.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30'"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
                                    >
                                        {{ u.is_active ? 'Active' : 'Suspended' }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-400">
                                    {{ u.created_at ? new Date(u.created_at).toLocaleDateString() : 'N/A' }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openRoleModal(u)"
                                            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs border border-slate-700 transition-colors"
                                        >
                                            Change Role
                                        </button>
                                        <button
                                            type="button"
                                            @click="toggleUserStatus(u)"
                                            :class="u.is_active ? 'bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border-rose-500/30' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border-emerald-500/30'"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
                                        >
                                            {{ u.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-500">
                                    No users found matching your search or filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users.links.length > 3" class="p-4 border-t border-slate-800 flex justify-center">
                    <Pagination :links="users.links" />
                </div>
            </div>

            <!-- Role Change Modal -->
            <div
                v-if="selectedUser"
                class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="closeRoleModal"
            >
                <div class="w-full max-w-md rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-white">
                            Modify Access: {{ selectedUser.name }}
                        </h3>
                        <button @click="closeRoleModal" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <form @submit.prevent="submitRoleUpdate" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Select Role</label>
                            <select
                                v-model="roleForm.role"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                            >
                                <option v-for="(desc, rKey) in availableRoles" :key="rKey" :value="rKey">
                                    {{ desc }}
                                </option>
                            </select>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400 space-y-1">
                            <p><strong>Note:</strong> Super Administrators have unconstrained access to platform settings and user roles.</p>
                            <p>The system prevents demoting the last active Super Administrator to maintain recovery guarantees.</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <button
                                type="button"
                                @click="closeRoleModal"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="roleForm.processing"
                                class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs"
                            >
                                Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

