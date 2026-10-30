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
                        <h2 class="text-base font-bold text-white mb-2">1. Overview & Operating Status</h2>
                        <p>
                            TapH5 Games ("we", "our", or "platform") respects player privacy and data protection principles. Our platform is architected around player safety, data minimization, and transparent controls.
                        </p>
                        <p class="mt-2 text-xs text-slate-400">
                            <em>Current Status: Commercial advertising technologies are inactive by default (<code class="text-emerald-400 font-mono">ADS_ENABLED=false</code>). No third-party marketing tags or ad tracking pixels execute during default portal operation.</em>
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">2. Information Collection & First-Party Analytics</h2>
                        <p>
                            We do not require mandatory registration or personal accounts for public game discovery and gameplay. Standard web server logs (including truncated IP addresses and user agents) are processed strictly for security, rate limiting, and operational diagnostics.
                        </p>
                        <p class="mt-2">
                            To measure portal engagement, game loads, and play sessions, we utilize a privacy-conscious, first-party analytics system. This system relies on rotating pseudonymized visitor identifiers (<code class="text-emerald-400 font-mono text-xs">taph5_vid</code>) and ephemeral session tokens. We do not track individual users across unrelated websites, do not construct behavioral profiles, and never sell or rent user data.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">3. Game Sandbox & Origin Isolation</h2>
                        <p>
                            Every game hosted on our portal runs within an isolated, sandboxed HTML5 frame (<code class="text-emerald-400 font-mono text-xs">sandbox="allow-scripts allow-forms allow-pointer-lock"</code>). Under no circumstances is <code class="text-amber-400 font-mono text-xs">allow-same-origin</code> granted. This technical barrier guarantees that game scripts cannot access parent cookies, local storage, session state, or credentials.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">4. Third-Party Advertising & Google Certified CMP</h2>
                        <p>
                            To sustainably offer free web games, TapH5 Games is architecturally prepared for approved advertising partners including Google AdSense and H5 Games Ads (Google Ad Placement API).
                        </p>
                        <p class="mt-2">
                            When commercial advertising is activated:
                        </p>
                        <ul class="list-disc list-inside space-y-1.5 mt-2 pl-2 text-slate-400 text-xs">
                            <li>Advertisers may use cookies or mobile advertising IDs to serve contextual or personalized advertisements and prevent fraudulent traffic.</li>
                            <li>For users within the European Economic Area (EEA), the United Kingdom, and Switzerland, Google requires an IAB Europe Transparency and Consent Framework (TCF v2.2) certified Consent Management Platform (CMP). Our platform prepares integration with Google's certified Privacy & Messaging CMP to ensure valid, auditable consent strings are communicated before ad scripts load.</li>
                            <li>H5 Games Ads and interstitial ad breaks run within individual game canvas documents subject to approved developer SDK integration.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-base font-bold text-white mb-2">5. Privacy Rights & Consent Management</h2>
                        <p>
                            Where regional privacy regulations (including GDPR, UK GDPR, and CCPA) apply, non-essential advertising scripts remain blocked until explicit consent is granted. You can review, adjust, or revoke your consent preferences at any time by selecting the "Cookie & Ad Preferences" link located in our footer.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

