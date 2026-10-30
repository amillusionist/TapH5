<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import BrandLogo from '@/Components/BrandLogo.vue';
import type { PageProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const isMobileSidebarOpen = ref(false);

const closeMobileSidebar = () => {
    isMobileSidebarOpen.value = false;
};
</script>

<template>
    <div class="min-h-screen bg-[#060911] text-slate-100 flex flex-col md:flex-row antialiased selection:bg-emerald-500 selection:text-slate-950">
        <Head :title="title ? `${title} — Admin Control Panel` : 'Admin Control Panel — TapH5 Games'" />

        <!-- Mobile Sidebar Overlay Backdrop -->
        <div
            v-if="isMobileSidebarOpen"
            @click="closeMobileSidebar"
            class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm md:hidden transition-opacity"
        ></div>

        <!-- Sidebar Navigation -->
        <aside
            class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-[#0a0f1d] border-r border-slate-800/80 flex flex-col transform transition-transform duration-200 ease-in-out md:translate-x-0"
            :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Logo & Brand Header -->
            <div class="h-18 px-5 flex items-center justify-between border-b border-slate-800/80">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-2.5">
                    <BrandLogo />
                </Link>
                <button
                    @click="closeMobileSidebar"
                    class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Admin Badge & System Subtitle -->
            <div class="px-5 py-3 border-b border-slate-800/40 bg-slate-950/40 flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Control Panel</span>
                <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-widest bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 rounded-full">
                    Admin
                </span>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    :href="route('admin.dashboard')"
                    :class="route().current('admin.dashboard') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1" stroke-width="2"/>
                        <rect x="14" y="3" width="7" height="7" rx="1" stroke-width="2"/>
                        <rect x="14" y="14" width="7" height="7" rx="1" stroke-width="2"/>
                        <rect x="3" y="14" width="7" height="7" rx="1" stroke-width="2"/>
                    </svg>
                    <span>Dashboard</span>
                </Link>

                <Link
                    :href="route('admin.games.index')"
                    :class="route().current('admin.games.*') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <polygon points="6 3 20 12 6 21 6 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Games Manager</span>
                </Link>

                <Link
                    :href="route('admin.categories.index')"
                    :class="route().current('admin.categories.*') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Categories</span>
                </Link>

                <Link
                    :href="route('admin.tags.index')"
                    :class="route().current('admin.tags.*') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <line x1="4" y1="9" x2="20" y2="9" stroke-width="2" stroke-linecap="round"/>
                        <line x1="4" y1="15" x2="20" y2="15" stroke-width="2" stroke-linecap="round"/>
                        <line x1="10" y1="3" x2="8" y2="21" stroke-width="2" stroke-linecap="round"/>
                        <line x1="16" y1="3" x2="14" y2="21" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>Tags</span>
                </Link>
            </nav>

            <!-- Bottom Section: Portal Shortcut & Logged-In User Profile -->
            <div class="p-3 border-t border-slate-800/80 space-y-2">
                <Link
                    href="/"
                    target="_blank"
                    class="flex items-center justify-between w-full px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800/50 transition-colors"
                >
                    <span class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="15 3 21 3 21 9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="10" y1="14" x2="21" y2="3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>View Public Portal</span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="m9 18 6-6-6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </Link>

                <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800/60 flex items-center justify-between">
                    <div class="min-w-0 pr-2">
                        <p class="text-xs font-bold text-white truncate">{{ page.props.auth?.user?.name || 'Administrator' }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ page.props.auth?.user?.email || '' }}</p>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        title="Sign Out"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors focus:outline-none"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="16 17 21 12 16 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="21" y1="12" x2="9" y2="12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Body Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Mobile Bar -->
            <header class="h-16 px-4 md:px-8 border-b border-slate-800/80 bg-[#080d1a]/80 backdrop-blur-md flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <button
                        @click="isMobileSidebarOpen = true"
                        class="md:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 focus:outline-none"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <line x1="3" y1="12" x2="21" y2="12" stroke-width="2" stroke-linecap="round"/>
                            <line x1="3" y1="6" x2="21" y2="6" stroke-width="2" stroke-linecap="round"/>
                            <line x1="3" y1="18" x2="21" y2="18" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <h1 class="text-base sm:text-lg font-bold text-white tracking-tight">
                        {{ title || 'Dashboard' }}
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Single-Domain Online
                    </span>
                </div>
            </header>

            <!-- Flash Alert Messages -->
            <div v-if="page.props.flash?.success || page.props.flash?.error || page.props.flash?.warning" class="px-4 md:px-8 pt-4">
                <div
                    v-if="page.props.flash?.success"
                    class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center justify-between shadow-lg shadow-emerald-950/20"
                >
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <path d="m9 12 2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>{{ page.props.flash.success }}</span>
                    </div>
                </div>

                <div
                    v-if="page.props.flash?.error"
                    class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm flex items-center justify-between shadow-lg shadow-rose-950/20"
                >
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
                            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>{{ page.props.flash.error }}</span>
                    </div>
                </div>

                <div
                    v-if="page.props.flash?.warning"
                    class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm flex items-center justify-between shadow-lg shadow-amber-950/20"
                >
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="12" y1="9" x2="12" y2="13" stroke-width="2" stroke-linecap="round"/>
                            <line x1="12" y1="17" x2="12.01" y2="17" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>{{ page.props.flash.warning }}</span>
                    </div>
                </div>
            </div>

            <!-- Page Slot Content -->
            <main class="flex-1 p-4 md:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
