<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    icon: '',
    thumbnail: '',
    status: 'active',
    sort_order: 0,
});

const onNameChange = () => {
    if (!form.slug || form.slug === '') {
        form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
};

const submit = () => {
    form.post(route('admin.categories.store'));
};
</script>

<template>
    <AdminLayout title="Create Category">
        <div class="max-w-3xl space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3 pb-4 border-b border-slate-800/80">
                <Link
                    :href="route('admin.categories.index')"
                    class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </Link>
                <div>
                    <h2 class="text-xl font-extrabold text-white">Create New Category</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Define a new game category for the portal.</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category Name *</label>
                        <input
                            v-model="form.name"
                            @input="onNameChange"
                            type="text"
                            required
                            placeholder="e.g. Arcade"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Slug *</label>
                        <input
                            v-model="form.slug"
                            type="text"
                            required
                            placeholder="e.g. arcade"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                        <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-400">{{ form.errors.slug }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Brief description for SEO and category headers"
                        class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                    ></textarea>
                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-400">{{ form.errors.description }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Status *</label>
                        <select
                            v-model="form.status"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <p v-if="form.errors.status" class="mt-1 text-xs text-rose-400">{{ form.errors.status }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Sort Order *</label>
                        <input
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                            required
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                        <p v-if="form.errors.sort_order" class="mt-1 text-xs text-rose-400">{{ form.errors.sort_order }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Icon Identifier</label>
                        <input
                            v-model="form.icon"
                            type="text"
                            placeholder="e.g. Gamepad2, Zap"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Thumbnail Image Path</label>
                        <input
                            v-model="form.thumbnail"
                            type="text"
                            placeholder="e.g. /images/categories/arcade.jpg"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800/80">
                    <Link
                        :href="route('admin.categories.index')"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-md shadow-emerald-500/20 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Category' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

