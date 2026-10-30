<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import FormSection from '@/Components/Admin/FormSection.vue';

const form = useForm({
    name: '',
    slug: '',
});

const onNameChange = () => {
    if (!form.slug || form.slug === '') {
        form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
};

const submit = () => {
    form.post(route('admin.tags.store'));
};
</script>

<template>
    <AdminLayout title="Create Tag">
        <div class="max-w-xl mx-auto space-y-6">
            <!-- Header -->
            <AdminPageHeader
                title="Create New Tag"
                description="Add a new searchable tag or feature label to the gaming platform."
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Tags', href: route('admin.tags.index') },
                    { label: 'Create' },
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.tags.index')"
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
                        {{ form.processing ? 'Creating...' : 'Create Tag' }}
                    </button>
                </template>
            </AdminPageHeader>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <FormSection
                    title="Tag Details"
                    description="Provide the tag name and system slug identifier."
                >
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Tag Name *</label>
                            <input
                                v-model="form.name"
                                @input="onNameChange"
                                type="text"
                                required
                                placeholder="e.g. Retro"
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
                                placeholder="e.g. retro"
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                            />
                            <p class="mt-1 text-[11px] text-slate-400 font-mono">Identifier: {{ form.slug || '...' }}</p>
                            <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-400">{{ form.errors.slug }}</p>
                        </div>
                    </div>
                </FormSection>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('admin.tags.index')"
                        class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-md shadow-emerald-500/20 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Tag' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
