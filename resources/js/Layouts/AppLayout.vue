<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import BrandLogo from '@/Components/BrandLogo.vue';
import SearchAutocomplete from '@/Components/SearchAutocomplete.vue';
import ConsentBanner from '@/Components/ConsentBanner.vue';
import AllGamesMegaMenu from '@/Components/AllGamesMegaMenu.vue';
import { openConsentPreferences } from '@/services/ads';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const isMobileMenuOpen = ref(false);
const isMobileGamesExpanded = ref(false);

// Categories Dropdown State
const isCategoriesDropdownOpen = ref(false);
const categoriesDropdownRef = ref<HTMLElement | null>(null);
const categoriesButtonRef = ref<HTMLButtonElement | null>(null);
let dropdownHoverTimer: ReturnType<typeof setTimeout> | null = null;

// Mega Menu State
const isMegaMenuOpen = ref(false);
const megaMenuContainerRef = ref<HTMLElement | null>(null);
let megaMenuHoverTimer: ReturnType<typeof setTimeout> | null = null;

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const onDropdownMouseEnter = () => {
    if (dropdownHoverTimer) {
        clearTimeout(dropdownHoverTimer);
        dropdownHoverTimer = null;
    }
    // Close mega menu when categories is entered
    if (megaMenuHoverTimer) {
        clearTimeout(megaMenuHoverTimer);
        megaMenuHoverTimer = null;
    }
    isMegaMenuOpen.value = false;
    isCategoriesDropdownOpen.value = true;
};

const onDropdownMouseLeave = () => {
    if (dropdownHoverTimer) clearTimeout(dropdownHoverTimer);
    dropdownHoverTimer = setTimeout(() => {
        isCategoriesDropdownOpen.value = false;
        dropdownHoverTimer = null;
    }, 180);
};

const toggleCategoriesDropdown = () => {
    if (dropdownHoverTimer) {
        clearTimeout(dropdownHoverTimer);
        dropdownHoverTimer = null;
    }
    isMegaMenuOpen.value = false;
    isCategoriesDropdownOpen.value = !isCategoriesDropdownOpen.value;
};

const closeCategoriesDropdown = () => {
    if (dropdownHoverTimer) {
        clearTimeout(dropdownHoverTimer);
        dropdownHoverTimer = null;
    }
    isCategoriesDropdownOpen.value = false;
};

// Mega Menu Handlers
const onMegaMenuEnter = () => {
    if (megaMenuHoverTimer) {
        clearTimeout(megaMenuHoverTimer);
        megaMenuHoverTimer = null;
    }
    // Close categories dropdown when mega menu is entered
    if (dropdownHoverTimer) {
        clearTimeout(dropdownHoverTimer);
        dropdownHoverTimer = null;
    }
    isCategoriesDropdownOpen.value = false;
    isMegaMenuOpen.value = true;
};

const onMegaMenuLeave = () => {
    if (megaMenuHoverTimer) clearTimeout(megaMenuHoverTimer);
    megaMenuHoverTimer = setTimeout(() => {
        isMegaMenuOpen.value = false;
        megaMenuHoverTimer = null;
    }, 180);
};

const closeMegaMenu = () => {
    if (megaMenuHoverTimer) {
        clearTimeout(megaMenuHoverTimer);
        megaMenuHoverTimer = null;
    }
    isMegaMenuOpen.value = false;
};

const handleDocumentClick = (e: MouseEvent) => {
    const target = e.target as Node;
    if (categoriesDropdownRef.value && !categoriesDropdownRef.value.contains(target)) {
        isCategoriesDropdownOpen.value = false;
    }
    const megaMenuEl = document.getElementById('all-games-mega-menu');
    if (
        megaMenuContainerRef.value &&
        !megaMenuContainerRef.value.contains(target) &&
        (!megaMenuEl || !megaMenuEl.contains(target))
    ) {
        isMegaMenuOpen.value = false;
    }
};

const onButtonKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        isCategoriesDropdownOpen.value = true;
        setTimeout(() => {
            const firstLink = categoriesDropdownRef.value?.querySelector<HTMLAnchorElement>('[role="menuitem"]');
            firstLink?.focus();
        }, 50);
    } else if (e.key === 'Escape') {
        isCategoriesDropdownOpen.value = false;
    }
};

const onMenuKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        isCategoriesDropdownOpen.value = false;
        categoriesButtonRef.value?.focus();
    }
};

let removeNavListener: (() => void) | null = null;

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
    removeNavListener = router.on('navigate', () => {
        isCategoriesDropdownOpen.value = false;
        isMegaMenuOpen.value = false;
        isMobileMenuOpen.value = false;
    });
});

onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick);
    if (removeNavListener) removeNavListener();
});
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

                    <!-- All Games with Mega Menu Trigger -->
                    <div
                        ref="megaMenuContainerRef"
                        class="relative"
                        @mouseenter="onMegaMenuEnter"
                        @mouseleave="onMegaMenuLeave"
                    >
                        <Link
                            :href="route('games.index')"
                            class="flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            :class="[
                                $page.url.startsWith('/games') && !$page.url.includes('/games/') ? 'text-emerald-400 bg-emerald-500/10' : 'text-slate-300 hover:text-white hover:bg-slate-800/50',
                                isMegaMenuOpen ? 'bg-slate-800/80 text-white' : ''
                            ]"
                            aria-haspopup="true"
                            :aria-expanded="isMegaMenuOpen"
                            aria-controls="all-games-mega-menu"
                        >
                            <span>All Games</span>
                            <svg
                                class="w-4 h-4 ml-0.5 text-slate-400 transition-transform duration-200"
                                :class="{ 'rotate-180 text-emerald-400': isMegaMenuOpen }"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </Link>
                    </div>

                    <!-- Categories Dropdown (Hover & Click Supported) -->
                    <div
                        ref="categoriesDropdownRef"
                        class="relative"
                        @mouseenter="onDropdownMouseEnter"
                        @mouseleave="onDropdownMouseLeave"
                        @keydown="onMenuKeyDown"
                    >
                        <button
                            ref="categoriesButtonRef"
                            type="button"
                            @click="toggleCategoriesDropdown"
                            @keydown="onButtonKeyDown"
                            aria-haspopup="menu"
                            :aria-expanded="isCategoriesDropdownOpen"
                            aria-controls="categories-desktop-menu"
                            class="flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            :class="[
                                $page.url.startsWith('/category') ? 'text-emerald-400 bg-emerald-500/10' : '',
                                isCategoriesDropdownOpen ? 'bg-slate-800/80 text-white' : ''
                            ]"
                        >
                            <span>Categories</span>
                            <svg
                                class="w-4 h-4 ml-0.5 text-slate-400 transition-transform duration-200"
                                :class="{ 'rotate-180 text-emerald-400': isCategoriesDropdownOpen }"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <!-- Dropdown Menu with zero-gap hover bridge (pt-1.5) -->
                        <transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                            enter-to-class="transform scale-100 opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="transform scale-100 opacity-100 translate-y-0"
                            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                        >
                            <div
                                v-show="isCategoriesDropdownOpen"
                                id="categories-desktop-menu"
                                role="menu"
                                aria-label="Game Categories"
                                class="absolute top-full left-0 pt-1.5 w-60 z-50 focus:outline-none"
                            >
                                <div class="rounded-2xl bg-slate-900/95 backdrop-blur-xl border border-slate-800 shadow-2xl p-2 divide-y divide-slate-800/60">
                                    <div class="px-3 py-1.5 flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Game Genres</span>
                                        <span class="text-[10px] font-semibold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">
                                            {{ page.props.categoriesNav?.length ?? 0 }}
                                        </span>
                                    </div>

                                    <div class="py-1">
                                        <Link
                                            v-for="cat in page.props.categoriesNav"
                                            :key="cat.id"
                                            :href="route('categories.show', { slug: cat.slug })"
                                            role="menuitem"
                                            tabindex="0"
                                            class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-emerald-400 hover:bg-slate-800/80 transition-all focus:outline-none focus:bg-slate-800 focus:text-emerald-400"
                                            @click="closeCategoriesDropdown"
                                        >
                                            <span class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-600 group-hover:bg-emerald-400 transition-colors"></span>
                                                <span>{{ cat.name }}</span>
                                            </span>
                                            <span class="text-[10px] font-semibold text-slate-400 bg-slate-800/80 group-hover:bg-emerald-500/10 group-hover:text-emerald-300 px-2 py-0.5 rounded-full transition-colors">
                                                {{ cat.games_count }}
                                            </span>
                                        </Link>
                                    </div>

                                    <div class="pt-1.5">
                                        <Link
                                            :href="route('games.index')"
                                            role="menuitem"
                                            class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-emerald-400 hover:text-emerald-300 hover:bg-slate-800/60 transition-colors"
                                            @click="closeCategoriesDropdown"
                                        >
                                            <span>Browse All Genres</span>
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M5 12h14m-7-7 7 7-7 7"/>
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </nav>

                <!-- Desktop Search Autocomplete -->
                <div class="hidden lg:flex items-center max-w-xs xl:max-w-sm w-full">
                    <SearchAutocomplete id-prefix="desktop-search" />
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

            <!-- All Games Full-Width Desktop Mega Menu -->
            <AllGamesMegaMenu
                :is-open="isMegaMenuOpen"
                @close="closeMegaMenu"
                @mouseenter="onMegaMenuEnter"
                @mouseleave="onMegaMenuLeave"
            />

            <!-- Mobile Dropdown Menu -->
            <div v-show="isMobileMenuOpen" class="md:hidden border-b border-slate-800 bg-slate-950/95 px-4 pt-3 pb-6">
                <!-- Mobile Search Autocomplete -->
                <div class="w-full mb-4">
                    <SearchAutocomplete id-prefix="mobile-search" @navigate="isMobileMenuOpen = false" />
                </div>

                <div class="flex flex-col gap-2">
                    <Link
                        href="/"
                        class="px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-200 hover:bg-slate-900"
                        @click="isMobileMenuOpen = false"
                    >
                        Home
                    </Link>

                    <!-- All Games Mobile Accordion Item -->
                    <div class="rounded-xl overflow-hidden bg-slate-900/50 border border-slate-800/80">
                        <div class="flex items-center justify-between">
                            <Link
                                :href="route('games.index')"
                                class="flex-grow px-3 py-3 text-sm font-semibold text-slate-200 hover:text-white flex items-center gap-2 min-h-[44px]"
                                @click="isMobileMenuOpen = false"
                            >
                                <span>All Games</span>
                                <span class="text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-1.5 py-0.5 rounded">
                                    Catalog
                                </span>
                            </Link>

                            <button
                                type="button"
                                @click="isMobileGamesExpanded = !isMobileGamesExpanded"
                                aria-label="Toggle popular games preview"
                                :aria-expanded="isMobileGamesExpanded"
                                class="p-3 text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors focus:outline-none focus:ring-1 focus:ring-emerald-400 min-h-[44px] min-w-[44px] flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180 text-emerald-400': isMobileGamesExpanded }"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Expandable Mobile Games Carousel -->
                        <div
                            v-show="isMobileGamesExpanded"
                            class="px-3 pb-3 pt-1 border-t border-slate-800/80 bg-slate-950/70"
                        >
                            <div class="flex items-center justify-between py-1.5 mb-2">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Popular Quick Play</span>
                                <span class="text-[10px] text-emerald-400 font-semibold">Swipe →</span>
                            </div>

                            <!-- Swipeable Carousel Track -->
                            <div class="flex items-stretch gap-3 overflow-x-auto scrollbar-none snap-x snap-mandatory pb-2 focus:outline-none">
                                <Link
                                    v-for="mGame in (page.props.megaMenuGames || []).slice(0, 8)"
                                    :key="mGame.id"
                                    :href="route('games.show', { slug: mGame.slug })"
                                    @click="isMobileMenuOpen = false"
                                    class="snap-start shrink-0 w-36 rounded-xl bg-slate-900 border border-slate-800 p-1.5 group flex flex-col active:scale-95 transition-all"
                                >
                                    <div class="relative w-full aspect-[4/3] rounded-lg overflow-hidden bg-slate-950">
                                        <img
                                            :src="mGame.thumbnail || '/images/thumbnails/default-game.svg'"
                                            :alt="mGame.title"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                            loading="lazy"
                                        />
                                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent opacity-40"></div>
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-active:opacity-100 transition-opacity">
                                            <div class="w-7 h-7 rounded-full bg-emerald-500 text-slate-950 flex items-center justify-center shadow">
                                                <svg class="w-3 h-3 ml-0.5" viewBox="0 0 24 24" fill="currentColor">
                                                    <polygon points="6 3 20 12 6 21 6 3" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-1.5 px-0.5">
                                        <div class="text-xs font-bold text-slate-200 truncate group-hover:text-emerald-400">
                                            {{ mGame.title }}
                                        </div>
                                        <div class="flex items-center justify-between text-[10px] text-slate-400 mt-0.5">
                                            <span class="text-emerald-400/90 font-medium truncate">{{ mGame.category?.name || 'H5 Game' }}</span>
                                            <span v-if="mGame.plays_count">{{ mGame.plays_count }}p</span>
                                        </div>
                                    </div>
                                </Link>
                            </div>

                            <!-- Dedicated View All Games Button -->
                            <Link
                                :href="route('games.index')"
                                @click="isMobileMenuOpen = false"
                                class="mt-2 w-full min-h-[44px] py-2.5 px-3 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold text-xs border border-emerald-500/30 flex items-center justify-center gap-1.5 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            >
                                <span>View All Games</span>
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14m-7-7 7 7-7 7"/>
                                </svg>
                            </Link>
                        </div>
                    </div>

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

        <!-- Optional Full-Bleed Top / Hero Slot -->
        <slot name="hero" />

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
                            <li><button @click="openConsentPreferences" type="button" class="hover:text-emerald-400 transition-colors text-left">Cookie & Ad Preferences</button></li>
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

        <!-- First-Party Consent Banner / Modal -->
        <ConsentBanner />
    </div>
</template>

