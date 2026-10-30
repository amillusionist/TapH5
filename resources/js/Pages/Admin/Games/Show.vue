<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import type { Game } from '@/types';

const props = defineProps<{
    game: Game;
}>();

const isPublishing = ref(false);
const isArchiving = ref(false);
const isPublishModalOpen = ref(false);
const isArchiveModalOpen = ref(false);
const showRawJson = ref(false);
const copiedFingerprint = ref(false);

const openPublishModal = () => {
    isPublishModalOpen.value = true;
};

const confirmPublish = () => {
    isPublishing.value = true;
    router.post(route('admin.games.publish', props.game.id), {}, {
        onFinish: () => {
            isPublishing.value = false;
            isPublishModalOpen.value = false;
        },
    });
};

const openArchiveModal = () => {
    isArchiveModalOpen.value = true;
};

const confirmArchive = () => {
    isArchiving.value = true;
    router.post(route('admin.games.archive', props.game.id), {}, {
        onFinish: () => {
            isArchiving.value = false;
            isArchiveModalOpen.value = false;
        },
    });
};

const copyFingerprint = async () => {
    if (!props.game.fingerprint) return;
    try {
        await navigator.clipboard.writeText(props.game.fingerprint);
        copiedFingerprint.value = true;
        setTimeout(() => {
            copiedFingerprint.value = false;
        }, 2000);
    } catch {
        // fallback ignored
    }
};
</script>

<template>
    <AdminLayout :title="`Game Review: ${game.title}`">
        <div class="space-y-6 max-w-6xl mx-auto">
            <!-- Header with Breadcrumbs & Actions -->
            <AdminPageHeader
                :title="game.title"
                :description="`ID: #${game.id} • Slug: ${game.slug}`"
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Games', href: route('admin.games.index') },
                    { label: game.title },
                ]"
            >
                <template #badge>
                    <StatusBadge :status="game.status" size="md" />
                </template>

                <template #actions>
                    <!-- Publish Button (For Drafts) -->
                    <button
                        v-if="game.status === 'draft'"
                        @click="openPublishModal"
                        :disabled="isPublishing"
                        class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-emerald-500/20 transition-all disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Approve & Publish</span>
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

                    <!-- Archive Button (For Published) -->
                    <button
                        v-if="game.status === 'published'"
                        @click="openArchiveModal"
                        :disabled="isArchiving"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-colors disabled:opacity-50"
                    >
                        Archive
                    </button>

                    <!-- Edit Button -->
                    <Link
                        :href="route('admin.games.edit', game.id)"
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke-width="2" stroke-linecap="round"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Edit Metadata</span>
                    </Link>
                </template>
            </AdminPageHeader>

            <!-- Two-Column Review Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Content Overview & Diagnostic Architecture -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- General Content Card -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-5">
                        <div class="border-b border-slate-800/60 pb-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                Game Overview & Instructions
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Content surfaced to players on the game detail page.
                            </p>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div>
                                <span class="text-slate-400 font-semibold block mb-1.5 uppercase text-[10px] tracking-wider">Description</span>
                                <div class="text-slate-200 leading-relaxed bg-slate-900/60 p-4 rounded-xl border border-slate-800/60">
                                    {{ game.description || 'No description provided.' }}
                                </div>
                            </div>

                            <div>
                                <span class="text-slate-400 font-semibold block mb-1.5 uppercase text-[10px] tracking-wider">Gameplay Instructions</span>
                                <div class="text-slate-200 leading-relaxed bg-slate-900/60 p-4 rounded-xl border border-slate-800/60">
                                    {{ game.instructions || 'No instructions provided.' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filesystem & Scanner Diagnostics Card -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-5">
                        <div class="border-b border-slate-800/60 pb-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                Filesystem & Discovery Engine Diagnostics
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Single-domain paths, entry file validation, and cryptographic identity.
                            </p>
                        </div>

                        <!-- 4 Metric Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/60 space-y-1">
                                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Storage Directory</span>
                                <span class="font-mono text-slate-200 text-xs break-all">{{ game.storage_path || 'Unassigned' }}</span>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/60 space-y-1">
                                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Entry File</span>
                                <span class="font-mono text-emerald-400 text-xs font-semibold">{{ game.entry_file }}</span>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/60 space-y-1">
                                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Game Architecture</span>
                                <span class="font-mono text-slate-200 text-xs">{{ game.game_type }}</span>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/60 space-y-1">
                                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Recorded Plays</span>
                                <span class="font-bold text-slate-100 text-xs">{{ game.plays_count.toLocaleString() }} sessions</span>
                            </div>
                        </div>

                        <!-- Cryptographic Fingerprint Box -->
                        <div v-if="game.fingerprint" class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Cryptographic Fingerprint</span>
                                <code class="text-xs font-mono text-slate-300 break-all select-all">{{ game.fingerprint }}</code>
                            </div>
                            <button
                                @click="copyFingerprint"
                                class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold shrink-0 transition-colors border border-slate-800"
                            >
                                {{ copiedFingerprint ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>

                        <!-- Scan Metadata Payload (Collapsible) -->
                        <div v-if="game.scan_metadata" class="space-y-2 pt-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-300">Scan Metadata Payload</span>
                                <button
                                    @click="showRawJson = !showRawJson"
                                    class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1 transition-colors"
                                >
                                    <span>{{ showRawJson ? 'Hide Raw JSON' : 'Inspect Raw JSON' }}</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="showRawJson ? 'rotate-180' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>

                            <div v-if="showRawJson" class="p-4 rounded-xl bg-slate-950 font-mono text-[11px] text-slate-300 overflow-x-auto border border-slate-800/80 max-h-72">
                                <pre>{{ JSON.stringify(game.scan_metadata, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right 1 Col: Media, Taxonomies & Curation -->
                <div class="space-y-6">
                    <!-- Thumbnail Preview Card -->
                    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Thumbnail Asset</h4>
                            <span class="text-[10px] font-mono text-slate-400">4:3 Aspect</span>
                        </div>

                        <div class="aspect-[4/3] rounded-xl overflow-hidden bg-slate-900 border border-slate-800 flex items-center justify-center relative group">
                            <img
                                :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                :alt="game.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        </div>
                        <p class="text-[11px] text-slate-400 font-mono break-all bg-slate-900/60 p-2.5 rounded-lg border border-slate-800/60">
                            {{ game.thumbnail || 'Default placeholder' }}
                        </p>
                    </div>

                    <!-- Taxonomies Card -->
                    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4 text-xs">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Taxonomies</h4>

                        <div class="pb-3 border-b border-slate-800/60">
                            <span class="text-slate-400 text-[11px] block mb-1">Primary Category</span>
                            <span v-if="game.category" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 font-medium">
                                {{ game.category.name }}
                            </span>
                            <span v-else class="text-slate-500 italic">None assigned</span>
                        </div>

                        <div>
                            <span class="text-slate-400 text-[11px] block mb-2">Assigned Tags</span>
                            <div v-if="game.tags && game.tags.length > 0" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="tag in game.tags"
                                    :key="tag.id"
                                    class="px-2.5 py-1 text-[11px] font-semibold bg-slate-800/80 text-slate-300 rounded-lg border border-slate-700/60"
                                >
                                    #{{ tag.name }}
                                </span>
                            </div>
                            <span v-else class="text-slate-500 italic">No tags assigned</span>
                        </div>
                    </div>

                    <!-- Curation & Promotion Flags -->
                    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3 text-xs">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Curation Flags</h4>

                        <div class="flex items-center justify-between pb-2 border-b border-slate-800/60">
                            <span class="text-slate-400">Featured Game</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="game.is_featured ? 'bg-purple-500/10 text-purple-400 border border-purple-500/30' : 'text-slate-500'"
                            >
                                {{ game.is_featured ? 'Active' : 'Off' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between pb-2 border-b border-slate-800/60">
                            <span class="text-slate-400">Trending Now</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="game.is_trending ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'text-slate-500'"
                            >
                                {{ game.is_trending ? 'Active' : 'Off' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Popular Catalog</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="game.is_popular ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30' : 'text-slate-500'"
                            >
                                {{ game.is_popular ? 'Active' : 'Off' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Approve & Publish Modal -->
        <ConfirmModal
            :show="isPublishModalOpen"
            title="Approve & Publish Game"
            :message="`Are you sure you want to approve and publish '${game.title}' to the public live portal? Visitors will immediately be able to play and discover this game.`"
            confirm-text="Approve & Publish"
            variant="primary"
            :loading="isPublishing"
            @confirm="confirmPublish"
            @close="isPublishModalOpen = false"
        />

        <!-- Confirm Archive Modal -->
        <ConfirmModal
            :show="isArchiveModalOpen"
            title="Archive Game"
            :message="`Are you sure you want to archive '${game.title}'? The game will be immediately hidden from public navigation and searches, but its files will NOT be deleted from disk.`"
            confirm-text="Archive Game"
            variant="warning"
            :loading="isArchiving"
            @confirm="confirmArchive"
            @close="isArchiveModalOpen = false"
        />
    </AdminLayout>
</template>
