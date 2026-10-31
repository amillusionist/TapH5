<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

export interface SearchResultItem {
    id: number;
    title: string;
    slug: string;
    url: string;
    thumbnail: string;
    category_name: string;
    category_slug?: string;
    game_type: string;
}

const props = withDefaults(
    defineProps<{
        idPrefix?: string;
        placeholder?: string;
        compact?: boolean;
    }>(),
    {
        idPrefix: 'search',
        placeholder: 'Search games by title, genre...',
        compact: false,
    }
);

const emit = defineEmits<{
    (e: 'navigate'): void;
}>();

const query = ref('');
const results = ref<SearchResultItem[]>([]);
const isOpen = ref(false);
const isLoading = ref(false);
const hasError = ref(false);
const selectedIndex = ref(-1);

const containerRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const listRef = ref<HTMLElement | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let abortController: AbortController | null = null;
const latestRequestId = ref(0);

const listboxId = computed(() => `${props.idPrefix}-results-listbox`);
const inputId = computed(() => `${props.idPrefix}-input`);

const activeDescendantId = computed(() => {
    if (selectedIndex.value >= 0 && selectedIndex.value < results.value.length) {
        return `${props.idPrefix}-option-${selectedIndex.value}`;
    }
    if (selectedIndex.value === results.value.length && results.value.length > 0) {
        return `${props.idPrefix}-option-view-all`;
    }
    return undefined;
});

const maxNavigationItems = computed(() => {
    return results.value.length > 0 ? results.value.length + 1 : 0;
});

/**
 * Perform asynchronous query to autocomplete endpoint with AbortController
 * and sequence protection against out-of-order stale responses.
 */
const fetchResults = async (searchTerm: string) => {
    if (abortController) {
        abortController.abort();
    }
    abortController = new AbortController();
    const requestId = ++latestRequestId.value;

    isLoading.value = true;
    hasError.value = false;

    try {
        const response = await fetch(
            `/api/games/autocomplete?q=${encodeURIComponent(searchTerm)}`,
            {
                signal: abortController.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }
        );

        if (requestId !== latestRequestId.value) {
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (requestId !== latestRequestId.value) {
            return;
        }

        results.value = Array.isArray(data.results) ? data.results : [];
        isLoading.value = false;
        selectedIndex.value = -1;
        isOpen.value = true;
    } catch (err: unknown) {
        if ((err as Error)?.name === 'AbortError') {
            return;
        }
        if (requestId !== latestRequestId.value) {
            return;
        }
        hasError.value = true;
        isLoading.value = false;
        results.value = [];
    }
};

const handleInput = () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    const trimmed = query.value.trim();

    if (trimmed.length < 2) {
        if (abortController) {
            abortController.abort();
            abortController = null;
        }
        results.value = [];
        isLoading.value = false;
        hasError.value = false;
        selectedIndex.value = -1;

        if (trimmed.length === 1) {
            isOpen.value = true;
        } else {
            isOpen.value = false;
        }
        return;
    }

    isLoading.value = true;
    isOpen.value = true;

    // 120ms debounce (within 100-150ms specification)
    debounceTimer = setTimeout(() => {
        fetchResults(trimmed);
    }, 120);
};

const handleFocus = () => {
    if (query.value.trim().length >= 1) {
        isOpen.value = true;
    }
};

const clearInput = () => {
    query.value = '';
    results.value = [];
    isOpen.value = false;
    selectedIndex.value = -1;
    hasError.value = false;
    if (abortController) {
        abortController.abort();
        abortController = null;
    }
    nextTick(() => {
        inputRef.value?.focus();
    });
};

const selectGame = (game: SearchResultItem) => {
    isOpen.value = false;
    emit('navigate');
    router.visit(game.url);
};

const submitFullSearch = () => {
    const trimmed = query.value.trim();
    isOpen.value = false;
    emit('navigate');
    if (trimmed) {
        router.get(route('games.index'), { search: trimmed });
    } else {
        router.get(route('games.index'));
    }
};

const scrollActiveItemIntoView = () => {
    nextTick(() => {
        if (!listRef.value || selectedIndex.value < 0) return;
        const activeEl = listRef.value.querySelector('[aria-selected="true"]') as HTMLElement | null;
        if (activeEl) {
            activeEl.scrollIntoView({ block: 'nearest' });
        }
    });
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (!isOpen.value && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        if (query.value.trim().length >= 1) {
            isOpen.value = true;
            return;
        }
    }

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (maxNavigationItems.value > 0) {
                selectedIndex.value = (selectedIndex.value + 1) % maxNavigationItems.value;
                scrollActiveItemIntoView();
            }
            break;

        case 'ArrowUp':
            e.preventDefault();
            if (maxNavigationItems.value > 0) {
                if (selectedIndex.value <= 0) {
                    selectedIndex.value = selectedIndex.value === 0 ? -1 : maxNavigationItems.value - 1;
                } else {
                    selectedIndex.value--;
                }
                scrollActiveItemIntoView();
            }
            break;

        case 'Enter':
            if (isOpen.value && selectedIndex.value >= 0) {
                e.preventDefault();
                if (selectedIndex.value < results.value.length) {
                    selectGame(results.value[selectedIndex.value]);
                } else {
                    submitFullSearch();
                }
            } else {
                // Regular submission
                e.preventDefault();
                submitFullSearch();
            }
            break;

        case 'Escape':
            e.preventDefault();
            isOpen.value = false;
            selectedIndex.value = -1;
            break;
    }
};

const handleClickOutside = (event: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        selectedIndex.value = -1;
    }
};

const onThumbnailError = (e: Event) => {
    const img = e.target as HTMLImageElement;
    if (img && !img.src.endsWith('/images/thumbnails/default-game.svg')) {
        img.src = '/images/thumbnails/default-game.svg';
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (debounceTimer) clearTimeout(debounceTimer);
    if (abortController) abortController.abort();
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Search Input Form -->
        <form @submit.prevent="submitFullSearch" role="search" class="relative w-full">
            <label :for="inputId" class="sr-only">Search games</label>

            <!-- Search Icon -->
            <button
                type="submit"
                aria-label="Submit search"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-400 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400 rounded-lg p-0.5"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </button>

            <!-- Text Input -->
            <input
                :id="inputId"
                ref="inputRef"
                v-model="query"
                type="text"
                autocomplete="off"
                spellcheck="false"
                :placeholder="placeholder"
                role="combobox"
                aria-autocomplete="list"
                :aria-expanded="isOpen"
                :aria-controls="listboxId"
                :aria-activedescendant="activeDescendantId"
                class="w-full pl-9 pr-9 py-2 bg-slate-900/90 border border-slate-800 rounded-xl text-xs sm:text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all shadow-inner"
                @input="handleInput"
                @focus="handleFocus"
                @keydown="handleKeyDown"
            />

            <!-- Spinner / Clear Button -->
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 flex items-center gap-1">
                <!-- Loading Spinner -->
                <div
                    v-if="isLoading"
                    class="w-3.5 h-3.5 border-2 border-slate-600 border-t-emerald-400 rounded-full animate-spin"
                    aria-label="Loading search suggestions"
                ></div>

                <!-- Clear Button -->
                <button
                    v-else-if="query"
                    type="button"
                    @click="clearInput"
                    aria-label="Clear search input"
                    class="text-slate-400 hover:text-white p-1 rounded-md focus:outline-none focus:ring-1 focus:ring-emerald-400 transition-colors"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
        </form>

        <!-- Autocomplete Suggestions Dropdown -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-95"
        >
            <div
                v-if="isOpen"
                :id="listboxId"
                ref="listRef"
                role="listbox"
                aria-label="Search suggestions"
                class="absolute left-0 right-0 top-full mt-2 z-50 w-full sm:min-w-[320px] lg:w-96 rounded-2xl bg-slate-900/95 border border-slate-800 shadow-2xl backdrop-blur-xl overflow-hidden divide-y divide-slate-800/80"
            >
                <!-- Short Query Prompt (< 2 chars) -->
                <div v-if="query.trim().length === 1" class="p-3.5 text-center text-xs text-slate-400">
                    <span class="text-slate-300 font-semibold">Type at least 2 characters</span> to see instant game suggestions.
                </div>

                <!-- Request Error State -->
                <div v-else-if="hasError" class="p-4 text-center">
                    <div class="inline-flex p-2 rounded-full bg-rose-500/10 text-rose-400 mb-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-rose-300">Unable to load suggestions</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Press Enter to perform full search.</p>
                </div>

                <!-- No Results State -->
                <div v-else-if="!isLoading && results.length === 0 && query.trim().length >= 2" class="p-4 text-center">
                    <div class="inline-flex p-2 rounded-full bg-slate-800/80 text-slate-400 mb-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-slate-200">No games found for "{{ query.trim() }}"</p>
                    <p class="text-[11px] text-slate-400 mt-1">Try another keyword or genre, or press Enter to search catalog.</p>
                </div>

                <!-- Results List -->
                <div v-else-if="results.length > 0" class="max-h-72 overflow-y-auto divide-y divide-slate-800/50 scrollbar-thin">
                    <div
                        v-for="(game, index) in results"
                        :id="`${idPrefix}-option-${index}`"
                        :key="game.id"
                        role="option"
                        :aria-selected="selectedIndex === index"
                        class="group flex items-center gap-3 p-2.5 sm:p-3 cursor-pointer transition-colors"
                        :class="selectedIndex === index ? 'bg-slate-800/90 text-white border-l-2 border-emerald-400 pl-2' : 'hover:bg-slate-800/60 text-slate-300'"
                        @click="selectGame(game)"
                        @mouseenter="selectedIndex = index"
                    >
                        <!-- Thumbnail -->
                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-slate-950 border border-slate-800 shrink-0">
                            <img
                                :src="game.thumbnail"
                                :alt="`${game.title} thumbnail`"
                                width="40"
                                height="40"
                                loading="lazy"
                                decoding="async"
                                @error="onThumbnailError"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                            />
                        </div>

                        <!-- Info -->
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="font-bold text-xs sm:text-sm text-slate-100 group-hover:text-emerald-400 transition-colors truncate">
                                    {{ game.title }}
                                </h4>
                                <span class="shrink-0 text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    {{ game.category_name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-400">
                                <span class="capitalize">{{ game.game_type === 'multi_file' ? 'Multi-File' : 'Instant H5' }}</span>
                                <span>•</span>
                                <span class="text-emerald-400/90">Free Play</span>
                            </div>
                        </div>

                        <!-- Arrow Icon -->
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition-all shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </div>
                </div>

                <!-- Footer "View all results" Action -->
                <div
                    v-if="results.length > 0"
                    :id="`${idPrefix}-option-view-all`"
                    role="option"
                    :aria-selected="selectedIndex === results.length"
                    class="p-2.5 sm:p-3 bg-slate-950/80 hover:bg-slate-800/80 cursor-pointer flex items-center justify-between text-xs transition-colors"
                    :class="selectedIndex === results.length ? 'bg-slate-800 text-emerald-400 border-l-2 border-emerald-400 pl-2' : 'text-slate-400 hover:text-emerald-400'"
                    @click="submitFullSearch"
                    @mouseenter="selectedIndex = results.length"
                >
                    <span class="flex items-center gap-1.5 font-semibold">
                        <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                        <span>View all matching results for "<span class="text-white">{{ query.trim() }}</span>"</span>
                    </span>
                    <span class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
                        <span>Enter ↵</span>
                    </span>
                </div>
            </div>
        </transition>
    </div>
</template>

