<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Game, PageProps } from '@/types';
import { GameSessionTracker } from '@/services/analytics';
import { triggerH5GameBreak } from '@/services/ads';

const props = withDefaults(
    defineProps<{
        game: Game;
        autoPlay?: boolean;
    }>(),
    {
        autoPlay: false,
    }
);

const page = usePage<PageProps>();
const containerRef = ref<HTMLElement | null>(null);
const iframeRef = ref<HTMLIFrameElement | null>(null);

const isStarted = ref(props.autoPlay);
const isLoading = ref(true);
const hasError = ref(false);
const isFullscreen = ref(false);
const isIframeFocused = ref(false);
const reloadKey = ref(0);
const tracker = ref<GameSessionTracker | null>(null);

/**
 * Focus the sandboxed game iframe so that keyboard and gamepad inputs
 * are immediately received without requiring a second click.
 */
const focusIframe = () => {
    if (!iframeRef.value) return;

    try {
        iframeRef.value.focus({ preventScroll: true });
        if (iframeRef.value.contentWindow) {
            iframeRef.value.contentWindow.focus();
        }
        isIframeFocused.value = true;
    } catch {
        // Cross-origin sandboxing may restrict contentWindow access in certain browsers;
        // focusing the iframe DOM node itself is the standard cross-origin safe fallback.
        iframeRef.value.focus();
        isIframeFocused.value = true;
    }
};

const startGame = () => {
    triggerH5GameBreak({
        type: 'start',
        name: props.game.slug,
        config: page.props.adConfig,
        afterAd: () => {
            isStarted.value = true;
            isLoading.value = true;
            hasError.value = false;
            tracker.value?.recordPlayClick();

            nextTick(() => {
                focusIframe();
            });
        },
    });
};

const onIframeLoad = () => {
    isLoading.value = false;
    hasError.value = false;
    tracker.value?.recordGameLoaded();

    // Automatically focus the iframe as soon as its documents/canvas finish loading
    focusIframe();
};

const onIframeError = () => {
    isLoading.value = false;
    hasError.value = true;
    isIframeFocused.value = false;
};

const reloadGame = () => {
    isLoading.value = true;
    hasError.value = false;
    reloadKey.value++;
    tracker.value?.recordPlayClick();

    nextTick(() => {
        focusIframe();
    });
};

const handleStageClick = (event: MouseEvent) => {
    if (!isStarted.value || isLoading.value || hasError.value) return;

    const target = event.target as HTMLElement;
    if (!target.closest('button') && !target.closest('a')) {
        focusIframe();
    }
};

const toggleFullscreen = async () => {
    if (!containerRef.value) return;

    try {
        if (!document.fullscreenElement) {
            if (containerRef.value.requestFullscreen) {
                await containerRef.value.requestFullscreen();
            } else if ((containerRef.value as any).webkitRequestFullscreen) {
                await (containerRef.value as any).webkitRequestFullscreen();
            } else if ((containerRef.value as any).msRequestFullscreen) {
                await (containerRef.value as any).msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                await document.exitFullscreen();
            } else if ((document as any).webkitExitFullscreen) {
                await (document as any).webkitExitFullscreen();
            } else if ((document as any).msExitFullscreen) {
                await (document as any).msExitFullscreen();
            }
        }
    } catch (err) {
        console.error('Fullscreen request error:', err);
    } finally {
        nextTick(() => {
            focusIframe();
        });
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
    if (isFullscreen.value) {
        nextTick(() => {
            focusIframe();
        });
    }
};

const handleWindowBlur = () => {
    // When focus moves to the sandboxed iframe, window emits a blur event
    if (document.activeElement === iframeRef.value) {
        isIframeFocused.value = true;
    }
};

const handleWindowFocus = () => {
    if (document.activeElement === iframeRef.value) {
        isIframeFocused.value = true;
    } else {
        isIframeFocused.value = false;
    }
};

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    window.addEventListener('blur', handleWindowBlur);
    window.addEventListener('focus', handleWindowFocus);

    // Initialize first-party game lifecycle analytics
    tracker.value = new GameSessionTracker(props.game.id);
    tracker.value.recordView();

    if (props.autoPlay) {
        tracker.value.recordPlayClick();
        nextTick(() => {
            focusIframe();
        });
    }
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    window.removeEventListener('blur', handleWindowBlur);
    window.removeEventListener('focus', handleWindowFocus);

    // Flush any pending gameplay session duration
    tracker.value?.destroy();
    tracker.value = null;
});
</script>

<template>
    <div
        ref="containerRef"
        class="relative w-full rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 shadow-2xl flex flex-col transition-all duration-300"
        :class="{ 'fixed inset-0 z-50 rounded-none border-none h-screen w-screen': isFullscreen }"
        @click="handleStageClick"
    >
        <!-- Top Toolbar -->
        <div class="flex items-center justify-between px-4 py-2.5 bg-slate-900/95 border-b border-slate-800 text-xs text-slate-300 z-20">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" :class="isStarted && !hasError ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                <span class="font-bold text-slate-200 tracking-wide truncate max-w-[160px] sm:max-w-md">{{ game.title }}</span>
                <span class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] uppercase font-bold bg-slate-800 text-emerald-400 rounded">
                    HTML5
                </span>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-2">
                <!-- Focus Controls Pill/Button -->
                <button
                    v-if="isStarted"
                    @click="focusIframe"
                    title="Focus Keyboard Controls"
                    aria-label="Focus Keyboard Controls"
                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all border focus:outline-none focus:ring-2 focus:ring-emerald-400"
                    :class="isIframeFocused ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-300 border-slate-700 hover:text-white hover:bg-slate-700'"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                        <path d="M6 12h4m-2-2v4m7-2h.01m3 0h.01"/>
                    </svg>
                    <span>{{ isIframeFocused ? 'Controls Active' : 'Focus Controls' }}</span>
                </button>

                <!-- Reload Button -->
                <button
                    v-if="isStarted"
                    @click="reloadGame"
                    title="Reload Game"
                    aria-label="Reload Game"
                    class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                </button>

                <!-- Fullscreen Button -->
                <button
                    @click="toggleFullscreen"
                    :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
                    :aria-label="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
                    class="flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-emerald-500 hover:text-slate-950 text-slate-200 font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400"
                >
                    <svg v-if="!isFullscreen" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/>
                    </svg>
                    <span class="hidden sm:inline">{{ isFullscreen ? 'Exit' : 'Fullscreen' }}</span>
                </button>
            </div>
        </div>

        <!-- Player Screen Area -->
        <div class="relative w-full aspect-[16/9] min-h-[340px] sm:min-h-[460px] md:min-h-[540px] bg-black flex items-center justify-center overflow-hidden">
            <!-- Click to Play Cover Screen -->
            <div
                v-if="!isStarted"
                class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-slate-950/90 text-center p-6 bg-cover bg-center"
                :style="game.thumbnail ? `background-image: linear-gradient(rgba(8, 13, 26, 0.85), rgba(8, 13, 26, 0.95)), url('${game.thumbnail}')` : 'background-image: linear-gradient(rgba(8, 13, 26, 0.85), rgba(8, 13, 26, 0.95)), url(\'/images/thumbnails/default-game.svg\')'"
            >
                <div class="max-w-md mx-auto flex flex-col items-center">
                    <button
                        @click="startGame"
                        aria-label="Start Game"
                        class="w-20 h-20 rounded-full bg-emerald-500/20 border-2 border-emerald-400 flex items-center justify-center text-emerald-400 mb-5 shadow-glow-emerald hover:scale-105 active:scale-95 transition-transform focus:outline-none focus:ring-4 focus:ring-emerald-400/50"
                    >
                        <svg class="w-9 h-9 ml-1" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="6 3 20 12 6 21 6 3" />
                        </svg>
                    </button>

                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-2">{{ game.title }}</h2>
                    <p class="text-slate-400 text-sm mb-6 line-clamp-2">
                        {{ game.description || 'Click play to start instant gameplay in your browser.' }}
                    </p>

                    <button
                        @click="startGame"
                        class="px-8 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black tracking-wide uppercase text-sm rounded-xl shadow-lg shadow-emerald-500/30 transform hover:-translate-y-0.5 active:translate-y-0 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400"
                    >
                        Play Now
                    </button>

                    <div class="mt-4 flex items-center gap-4 text-xs text-slate-400">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                            Instant Load
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            Sandboxed & Secure
                        </span>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div
                v-if="isStarted && isLoading"
                class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-slate-950/90 text-slate-300 gap-3"
            >
                <div class="w-12 h-12 rounded-full border-4 border-slate-700 border-t-emerald-400 animate-spin"></div>
                <span class="text-xs font-semibold tracking-wider uppercase text-emerald-400">Loading Game Assets...</span>
            </div>

            <!-- Error State -->
            <div
                v-if="isStarted && hasError"
                class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-slate-950/95 text-center p-6 text-slate-300 gap-4"
            >
                <div class="w-14 h-14 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center">
                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Failed to Load Game</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm">The game assets could not be loaded or the origin is unavailable.</p>
                </div>
                <button
                    @click="reloadGame"
                    class="px-5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider transition-colors"
                >
                    Retry Loading
                </button>
            </div>

            <!-- Sandboxed Game Iframe -->
            <iframe
                v-if="isStarted"
                :key="reloadKey"
                ref="iframeRef"
                :src="game.embed_url"
                :title="`${game.title} Game Player`"
                :sandbox="game.sandbox_attributes || 'allow-scripts allow-forms allow-pointer-lock'"
                tabindex="0"
                loading="eager"
                allow="fullscreen; autoplay; gamepad"
                class="w-full h-full border-0 select-none outline-none focus:outline-none"
                @load="onIframeLoad"
                @error="onIframeError"
            ></iframe>
        </div>

        <!-- Bottom Controls / Aspect Bar -->
        <div class="px-4 py-2 bg-slate-950 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5 text-slate-300">
                    <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                    <span>Desktop & Touch Ready</span>
                </span>
                <span v-if="isStarted" class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-slate-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Click game stage anytime to activate controls</span>
                </span>
            </div>
            <span class="text-slate-400 text-[11px]">
                Sandboxed HTML5 Engine
            </span>
        </div>
    </div>
</template>

