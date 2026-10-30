<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import Pagination from '@/Components/Pagination.vue';
import type { Category, PaginatedData } from '@/types';

defineProps<{
    categories: PaginatedData<Category>;
}>();

const categoryToDelete = ref<Category | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (category: Category) => {
    categoryToDelete.value = category;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        categoryToDelete.value = null;
    }
};

const confirmDelete = () => {
    if (!categoryToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('admin.categories.destroy', categoryToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            categoryToDelete.value = null;
        },
    });
};
</script>

<template>
    <AdminLayout title="Category Management">
        <div class="space-y-6 max-w-6xl mx-auto">
            <!-- Header -->
            <AdminPageHeader
                title="Categories Manager"
                description="Manage portal game classifications, navigation categories, and display orders."
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Categories' },
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.categories.create')"
                        class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-1.5 shrink-0"
                    >
                        <span>+ New Category</span>
                    </Link>
                </template>
            </AdminPageHeader>

            <!-- Categories Table (Desktop) -->
            <div class="hidden md:block rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 border-b border-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="p-4">Category Name</th>
                            <th class="p-4">Slug & Route</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Sort Order</th>
                            <th class="p-4">Cataloged Games</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <tr v-if="categories.data.length === 0">
                            <td colspan="6" class="p-12 text-center text-slate-500 text-xs">
                                No categories found. Create your first category above.
                            </td>
                        </tr>

                        <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-slate-900/40 transition-colors">
                            <td class="p-4 font-bold text-white text-xs">
                                {{ cat.name }}
                            </td>
                            <td class="p-4 font-mono text-[11px] text-slate-400">
                                /category/{{ cat.slug }}
                            </td>
                            <td class="p-4">
                                <StatusBadge :status="cat.status" />
                            </td>
                            <td class="p-4 font-mono text-slate-300">
                                #{{ cat.sort_order }}
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-white">{{ cat.published_games_count || 0 }}</span>
                                <span class="text-slate-400 text-[11px]"> published</span>
                                <span class="text-slate-400 text-[10px]"> ({{ cat.games_count || 0 }} total)</span>
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
                                        @click="openDeleteModal(cat)"
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
                <div v-if="categories.links && categories.links.length > 3" class="p-4 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Showing {{ categories.from || 0 }} to {{ categories.to || 0 }} of {{ categories.total }} categories
                    </span>
                    <Pagination :links="categories.links" />
                </div>
            </div>

            <!-- Categories Mobile Cards -->
            <div class="md:hidden space-y-3">
                <div v-if="categories.data.length === 0" class="p-8 rounded-2xl bg-[#0a0f1d] border border-slate-800 text-center text-slate-500 text-xs">
                    No categories found.
                </div>

                <div
                    v-for="cat in categories.data"
                    :key="cat.id"
                    class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="font-bold text-white text-sm">{{ cat.name }}</h4>
                        <StatusBadge :status="cat.status" size="sm" />
                    </div>
                    <p class="text-[11px] text-slate-400 font-mono">/category/{{ cat.slug }}</p>
                    <div class="text-xs text-slate-400 flex items-center justify-between">
                        <span>Order #{{ cat.sort_order }}</span>
                        <span>{{ cat.published_games_count || 0 }} published games</span>
                    </div>

                    <div class="pt-2 border-t border-slate-800/60 flex items-center justify-end gap-2">
                        <Link
                            :href="route('admin.categories.edit', cat.id)"
                            class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-200 text-xs font-semibold"
                        >
                            Edit
                        </Link>
                        <button
                            @click="openDeleteModal(cat)"
                            class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/30 text-xs font-semibold"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div v-if="categories.links && categories.links.length > 3" class="pt-2 flex justify-center">
                    <Pagination :links="categories.links" />
                </div>
            </div>
        </div>

        <!-- Confirm Deletion Modal -->
        <ConfirmModal
            :show="categoryToDelete !== null"
            title="Delete Category"
            :message="`Are you sure you want to permanently delete category '${categoryToDelete?.name}'? Games assigned to this category will have their category unassigned, but will NOT be deleted.`"
            confirm-text="Delete Category"
            variant="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @close="closeDeleteModal"
        />
    </AdminLayout>
</template>
