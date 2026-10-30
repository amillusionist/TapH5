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
                <span class="text-slate-200">Privacy Policy</span>
            </nav>

            <div class="p-8 sm:p-12 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6">
                <header class="border-b border-slate-800 pb-6">
                    <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-xs font-bold uppercase tracking-wider">
                        Legal Document
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-black text-white mt-3">Privacy Policy</h1>
                    <p class="text-slate-400 text-xs mt-2">Last updated: October 2026 • Official Platform Policy</p>
                </header>

                <div class="space-y-6 text-slate-300 text-sm leading-relaxed">
                    <section>
                        <h2 class="text-base font-bold text-white mb-2">1. Overview</h2>
                        <p>
                            TapH5 Games ("we", "our", or "platform") respects player privacy. This policy outlines how information is handled when you browse and play games on our website.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">2. Information Collection</h2>
                        <p>
                            We do not require mandatory registration or personal accounts for public game discovery and gameplay. Standard web server logs (including IP addresses, browser types, and referring pages) are collected strictly for security, performance optimization, and operational diagnostics.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">3. Game Sandbox and Isolation</h2>
                        <p>
                            Games embedded on our platform run within sandboxed frames to prevent game code from accessing sensitive session data, cookies, or personal device storage beyond standard in-browser game progress storage.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">4. Third-Party Services</h2>
                        <p>
                            Games may utilize local browser storage (such as HTML5 LocalStorage) solely to preserve high scores, level progression, or game state locally on your machine.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

