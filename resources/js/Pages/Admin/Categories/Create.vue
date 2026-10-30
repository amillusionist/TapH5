<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import FormSection from '@/Components/Admin/FormSection.vue';

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
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <AdminPageHeader
                title="Create New Category"
                description="Define a new gaming classification for catalog organization and portal navigation."
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Categories', href: route('admin.categories.index') },
                    { label: 'Create' },
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.categories.index')"
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-xs font-semibold transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-md shadow-emerald-500/20 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Category' }}
                    </button>
                </template>
            </AdminPageHeader>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <FormSection
                    title="Category Details"
                    description="Enter the display name, custom URL slug, and optional description."
                >
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Category Name *</label>
                            <input
                                v-model="form.name"
                                @input="onNameChange"
                                type="text"
                                required
                                placeholder="e.g. Arcade"
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
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
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                            />
                            <p class="mt-1 text-[11px] text-slate-400 font-mono">Route: /category/{{ form.slug || '...' }}</p>
                            <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-400">{{ form.errors.slug }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Brief description for SEO and category headers..."
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 leading-relaxed"
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
                            <p class="mt-1 text-[11px] text-slate-400">Lower numbers appear first in portal navigation.</p>
                            <p v-if="form.errors.sort_order" class="mt-1 text-xs text-rose-400">{{ form.errors.sort_order }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Icon Identifier</label>
                            <input
                                v-model="form.icon"
                                type="text"
                                placeholder="e.g. Gamepad2, Zap, Rocket"
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
                </FormSection>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('admin.categories.index')"
                        class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-md shadow-emerald-500/20 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Category' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
