<script setup lang="ts">
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import BrandLogo from '@/Components/BrandLogo.vue';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const searchQuery = ref('');
const isMobileMenuOpen = ref(false);
const isCategoriesDropdownOpen = ref(false);

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get(route('games.index'), { search: searchQuery.value.trim() });
    }
};

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#080d1a] text-slate-100 font-sans selection:bg-emerald-500 selection:text-slate-950">
        <!-- Main Navigation Bar -->
        <header class="sticky top-0 z-40 w-full bg-[#080d1a]/90 backdrop-blur-md border-b border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
                <!-- Brand Logo -->
                <BrandLogo />

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 lg:gap-2">
                    <Link
                        href="/"
                        class="px-3.5 py-2 text-sm font-semibold rounded-xl transition-colors"
                        :class="$page.url === '/' ? 'text-emerald-400 bg-emerald-500/10' : 'text-slate-300 hover:text-white hover:bg-slate-800/50'"
                    >
                        Home
                    </Link>

                    <Link
                        :href="route('games.index')"
                        class="px-3.5 py-2 text-sm font-semibold rounded-xl transition-colors"
                        :class="$page.url.startsWith('/games') && !$page.url.includes('/games/') ? 'text-emerald-400 bg-emerald-500/10' : 'text-slate-300 hover:text-white hover:bg-slate-800/50'"
                    >
                        All Games
                    </Link>

                    <!-- Categories Dropdown -->
                    <div class="relative" @mouseenter="isCategoriesDropdownOpen = true" @mouseleave="isCategoriesDropdownOpen = false">
                        <button
                            type="button"
                            @click="isCategoriesDropdownOpen = !isCategoriesDropdownOpen"
                            @keydown.escape="isCategoriesDropdownOpen = false"
                            aria-haspopup="true"
                            :aria-expanded="isCategoriesDropdownOpen"
                            class="flex items-center gap-1 px-3.5 py-2 text-sm font-semibold rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/50 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            :class="$page.url.startsWith('/category') ? 'text-emerald-400 bg-emerald-500/10' : ''"
                        >
                            <span>Categories</span>
                            <svg class="w-4 h-4 ml-0.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-show="isCategoriesDropdownOpen"
                            class="absolute top-full left-0 mt-1 w-56 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-2 z-50 transition-all duration-200"
                        >
                            <Link
                                v-for="cat in page.props.categoriesNav"
                                :key="cat.id"
                                :href="route('categories.show', { slug: cat.slug })"
                                class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-emerald-400 hover:bg-slate-800/80 transition-colors focus:outline-none focus:ring-1 focus:ring-emerald-400"
                            >
                                <span>{{ cat.name }}</span>
                                <span class="text-[11px] text-slate-400">{{ cat.games_count }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- Search Input Form -->
                <div class="hidden lg:flex items-center max-w-xs w-full">
                    <form @submit.prevent="handleSearch" role="search" class="relative w-full">
                        <label for="desktop-search-input" class="sr-only">Search free games</label>
                        <input
                            id="desktop-search-input"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search games..."
                            aria-label="Search games"
                            class="w-full pl-9 pr-4 py-2 bg-slate-900/90 border border-slate-800 rounded-xl text-xs text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all"
                        />
                        <button type="submit" aria-label="Submit search" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-400 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400 rounded">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center gap-2 md:hidden">
                    <button
                        @click="toggleMobileMenu"
                        type="button"
                        aria-label="Toggle navigation menu"
                        :aria-expanded="isMobileMenuOpen"
                        class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-400"
                    >
                        <svg v-if="!isMobileMenuOpen" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>
                        </svg>
                        <svg v-else class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Dropdown Menu -->
            <div v-show="isMobileMenuOpen" class="md:hidden border-b border-slate-800 bg-slate-950/95 px-4 pt-3 pb-6">
                <!-- Mobile Search -->
                <form @submit.prevent="handleSearch" role="search" class="relative w-full mb-4">
                    <label for="mobile-search-input" class="sr-only">Search games</label>
                    <input
                        id="mobile-search-input"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search games..."
                        aria-label="Search games"
                        class="w-full pl-9 pr-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500"
                    />
                    <button type="submit" aria-label="Submit search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-400 rounded">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                    </button>
                </form>

                <div class="flex flex-col gap-1">
                    <Link
                        href="/"
                        class="px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-200 hover:bg-slate-900"
                        @click="isMobileMenuOpen = false"
                    >
                        Home
                    </Link>
                    <Link
                        :href="route('games.index')"
                        class="px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-200 hover:bg-slate-900"
                        @click="isMobileMenuOpen = false"
                    >
                        All Games
                    </Link>

                    <div class="pt-3 pb-1 border-t border-slate-800/80">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider px-3">Categories</span>
                        <div class="grid grid-cols-2 gap-1 mt-2">
                            <Link
                                v-for="cat in page.props.categoriesNav"
                                :key="cat.id"
                                :href="route('categories.show', { slug: cat.slug })"
                                class="px-3 py-2 rounded-lg text-xs text-slate-300 hover:text-emerald-400 hover:bg-slate-900 truncate"
                                @click="isMobileMenuOpen = false"
                            >
                                {{ cat.name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <slot />
        </main>

        <!-- Global Gaming Portal Footer -->
        <footer class="mt-auto bg-slate-950 border-t border-slate-800/80 pt-16 pb-12 text-slate-400">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-10 pb-12 border-b border-slate-900">
                    <!-- Brand info -->
                    <div class="lg:col-span-2 flex flex-col gap-4">
                        <BrandLogo />
                        <p class="text-xs sm:text-sm text-slate-400 leading-relaxed max-w-sm">
                            TapH5 Games is a dedicated web gaming portal delivering high-performance, responsive HTML5 games playable across mobile, tablet, and desktop without downloads.
                        </p>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>Shared-Hosting & Sandbox Origin Ready</span>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider mb-4">Categories</h4>
                        <ul class="space-y-2 text-xs">
                            <li v-for="cat in page.props.categoriesNav?.slice(0, 6)" :key="cat.id">
                                <Link :href="route('categories.show', { slug: cat.slug })" class="hover:text-emerald-400 transition-colors">
                                    {{ cat.name }} Games
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Navigation -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider mb-4">Discover</h4>
                        <ul class="space-y-2 text-xs">
                            <li><Link href="/" class="hover:text-emerald-400 transition-colors">Home Portal</Link></li>
                            <li><Link :href="route('games.index')" class="hover:text-emerald-400 transition-colors">All Games</Link></li>
                            <li><Link :href="route('games.index', { sort: 'featured' })" class="hover:text-emerald-400 transition-colors">Featured Games</Link></li>
                            <li><Link :href="route('games.index', { sort: 'popular' })" class="hover:text-emerald-400 transition-colors">Popular Titles</Link></li>
                        </ul>
                    </div>

                    <!-- Legal / Informational -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider mb-4">Legal & About</h4>
                        <ul class="space-y-2 text-xs">
                            <li><Link :href="route('pages.about')" class="hover:text-emerald-400 transition-colors">About Us</Link></li>
                            <li><Link :href="route('pages.contact')" class="hover:text-emerald-400 transition-colors">Contact Us</Link></li>
                            <li><Link :href="route('pages.privacy')" class="hover:text-emerald-400 transition-colors">Privacy Policy</Link></li>
                            <li><Link :href="route('pages.terms')" class="hover:text-emerald-400 transition-colors">Terms of Service</Link></li>
                            <li><Link :href="route('pages.cookie-policy')" class="hover:text-emerald-400 transition-colors">Cookie Policy</Link></li>
                            <li><Link :href="route('pages.data-policy')" class="hover:text-emerald-400 transition-colors">Data Policy</Link></li>
                        </ul>
                    </div>
                </div>

                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                    <p>© 2026 TapH5 Games. All rights reserved.</p>
                    <div class="flex items-center gap-4">
                        <Link :href="route('sitemap')" class="hover:text-slate-400">XML Sitemap</Link>
                        <span>•</span>
                        <span>Designed for Global HTML5 Gaming</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

