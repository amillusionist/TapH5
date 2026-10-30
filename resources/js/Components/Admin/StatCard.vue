<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        helper?: string;
        badge?: string;
        icon?: 'games' | 'published' | 'draft' | 'plays' | 'categories' | 'tags';
        variant?: 'emerald' | 'amber' | 'cyan' | 'teal' | 'indigo' | 'slate';
    }>(),
    {
        variant: 'emerald',
    }
);

const iconClasses = computed(() => {
    switch (props.variant) {
        case 'emerald':
            return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
        case 'amber':
            return 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
        case 'cyan':
            return 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20';
        case 'teal':
            return 'bg-teal-500/10 text-teal-400 border border-teal-500/20';
        case 'indigo':
            return 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20';
        default:
            return 'bg-slate-800 text-slate-300 border border-slate-700';
    }
});
</script>

<template>
    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 hover:border-slate-700/80 shadow-md transition-all group">
        <div class="flex items-center justify-between gap-2 mb-3">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider group-hover:text-slate-300 transition-colors">
                {{ label }}
            </span>
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" :class="iconClasses">
                <!-- Games Icon -->
                <svg v-if="icon === 'games'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <polygon points="6 3 20 12 6 21 6 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <!-- Published Icon -->
                <svg v-else-if="icon === 'published'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <path d="m9 12 2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <!-- Draft Icon -->
                <svg v-else-if="icon === 'draft'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
                    <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <!-- Plays Icon -->
                <svg v-else-if="icon === 'plays'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <polygon points="10 8 16 12 10 16 10 8" fill="currentColor"/>
                </svg>
                <!-- Categories Icon -->
                <svg v-else-if="icon === 'categories'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <!-- Tags Icon -->
                <svg v-else-if="icon === 'tags'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <line x1="4" y1="9" x2="20" y2="9" stroke-width="2" stroke-linecap="round"/>
                    <line x1="4" y1="15" x2="20" y2="15" stroke-width="2" stroke-linecap="round"/>
                    <line x1="10" y1="3" x2="8" y2="21" stroke-width="2" stroke-linecap="round"/>
                    <line x1="16" y1="3" x2="14" y2="21" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <!-- Default Icon -->
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/>
                </svg>
            </div>
        </div>

        <div class="flex items-baseline gap-2">
            <span class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                {{ value }}
            </span>
            <span
                v-if="badge"
                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                :class="iconClasses"
            >
                {{ badge }}
            </span>
        </div>

        <div v-if="helper || $slots.helper" class="mt-2 text-xs text-slate-400">
            <slot name="helper">
                {{ helper }}
            </slot>
        </div>
    </div>
</template>

