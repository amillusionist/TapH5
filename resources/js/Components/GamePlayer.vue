<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import type { Game } from '@/types';

const props = withDefaults(
    defineProps<{
        game: Game;
        autoPlay?: boolean;
    }>(),
    {
        autoPlay: false,
    }
);

const containerRef = ref<HTMLElement | null>(null);
const iframeRef = ref<HTMLIFrameElement | null>(null);

const isStarted = ref(props.autoPlay);
const isLoading = ref(true);
const hasError = ref(false);
const isFullscreen = ref(false);
const reloadKey = ref(0);

const startGame = () => {
    isStarted.value = true;
    isLoading.value = true;
    hasError.value = false;
};

const onIframeLoad = () => {
    isLoading.value = false;
    hasError.value = false;
};

const onIframeError = () => {
    isLoading.value = false;
    hasError.value = true;
};

const reloadGame = () => {
    isLoading.value = true;
    hasError.value = false;
    reloadKey.value++;
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
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
});
</script>

<template>
    <div
        ref="containerRef"
        class="relative w-full rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 shadow-2xl flex flex-col transition-all duration-300"
        :class="{ 'fixed inset-0 z-50 rounded-none border-none h-screen w-screen': isFullscreen }"
    >
        <!-- Top Toolbar -->
        <div class="flex items-center justify-between px-4 py-2.5 bg-slate-900/90 border-b border-slate-800 text-xs text-slate-300 z-20">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" :class="isStarted && !hasError ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                <span class="font-bold text-slate-200 tracking-wide truncate max-w-[200px] sm:max-w-md">{{ game.title }}</span>
                <span class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] uppercase font-bold bg-slate-800 text-emerald-400 rounded">
                    HTML5
                </span>
            </div>

            <div class="flex items-center gap-1.5">
                <!-- Reload Button -->
                <button
                    v-if="isStarted"
                    @click="reloadGame"
                    title="Reload Game"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                </button>

                <!-- Fullscreen Button -->
                <button
                    @click="toggleFullscreen"
                    :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
                    class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-emerald-500 hover:text-slate-950 text-slate-200 font-semibold transition-all focus:outline-none"
                >
                    <svg v-if="!isFullscreen" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                :style="game.thumbnail ? `background-image: linear-gradient(rgba(8, 13, 26, 0.85), rgba(8, 13, 26, 0.95)), url('${game.thumbnail}')` : ''"
            >
                <div class="max-w-md mx-auto flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full bg-emerald-500/20 border-2 border-emerald-400 flex items-center justify-center text-emerald-400 mb-5 shadow-glow-emerald animate-pulse">
                        <svg class="w-9 h-9 ml-1" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="6 3 20 12 6 21 6 3" />
                        </svg>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-2">{{ game.title }}</h2>
                    <p class="text-slate-400 text-sm mb-6 line-clamp-2">
                        {{ game.description || 'Click play to start instant gameplay in your browser.' }}
                    </p>

                    <button
                        @click="startGame"
                        class="px-8 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black tracking-wide uppercase text-sm rounded-xl shadow-lg shadow-emerald-500/30 transform hover:-translate-y-0.5 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400"
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
                :sandbox="game.sandbox_attributes || 'allow-scripts allow-same-origin allow-forms allow-pointer-lock'"
                allow="fullscreen; autoplay; gamepad"
                class="w-full h-full border-0 select-none"
                @load="onIframeLoad"
                @error="onIframeError"
            ></iframe>
        </div>

        <!-- Bottom Controls / Aspect Bar -->
        <div class="px-4 py-2 bg-slate-950 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <span>Desktop & Touch Ready</span>
            </span>
            <span class="text-slate-400 text-[11px]">
                Powered by TapH5 Engine
            </span>
        </div>
    </div>
</template>

