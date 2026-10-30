<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-5">
        <div class="border-b border-slate-800/60 pb-3">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                {{ title }}
            </h3>
            <p v-if="description" class="text-xs text-slate-400 mt-0.5">
                {{ description }}
            </p>
        </div>

        <slot />
    </div>
</template>

