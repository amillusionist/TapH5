<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import type { PaginatedData, Tag } from '@/types';

defineProps<{
    tags: PaginatedData<Tag>;
}>();

const deleteTag = (tag: Tag) => {
    if (!confirm(`Are you sure you want to delete tag "#${tag.name}"? It will be removed from all assigned games.`)) {
        return;
    }
    router.delete(route('admin.tags.destroy', tag.id));
};
</script>

<template>
    <AdminLayout title="Tags Management">
        <div class="space-y-6 max-w-4xl">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-800/80">
                <div>
                    <h2 class="text-xl font-extrabold text-white">Tags</h2>
                    <p class="text-xs text-slate-400 mt-1">Manage game metadata tags and labels.</p>
                </div>
                <Link
                    :href="route('admin.tags.create')"
                    class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-1.5 shrink-0"
                >
                    <span>+ New Tag</span>
                </Link>
            </div>

            <!-- Tags Table -->
            <div class="rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 border-b border-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="p-4">Tag Name</th>
                                <th class="p-4">Slug</th>
                                <th class="p-4">Associated Games</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="tags.data.length === 0">
                                <td colspan="4" class="p-8 text-center text-slate-500">
                                    No tags created yet. Click "+ New Tag" to create one.
                                </td>
                            </tr>
                            <tr v-for="tag in tags.data" :key="tag.id" class="hover:bg-slate-900/40 transition-colors">
                                <td class="p-4">
                                    <span class="font-bold text-white text-xs">#{{ tag.name }}</span>
                                </td>
                                <td class="p-4 font-mono text-slate-400 text-[11px]">
                                    {{ tag.slug }}
                                </td>
                                <td class="p-4">
                                    <span class="font-semibold text-slate-200">{{ tag.games_count || 0 }}</span>
                                    <span class="text-slate-500 text-[11px]"> games</span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="route('admin.tags.edit', tag.id)"
                                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="deleteTag(tag)"
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
                <div v-if="tags.links && tags.links.length > 3" class="p-4 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Showing {{ tags.from || 0 }} to {{ tags.to || 0 }} of {{ tags.total }}
                    </span>
                    <Pagination :links="tags.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

