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
                <span class="text-slate-200">Data Policy</span>
            </nav>

            <div class="p-8 sm:p-12 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6">
                <header class="border-b border-slate-800 pb-6">
                    <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-xs font-bold uppercase tracking-wider">
                        Compliance & Data Handling
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-black text-white mt-3">Data Policy</h1>
                    <p class="text-slate-400 text-xs mt-2">Guidelines on data integrity, retention, and player rights</p>
                </header>

                <div class="space-y-6 text-slate-300 text-sm leading-relaxed">
                    <section>
                        <h2 class="text-base font-bold text-white mb-2">1. Data Minimization</h2>
                        <p>
                            We adhere to data minimization principles. We do not collect unnecessary personal metrics, track players across outside websites, or require user identity verification for standard gaming discovery.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">2. Game Data Security</h2>
                        <p>
                            All gameplay sessions are mediated through isolated sandboxes that protect client browser environments from unauthorized data access or external tampering.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">3. Requests & Contact</h2>
                        <p>
                            If you have questions regarding data handling practices or wish to request data verification under applicable privacy frameworks, please submit your inquiry via our Contact page.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

