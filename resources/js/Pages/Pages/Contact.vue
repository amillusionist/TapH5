<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import type { SiteMeta } from '@/types';

defineProps<{
    meta: SiteMeta;
}>();

const name = ref('');
const email = ref('');
const subject = ref('');
const message = ref('');
const submitted = ref(false);

const submitContact = () => {
    // Phase 1 client interaction state
    submitted.value = true;
};
</script>

<template>
    <SeoHead :meta="meta" />

    <AppLayout>
        <div class="max-w-3xl mx-auto py-8">
            <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6">
                <Link href="/" class="hover:text-emerald-400">Home</Link>
                <span>/</span>
                <span class="text-slate-200">Contact Us</span>
            </nav>

            <div class="p-8 sm:p-12 rounded-3xl bg-slate-900/80 border border-slate-800">
                <header class="border-b border-slate-800 pb-6 mb-8">
                    <span class="px-3 py-1 rounded-full bg-cyan-500/10 text-cyan-400 text-xs font-bold uppercase tracking-wider">
                        Get In Touch
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-black text-white mt-3">Contact Support & Developer Relations</h1>
                    <p class="text-slate-400 text-sm mt-2">
                        Have feedback, game inquiries, or partnership questions? Reach out through the form below.
                    </p>
                </header>

                <div v-if="submitted" class="p-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-1">Message Sent Successfully</h3>
                    <p class="text-xs text-slate-300">Thank you for reaching out. Our team will review your message.</p>
                </div>

                <form v-else @submit.prevent="submitContact" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Name</label>
                            <input
                                v-model="name"
                                required
                                type="text"
                                placeholder="Your name"
                                class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Email</label>
                            <input
                                v-model="email"
                                required
                                type="email"
                                placeholder="your@email.com"
                                class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Subject</label>
                        <input
                            v-model="subject"
                            required
                            type="text"
                            placeholder="Game submission, feedback, or general inquiry"
                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Message</label>
                        <textarea
                            v-model="message"
                            required
                            rows="5"
                            placeholder="Please provide details about your inquiry..."
                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="px-8 py-3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-sm uppercase tracking-wide rounded-xl shadow-lg shadow-emerald-500/20 transition-all"
                    >
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

