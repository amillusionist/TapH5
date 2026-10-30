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
                <span class="text-slate-200">About Us</span>
            </nav>

            <div class="p-8 sm:p-12 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-8">
                <header class="border-b border-slate-800 pb-6">
                    <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold uppercase tracking-wider">
                        About Platform
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-black text-white mt-3">About TapH5 Games</h1>
                    <p class="text-slate-400 text-sm mt-2">The modern online gaming portal engineered for instant play.</p>
                </header>

                <div class="space-y-6 text-slate-300 text-sm leading-relaxed">
                    <section>
                        <h2 class="text-lg font-bold text-white mb-2">Our Mission</h2>
                        <p>
                            TapH5 Games was built to deliver fast, seamless, and high-performance HTML5 games directly to web browsers worldwide. We believe great games should be accessible on any device—mobile phone, tablet, laptop, or desktop—instantly without bulky downloads, app installations, or cumbersome registration barriers.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-bold text-white mb-2">Modern Web Standards & Security</h2>
                        <p>
                            Every game running on the TapH5 platform leverages modern web technologies including HTML5, WebGL, Canvas, and Web Audio. Game runtimes are isolated in sandboxed security containers to safeguard player privacy and maintain uncompromising performance standards.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-bold text-white mb-2">A Growing Library</h2>
                        <p>
                            From fast-paced action and retro arcade titles to deep brain teasers, racers, and sports minigames, our catalog is curated to provide engaging entertainment tailored for short gaming sessions and marathon play alike.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

