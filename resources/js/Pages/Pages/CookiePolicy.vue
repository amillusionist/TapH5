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
                        <h2 class="text-base font-bold text-white mb-2">What Are Cookies & Local Storage?</h2>
                        <p>
                            Cookies and browser storage mechanisms (such as HTML5 LocalStorage) are small data files saved by your browser. They enable web applications to maintain session security, remember user settings, and measure aggregate platform activity.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">Cookie Categories Used on TapH5 Games</h2>
                        <div class="space-y-3 mt-3">
                            <!-- Category 1: Essential -->
                            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                                <div class="font-bold text-white flex items-center justify-between text-xs">
                                    <span>1. Strictly Necessary & Security</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-500/10 text-emerald-400">Always Active</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Required for secure navigation, CSRF forgery protection (<code class="text-emerald-400 font-mono text-[11px]">XSRF-TOKEN</code>), and session state. These cannot be disabled.
                                </p>
                            </div>

                            <!-- Category 2: First-Party Analytics -->
                            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                                <div class="font-bold text-white flex items-center justify-between text-xs">
                                    <span>2. First-Party Analytics & Telemetry</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-500/10 text-cyan-400">Privacy-First</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Used exclusively to compute game views, play counts, and playtime durations using pseudonymized client identifiers (<code class="text-cyan-400 font-mono text-[11px]">taph5_vid</code>, <code class="text-cyan-400 font-mono text-[11px]">taph5_sid</code>). No personal identity or IP addresses are stored or shared.
                                </p>
                            </div>

                            <!-- Category 3: Preferences -->
                            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                                <div class="font-bold text-white flex items-center justify-between text-xs">
                                    <span>3. Preferences & Consent Record</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300">Local Storage</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Saves your privacy choices in versioned local storage (<code class="text-slate-300 font-mono text-[11px]">taph5_ad_consent</code>) so you are not repeatedly prompted on each page visit.
                                </p>
                            </div>

                            <!-- Category 4: Advertising -->
                            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                                <div class="font-bold text-white flex items-center justify-between text-xs">
                                    <span>4. Advertising & Monetization (Third-Party)</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-amber-500/10 text-amber-400">Dormant / Consent-Gated</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Prepared for certified partners (Google AdSense, H5 Games Ads). Currently dormant by default (<code class="text-amber-400 font-mono text-[11px]">ADS_ENABLED=false</code>). When activated, third-party cookies (e.g. <code class="text-slate-300 font-mono text-[11px]">__gads</code>) may be placed only after required consent is secured.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">Google Certified CMP (EEA, UK & Switzerland)</h2>
                        <p>
                            In compliance with European regulatory standards and Google AdSense requirements, users located in the EEA, UK, or Switzerland will be presented with an IAB TCF v2.2 certified Consent Management Platform (Google Privacy & Messaging CMP) before any commercial ad scripts or advertising cookies are initialized.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">Your Consent Choices & Preferences</h2>
                        <p>
                            You have complete control over your advertising cookies. You can adjust your consent settings at any time by selecting the "Cookie & Ad Preferences" button located in the portal footer.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">Managing Cookies via Your Browser</h2>
                        <p>
                            You can also disable, block, or delete cookies via your web browser's built-in privacy settings. Please note that clearing storage may reset saved high scores or local level progression stored by standalone HTML5 games.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

