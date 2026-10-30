<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import type { Category, PaginatedData } from '@/types';

defineProps<{
    categories: PaginatedData<Category>;
}>();

const deleteCategory = (category: Category) => {
    if (!confirm(`Are you sure you want to delete category "${category.name}"? This cannot be undone.`)) {
        return;
    }
    router.delete(route('admin.categories.destroy', category.id));
};
</script>

<template>
    <AdminLayout title="Category Management">
        <div class="space-y-6 max-w-5xl">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-800/80">
                <div>
                    <h2 class="text-xl font-extrabold text-white">Categories</h2>
                    <p class="text-xs text-slate-400 mt-1">Manage portal game taxonomy and navigational categories.</p>
                </div>
                <Link
                    :href="route('admin.categories.create')"
                    class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-1.5 shrink-0"
                >
                    <span>+ New Category</span>
                </Link>
            </div>

            <!-- Categories Table -->
            <div class="rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 border-b border-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="p-4">Name / Slug</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Order</th>
                                <th class="p-4">Games</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="categories.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-500">
                                    No categories found. Create your first category above.
                                </td>
                            </tr>
                            <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-white text-xs">{{ cat.name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">/category/{{ cat.slug }}</div>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                                        :class="cat.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'"
                                    >
                                        {{ cat.status }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-slate-300">
                                    {{ cat.sort_order }}
                                </td>
                                <td class="p-4">
                                    <span class="font-semibold text-white">{{ cat.published_games_count || 0 }}</span>
                                    <span class="text-slate-400 text-[11px]"> ({{ cat.games_count || 0 }} total)</span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="route('admin.categories.edit', cat.id)"
                                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="deleteCategory(cat)"
                                            class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition-colors"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="categories.links && categories.links.length > 3" class="p-4 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Showing {{ categories.from || 0 }} to {{ categories.to || 0 }} of {{ categories.total }}
                    </span>
                    <Pagination :links="categories.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

