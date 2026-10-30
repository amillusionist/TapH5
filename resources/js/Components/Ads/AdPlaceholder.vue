<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        placementKey: string;
        providerName: string;
        dimensions?: string;
        type?: string;
    }>(),
    {
        dimensions: 'Responsive (728x90 / 300x250)',
        type: 'Display Ad',
    }
);
</script>

<template>
    <div class="w-full my-4 p-4 rounded-xl border border-dashed border-slate-700/80 bg-slate-900/40 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left select-none">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="2" y="4" width="20" height="16" rx="2" stroke-width="2"/>
                    <line x1="2" y1="10" x2="22" y2="10" stroke-width="2"/>
                    <line x1="6" y1="15" x2="10" y2="15" stroke-width="2"/>
                </svg>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-200">
                    {{ title }} <span class="font-mono text-slate-400">({{ placementKey }})</span>
                </div>
                <div class="text-[11px] text-slate-400">
                    Provider: <span class="text-emerald-400 font-semibold">{{ providerName }}</span> • {{ type }}
                </div>
            </div>
        </div>

        <div class="px-2.5 py-1 rounded-md bg-slate-800/80 text-[10px] font-mono text-slate-300 border border-slate-700/60">
            {{ dimensions }}
        </div>
    </div>
</template>

