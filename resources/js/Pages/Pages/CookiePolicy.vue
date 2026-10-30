<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import type { SiteMeta } from '@/types';

defineProps<{
    meta: SiteMeta;
}>();
</script>

<template>
    <SeoHead :meta="meta" />

    <AppLayout>
        <div class="max-w-4xl mx-auto py-8">
            <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6">
                <Link href="/" class="hover:text-emerald-400">Home</Link>
                <span>/</span>
                <span class="text-slate-200">Cookie Policy</span>
            </nav>

            <div class="p-8 sm:p-12 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6">
                <header class="border-b border-slate-800 pb-6">
                    <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-xs font-bold uppercase tracking-wider">
                        Platform Policy
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-black text-white mt-3">Cookie Policy</h1>
                    <p class="text-slate-400 text-xs mt-2">Information regarding browser cookies and client-side storage</p>
                </header>

                <div class="space-y-6 text-slate-300 text-sm leading-relaxed">
                    <section>
                        <h2 class="text-base font-bold text-white mb-2">What Are Cookies?</h2>
                        <p>
                            Cookies and local storage mechanisms are small text items stored by your web browser that enable websites to remember settings, session integrity, and user preferences across visits.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">How TapH5 Games Uses Cookies</h2>
                        <p>
                            TapH5 Games uses essential session tokens and CSRF protection cookies required for core platform navigation and security. Individual HTML5 games may utilize browser LocalStorage or IndexedDB solely to remember game progress and locally saved preferences.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">Managing Cookies</h2>
                        <p>
                            You can choose to disable or clear cookies through your browser settings at any time. Note that doing so may reset locally saved game progress or high score leaderboards in offline-capable games.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

