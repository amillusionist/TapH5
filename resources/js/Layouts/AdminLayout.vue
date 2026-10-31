<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import BrandLogo from '@/Components/BrandLogo.vue';
import type { PageProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const isMobileSidebarOpen = ref(false);
const dismissedFlash = ref(false);

const user = computed(() => page.props.auth?.user);
const role = computed(() => user.value?.role || 'admin');

const isSuperAdmin = computed(() => role.value === 'super_admin');
const canManageUsers = computed(() => isSuperAdmin.value);
const canManageSettings = computed(() => isSuperAdmin.value);
const canManageContent = computed(() => ['super_admin', 'admin', 'content_manager'].includes(role.value));
const canReviewGames = computed(() => ['super_admin', 'admin', 'content_manager'].includes(role.value));
const canViewAnalytics = computed(() => ['super_admin', 'admin', 'analyst'].includes(role.value));
const pendingReviewCount = computed(() => Number(page.props.pendingReviewCount ?? 0));

const roleLabel = computed(() => {
    switch (role.value) {
        case 'super_admin': return 'Super Admin';
        case 'admin': return 'Administrator';
        case 'content_manager': return 'Content Manager';
        case 'analyst': return 'Analyst';
        default: return 'Staff';
    }
});

const closeMobileSidebar = () => {
    isMobileSidebarOpen.value = false;
};

const dismissAlert = () => {
    dismissedFlash.value = true;
};
</script>

<template>
    <div class="min-h-screen bg-[#060911] text-slate-100 flex flex-col md:flex-row antialiased selection:bg-emerald-500 selection:text-slate-950 font-sans">
        <Head :title="title ? `${title} — Admin Control Panel` : 'Admin Control Panel — TapH5 Games'" />

        <!-- Mobile Sidebar Overlay Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isMobileSidebarOpen"
                @click="closeMobileSidebar"
                class="fixed inset-0 z-40 bg-black/80 backdrop-blur-sm md:hidden"
            ></div>
        </Transition>

        <!-- Sidebar Navigation -->
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 h-screen bg-[#090e1c] border-r border-slate-800/80 flex flex-col transition-transform duration-200 ease-in-out md:translate-x-0"
            :class="isMobileSidebarOpen ? 'translate-x-0 z-50' : '-translate-x-full'"
        >
            <!-- Logo & Brand Header -->
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800/80 shrink-0">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-2 group" aria-label="Admin Control Panel Dashboard">
                    <BrandLogo />
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 group-hover:border-emerald-500/40 transition-colors">
                        Admin
                    </span>
                </Link>
                <button
                    @click="closeMobileSidebar"
                    class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none"
                    aria-label="Close Sidebar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Sections -->
            <div class="flex-1 px-3 py-4 space-y-5 overflow-y-auto">
                <!-- Group: Overview -->
                <div>
                    <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Overview
                    </div>
                    <nav class="space-y-1">
                        <Link
                            :href="route('admin.dashboard')"
                            :class="route().current('admin.dashboard')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" stroke-width="2"/>
                                <rect x="14" y="3" width="7" height="7" rx="1.5" stroke-width="2"/>
                                <rect x="14" y="14" width="7" height="7" rx="1.5" stroke-width="2"/>
                                <rect x="3" y="14" width="7" height="7" rx="1.5" stroke-width="2"/>
                            </svg>
                            <span>Dashboard</span>
                        </Link>
                    </nav>
                </div>

                <!-- Group: Content Management -->
                <div v-if="canManageContent">
                    <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Content Management
                    </div>
                    <nav class="space-y-1">
                        <Link
                            :href="route('admin.homepage.index')"
                            :class="route().current('admin.homepage.*')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span>Homepage Editor</span>
                        </Link>

                        <Link
                            :href="route('admin.games.index')"
                            :class="route().current('admin.games.index') || route().current('admin.games.show') || route().current('admin.games.edit')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <polygon points="6 3 20 12 6 21 6 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>All Games</span>
                        </Link>

                        <Link
                            :href="route('admin.games.add')"
                            :class="route().current('admin.games.add')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Add Game</span>
                        </Link>

                        <Link
                            :href="route('admin.game_reviews.index')"
                            :class="route().current('admin.game_reviews.*')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center justify-between py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span class="truncate">Game Review</span>
                            </div>
                            <span
                                v-if="pendingReviewCount > 0"
                                class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 shrink-0"
                            >
                                {{ pendingReviewCount }}
                            </span>
                        </Link>

                        <Link
                            :href="route('admin.categories.index')"
                            :class="route().current('admin.categories.*')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Categories</span>
                        </Link>

                        <Link
                            :href="route('admin.tags.index')"
                            :class="route().current('admin.tags.*')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <line x1="4" y1="9" x2="20" y2="9" stroke-width="2" stroke-linecap="round"/>
                                <line x1="4" y1="15" x2="20" y2="15" stroke-width="2" stroke-linecap="round"/>
                                <line x1="10" y1="3" x2="8" y2="21" stroke-width="2" stroke-linecap="round"/>
                                <line x1="16" y1="3" x2="14" y2="21" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Tags</span>
                        </Link>
                    </nav>
                </div>

                <!-- Group: Platform Insights -->
                <div v-if="canViewAnalytics">
                    <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Platform Insights
                    </div>
                    <nav class="space-y-1">
                        <Link
                            :href="route('admin.analytics.index')"
                            :class="route().current('admin.analytics.*')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Analytics</span>
                        </Link>

                        <Link
                            :href="route('admin.monetization.index')"
                            :class="route().current('admin.monetization.*')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="2" y="4" width="20" height="16" rx="2" stroke-width="2"/>
                                <line x1="2" y1="10" x2="22" y2="10" stroke-width="2"/>
                                <line x1="6" y1="15" x2="10" y2="15" stroke-width="2"/>
                            </svg>
                            <span>Monetization</span>
                        </Link>
                    </nav>
                </div>

                <!-- Group: Access Management -->
                <div v-if="canManageUsers">
                    <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Access Control
                    </div>
                    <nav class="space-y-1">
                        <Link
                            :href="route('admin.users.index')"
                            :class="route().current('admin.users.*')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Users & Roles</span>
                        </Link>
                    </nav>
                </div>

                <!-- Group: System & Tools -->
                <div>
                    <div class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        System Operations
                    </div>
                    <nav class="space-y-1">
                        <Link
                            v-if="canManageSettings"
                            :href="route('admin.settings.index')"
                            :class="route().current('admin.settings.*')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <circle cx="12" cy="12" r="3" stroke-width="2"/>
                            </svg>
                            <span>Platform Settings</span>
                        </Link>

                        <Link
                            :href="route('admin.system.index')"
                            :class="route().current('admin.system.index')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>System Health</span>
                        </Link>

                        <Link
                            :href="route('admin.system.logs')"
                            :class="route().current('admin.system.logs')
                                ? 'bg-emerald-500/10 text-emerald-400 border-l-2 border-emerald-400 font-semibold pl-2.5'
                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 pl-3'"
                            class="flex items-center gap-3 py-2 pr-3 rounded-r-xl text-xs sm:text-sm transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span>Activity Logs</span>
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- Bottom Section: Portal Link & User Info -->
            <div class="p-3 border-t border-slate-800/80 space-y-2 shrink-0 bg-slate-950/40">
                <Link
                    href="/"
                    target="_blank"
                    class="flex items-center justify-between w-full px-3 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-emerald-400 hover:bg-slate-800/50 transition-colors"
                >
                    <span class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="15 3 21 3 21 9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="10" y1="14" x2="21" y2="3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>View Live Portal</span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="m9 18 6-6-6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </Link>

                <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <p class="text-xs font-bold text-white truncate">
                                {{ page.props.auth?.user?.name || 'Administrator' }}
                            </p>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                {{ roleLabel }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 truncate">
                            {{ page.props.auth?.user?.email || 'admin@taph5gaming.com' }}
                        </p>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        title="Sign Out of Admin"
                        aria-label="Sign Out of Admin"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors focus:outline-none focus:ring-1 focus:ring-rose-400 shrink-0"
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
        <div class="flex-1 flex flex-col min-w-0 md:pl-64">
            <!-- Top Sticky Navigation Bar -->
            <header class="h-16 px-4 md:px-8 border-b border-slate-800/80 bg-[#080d1a]/90 backdrop-blur-md flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <button
                        @click="isMobileSidebarOpen = true"
                        class="md:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none"
                        aria-label="Open Mobile Menu"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <line x1="3" y1="12" x2="21" y2="12" stroke-width="2" stroke-linecap="round"/>
                            <line x1="3" y1="6" x2="21" y2="6" stroke-width="2" stroke-linecap="round"/>
                            <line x1="3" y1="18" x2="21" y2="18" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <div class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                        <span class="text-slate-400">Control Panel</span>
                        <span class="text-slate-600">/</span>
                        <span class="text-white font-bold">{{ title || 'Dashboard' }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Single-Domain Online
                    </span>
                </div>
            </header>

            <!-- Flash Alert Messages (Dismissible & Styled) -->
            <div
                v-if="!dismissedFlash && (page.props.flash?.success || page.props.flash?.error || page.props.flash?.warning)"
                class="px-4 md:px-8 pt-4"
            >
                <!-- Success Alert -->
                <div
                    v-if="page.props.flash?.success"
                    class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-emerald-950/20"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <path d="m9 12 2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="truncate">{{ page.props.flash.success }}</span>
                    </div>
                    <button
                        @click="dismissAlert"
                        class="p-1 text-emerald-400/80 hover:text-emerald-300 focus:outline-none shrink-0"
                        title="Dismiss"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Error Alert -->
                <div
                    v-if="page.props.flash?.error"
                    class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-rose-950/20"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
                            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="truncate">{{ page.props.flash.error }}</span>
                    </div>
                    <button
                        @click="dismissAlert"
                        class="p-1 text-rose-400/80 hover:text-rose-300 focus:outline-none shrink-0"
                        title="Dismiss"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Warning Alert -->
                <div
                    v-if="page.props.flash?.warning"
                    class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-amber-950/20"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="12" y1="9" x2="12" y2="13" stroke-width="2" stroke-linecap="round"/>
                            <line x1="12" y1="17" x2="12.01" y2="17" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="truncate">{{ page.props.flash.warning }}</span>
                    </div>
                    <button
                        @click="dismissAlert"
                        class="p-1 text-amber-400/80 hover:text-amber-300 focus:outline-none shrink-0"
                        title="Dismiss"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Page Content Slot -->
            <main class="flex-1 p-4 md:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
