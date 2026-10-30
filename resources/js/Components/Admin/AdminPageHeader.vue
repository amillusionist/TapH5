<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    }
);
</script>

<template>
    <div class="space-y-2 pb-5 border-b border-slate-800/80">
        <!-- Breadcrumbs (if provided) -->
        <nav v-if="breadcrumbs.length > 0" class="flex items-center gap-1.5 text-xs text-slate-400 font-medium overflow-x-auto whitespace-nowrap">
            <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.label">
                <Link
                    v-if="crumb.href && idx < breadcrumbs.length - 1"
                    :href="crumb.href"
                    class="hover:text-slate-200 transition-colors"
                >
                    {{ crumb.label }}
                </Link>
                <span v-else-if="idx < breadcrumbs.length - 1" class="text-slate-400">
                    {{ crumb.label }}
                </span>
                <span v-else class="text-slate-200 font-semibold truncate">
                    {{ crumb.label }}
                </span>

                <svg
                    v-if="idx < breadcrumbs.length - 1"
                    class="w-3.5 h-3.5 text-slate-600 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </template>
        </nav>

        <!-- Main Title & Action Row -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-3">
                    <span class="truncate">{{ title }}</span>
                    <slot name="badge" />
                </h1>
                <p v-if="description" class="text-xs sm:text-sm text-slate-400 mt-1">
                    {{ description }}
                </p>
            </div>

            <!-- Action Buttons Slot -->
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2.5 shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>

