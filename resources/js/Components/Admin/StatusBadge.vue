<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        status?: string;
        size?: 'sm' | 'md';
        pulse?: boolean;
    }>(),
    {
        status: '',
        size: 'sm',
        pulse: false,
    }
);

const normalizedStatus = computed(() => (props.status || '').toLowerCase().trim());

const statusConfig = computed(() => {
    switch (normalizedStatus.value) {
        case 'published':
            return {
                label: 'Published',
                badgeClasses: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                dotClasses: 'bg-emerald-400',
            };
        case 'draft':
            return {
                label: 'Draft',
                badgeClasses: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                dotClasses: 'bg-amber-400',
            };
        case 'archived':
            return {
                label: 'Archived',
                badgeClasses: 'bg-slate-700/20 text-slate-400 border-slate-700/60',
                dotClasses: 'bg-slate-500',
            };
        case 'active':
            return {
                label: 'Active',
                badgeClasses: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                dotClasses: 'bg-emerald-400',
            };
        case 'inactive':
            return {
                label: 'Inactive',
                badgeClasses: 'bg-slate-800 text-slate-400 border-slate-700/60',
                dotClasses: 'bg-slate-600',
            };
        default:
            return {
                label: props.status,
                badgeClasses: 'bg-slate-800/80 text-slate-300 border-slate-700/60',
                dotClasses: 'bg-slate-400',
            };
    }
});

const sizeClasses = computed(() => {
    return props.size === 'md'
        ? 'px-3 py-1 text-xs gap-1.5'
        : 'px-2.5 py-0.5 text-[10px] gap-1.5';
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full font-bold uppercase tracking-wider border transition-colors select-none"
        :class="[statusConfig.badgeClasses, sizeClasses]"
    >
        <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="[
                statusConfig.dotClasses,
                (pulse || normalizedStatus === 'draft') ? 'animate-pulse' : '',
            ]"
        ></span>
        <span>{{ statusConfig.label }}</span>
    </span>
</template>
