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
                <span class="text-slate-200">Terms and Conditions</span>
            </nav>

            <div class="p-8 sm:p-12 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6">
                <header class="border-b border-slate-800 pb-6">
                    <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-xs font-bold uppercase tracking-wider">
                        Legal Document
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-black text-white mt-3">Terms and Conditions</h1>
                    <p class="text-slate-400 text-xs mt-2">Effective Date: October 2026</p>
                </header>

                <div class="space-y-6 text-slate-300 text-sm leading-relaxed">
                    <section>
                        <h2 class="text-base font-bold text-white mb-2">1. Acceptance of Terms</h2>
                        <p>
                            By accessing or using TapH5 Games, you agree to be bound by these Terms and Conditions. If you do not agree with any portion of these terms, please discontinue using the service.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">2. Platform Access and Use</h2>
                        <p>
                            TapH5 Games provides access to free browser games for personal, non-commercial entertainment. You agree not to attempt to reverse engineer, disrupt, or introduce malicious scripts or unauthorized scraping activities against the platform infrastructure.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">3. Intellectual Property</h2>
                        <p>
                            All platform designs, branding, logos, and custom code are proprietary to TapH5 Games. Games displayed retain their respective author and license copyrights.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

