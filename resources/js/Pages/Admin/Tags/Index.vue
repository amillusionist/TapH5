<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import Pagination from '@/Components/Pagination.vue';
import type { PaginatedData, Tag } from '@/types';

defineProps<{
    tags: PaginatedData<Tag>;
}>();

const tagToDelete = ref<Tag | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (tag: Tag) => {
    tagToDelete.value = tag;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        tagToDelete.value = null;
    }
};

const confirmDelete = () => {
    if (!tagToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('admin.tags.destroy', tagToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            tagToDelete.value = null;
        },
    });
};
</script>

<template>
    <AdminLayout title="Tags Management">
        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Header -->
            <AdminPageHeader
                title="Tags Manager"
                description="Manage searchable keywords, gameplay labels, and taxonomies."
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Tags' },
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.tags.create')"
                        class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-1.5 shrink-0"
                    >
                        <span>+ New Tag</span>
                    </Link>
                </template>
            </AdminPageHeader>

            <!-- Tags Table (Desktop) -->
            <div class="hidden md:block rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 border-b border-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="p-4">Tag Label</th>
                            <th class="p-4">Slug Identifier</th>
                            <th class="p-4">Associated Games</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <tr v-if="tags.data.length === 0">
                            <td colspan="4" class="p-12 text-center text-slate-500 text-xs">
                                No tags created yet. Click "+ New Tag" to create one.
                            </td>
                        </tr>

                        <tr v-for="tag in tags.data" :key="tag.id" class="hover:bg-slate-900/40 transition-colors">
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-800/80 border border-slate-700/60 text-slate-200 font-semibold text-xs inline-flex items-center gap-1">
                                    <span class="text-emerald-400">#</span>
                                    <span>{{ tag.name }}</span>
                                </span>
                            </td>
                            <td class="p-4 font-mono text-slate-400 text-[11px]">
                                {{ tag.slug }}
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-white">{{ tag.games_count || 0 }}</span>
                                <span class="text-slate-400 text-[11px]"> assigned games</span>
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
                                        @click="openDeleteModal(tag)"
                                        class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition-colors"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination Footer -->
                <div v-if="tags.links && tags.links.length > 3" class="p-4 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Showing {{ tags.from || 0 }} to {{ tags.to || 0 }} of {{ tags.total }} tags
                    </span>
                    <Pagination :links="tags.links" />
                </div>
            </div>

            <!-- Tags Mobile Cards -->
            <div class="md:hidden space-y-3">
                <div v-if="tags.data.length === 0" class="p-8 rounded-2xl bg-[#0a0f1d] border border-slate-800 text-center text-slate-500 text-xs">
                    No tags found.
                </div>

                <div
                    v-for="tag in tags.data"
                    :key="tag.id"
                    class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 font-bold text-xs inline-flex items-center gap-1">
                            <span class="text-emerald-400">#</span>
                            <span>{{ tag.name }}</span>
                        </span>
                        <span class="text-xs text-slate-400">{{ tag.games_count || 0 }} games</span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-mono">Slug: {{ tag.slug }}</p>

                    <div class="pt-2 border-t border-slate-800/60 flex items-center justify-end gap-2">
                        <Link
                            :href="route('admin.tags.edit', tag.id)"
                            class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-200 text-xs font-semibold"
                        >
                            Edit
                        </Link>
                        <button
                            @click="openDeleteModal(tag)"
                            class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/30 text-xs font-semibold"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div v-if="tags.links && tags.links.length > 3" class="pt-2 flex justify-center">
                    <Pagination :links="tags.links" />
                </div>
            </div>
        </div>

        <!-- Confirm Deletion Modal -->
        <ConfirmModal
            :show="tagToDelete !== null"
            title="Delete Tag"
            :message="`Are you sure you want to permanently delete tag '#${tagToDelete?.name}'? The tag will be cleanly detached from all assigned games, but no games will be deleted.`"
            confirm-text="Delete Tag"
            variant="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @close="closeDeleteModal"
        />
    </AdminLayout>
</template>
