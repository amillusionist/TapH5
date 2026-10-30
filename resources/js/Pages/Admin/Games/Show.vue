<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Game } from '@/types';

const props = defineProps<{
    game: Game;
}>();

const isPublishing = ref(false);
const isArchiving = ref(false);

const publishGame = () => {
    if (!confirm(`Are you sure you want to approve and publish "${props.game.title}" to the live portal?`)) {
        return;
    }
    isPublishing.value = true;
    router.post(route('admin.games.publish', props.game.id), {}, {
        onFinish: () => {
            isPublishing.value = false;
        },
    });
};

const archiveGame = () => {
    if (!confirm(`Are you sure you want to archive "${props.game.title}"? The game will be hidden from the public portal, but files will NOT be deleted.`)) {
        return;
    }
    isArchiving.value = true;
    router.post(route('admin.games.archive', props.game.id), {}, {
        onFinish: () => {
            isArchiving.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout :title="`Game Review: ${game.title}`">
        <div class="space-y-6 max-w-5xl">
            <!-- Top Action Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.games.index')"
                        class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors"
                        title="Back to Games List"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                            <span>{{ game.title }}</span>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide border"
                                :class="{
                                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/30': game.status === 'published',
                                    'bg-amber-500/10 text-amber-400 border-amber-500/30': game.status === 'draft',
                                    'bg-slate-700/20 text-slate-400 border-slate-700/50': game.status === 'archived',
                                }"
                            >
                                {{ game.status }}
                            </span>
                        </h2>
                        <p class="text-xs text-slate-400 font-mono mt-0.5">ID: #{{ game.id }} • Slug: {{ game.slug }}</p>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Publish Button (For Drafts) -->
                    <button
                        v-if="game.status === 'draft'"
                        @click="publishGame"
                        :disabled="isPublishing"
                        class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-emerald-500/20 transition-all disabled:opacity-50"
                    >
                        {{ isPublishing ? 'Publishing...' : 'Approve & Publish' }}
                    </button>

                    <!-- Public URL Shortcut (For Published) -->
                    <a
                        v-if="game.status === 'published'"
                        :href="`/games/${game.slug}`"
                        target="_blank"
                        class="px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" stroke-width="2" stroke-linecap="round"/>
                            <polyline points="15 3 21 3 21 9" stroke-width="2" stroke-linecap="round"/>
                            <line x1="10" y1="14" x2="21" y2="3" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>View Live</span>
                    </a>

                    <!-- Archive Button -->
                    <button
                        v-if="game.status === 'published'"
                        @click="archiveGame"
                        :disabled="isArchiving"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-colors disabled:opacity-50"
                    >
                        {{ isArchiving ? 'Archiving...' : 'Archive Game' }}
                    </button>

                    <!-- Edit Button -->
                    <Link
                        :href="route('admin.games.edit', game.id)"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition-colors"
                    >
                        Edit Metadata
                    </Link>
                </div>
            </div>

            <!-- Two-Column Review Layout -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Specifications, Instructions, Scan Metadata -->
                <div class="md:col-span-2 space-y-6">
                    <!-- General Information Card -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Game Overview</h3>

                        <div class="space-y-3 text-xs">
                            <div>
                                <span class="text-slate-400 block mb-1">Description</span>
                                <p class="text-slate-200 leading-relaxed bg-slate-900/60 p-3 rounded-xl border border-slate-800/60">
                                    {{ game.description || 'No description provided.' }}
                                </p>
                            </div>

                            <div>
                                <span class="text-slate-400 block mb-1">Gameplay Instructions</span>
                                <p class="text-slate-200 leading-relaxed bg-slate-900/60 p-3 rounded-xl border border-slate-800/60">
                                    {{ game.instructions || 'No instructions provided.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Scan & Filesystem Details -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Filesystem & Scanner Diagnostics</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/60">
                                <span class="text-slate-400 block text-[11px]">Storage Path</span>
                                <span class="font-mono text-slate-200 break-all">{{ game.storage_path || 'Unassigned' }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/60">
                                <span class="text-slate-400 block text-[11px]">Entry File</span>
                                <span class="font-mono text-emerald-400">{{ game.entry_file }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/60">
                                <span class="text-slate-400 block text-[11px]">Game Type</span>
                                <span class="font-mono text-slate-200">{{ game.game_type }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/60">
                                <span class="text-slate-400 block text-[11px]">Recorded Plays</span>
                                <span class="font-bold text-slate-200">{{ game.plays_count.toLocaleString() }}</span>
                            </div>
                        </div>

                        <!-- Scan Metadata (JSON) Details if present -->
                        <div v-if="game.scan_metadata" class="space-y-2 pt-2">
                            <span class="text-xs font-bold text-slate-400 block">Scan Metadata Payload:</span>
                            <div class="p-4 rounded-xl bg-slate-950 font-mono text-[11px] text-slate-300 overflow-x-auto border border-slate-800/60 max-h-60">
                                <pre>{{ JSON.stringify(game.scan_metadata, null, 2) }}</pre>
                            </div>
                        </div>

                        <!-- Fingerprint -->
                        <div v-if="game.fingerprint" class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/60 text-xs font-mono">
                            <span class="text-slate-400 block text-[11px]">Cryptographic Fingerprint</span>
                            <span class="text-slate-300 break-all">{{ game.fingerprint }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right 1 Col: Media, Taxonomies & Flags -->
                <div class="space-y-6">
                    <!-- Thumbnail Preview -->
                    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Thumbnail Preview</h4>
                        <div class="aspect-[4/3] rounded-xl overflow-hidden bg-slate-900 border border-slate-800 flex items-center justify-center">
                            <img
                                :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                :alt="game.title"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <p class="text-[11px] text-slate-400 font-mono break-all">{{ game.thumbnail }}</p>
                    </div>

                    <!-- Category & Tags -->
                    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3 text-xs">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Taxonomies</h4>

                        <div class="pb-2 border-b border-slate-800/60">
                            <span class="text-slate-400 block mb-1">Category</span>
                            <span v-if="game.category" class="font-semibold text-slate-200">
                                {{ game.category.name }}
                            </span>
                            <span v-else class="text-slate-500 italic">None assigned</span>
                        </div>

                        <div>
                            <span class="text-slate-400 block mb-2">Assigned Tags</span>
                            <div v-if="game.tags && game.tags.length > 0" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="tag in game.tags"
                                    :key="tag.id"
                                    class="px-2.5 py-1 text-[11px] font-semibold bg-slate-800 text-slate-300 rounded-lg border border-slate-700/60"
                                >
                                    #{{ tag.name }}
                                </span>
                            </div>
                            <span v-else class="text-slate-500 italic">No tags assigned</span>
                        </div>
                    </div>

                    <!-- Curation Flags -->
                    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-2 text-xs">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Curation Status</h4>

                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-800/60">
                            <span class="text-slate-400">Featured</span>
                            <span :class="game.is_featured ? 'text-emerald-400 font-bold' : 'text-slate-500'">
                                {{ game.is_featured ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-800/60">
                            <span class="text-slate-400">Trending</span>
                            <span :class="game.is_trending ? 'text-amber-400 font-bold' : 'text-slate-500'">
                                {{ game.is_trending ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Popular</span>
                            <span :class="game.is_popular ? 'text-cyan-400 font-bold' : 'text-slate-500'">
                                {{ game.is_popular ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

