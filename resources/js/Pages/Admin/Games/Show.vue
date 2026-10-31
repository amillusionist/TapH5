<script setup lang="ts">
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import type { Game, StagingFolderInfo } from '@/types';

const props = defineProps<{
    game: Game;
    fileStatus?: {
        entry_file_exists: boolean;
        thumbnail_exists: boolean;
    };
    stagingFolders?: StagingFolderInfo[];
}>();

const isPublishing = ref(false);
const isArchiving = ref(false);
const isPublishModalOpen = ref(false);
const isArchiveModalOpen = ref(false);
const showRawJson = ref(false);
const copiedFingerprint = ref(false);

// Operations & Maintenance modals
const isRevalidating = ref(false);
const revalidationResult = ref<any | null>(null);
const isRevalidateModalOpen = ref(false);

const isSubmitReviewModalOpen = ref(false);
const submitReviewForm = useForm({
    notes: '',
});

const isReplacePackageModalOpen = ref(false);
const replacePackageForm = useForm({
    staging_folder: '',
});

const rescanGame = () => {
    router.post(route('admin.games.rescan', props.game.id), {}, { preserveScroll: true });
};

const repairMetadata = () => {
    router.post(route('admin.games.repair_metadata', props.game.id), {}, { preserveScroll: true });
};

const regenerateThumbnail = () => {
    router.post(route('admin.games.regenerate_thumbnail', props.game.id), {}, { preserveScroll: true });
};

const refreshMetadata = () => {
    router.post(route('admin.games.refresh_metadata', props.game.id), {}, { preserveScroll: true });
};

const runRevalidate = async () => {
    isRevalidating.value = true;
    isRevalidateModalOpen.value = true;
    try {
        const res = await fetch(route('admin.games.revalidate', props.game.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        revalidationResult.value = data;
    } catch {
        revalidationResult.value = { is_valid: false, errors: ['Failed to execute revalidation.'] };
    } finally {
        isRevalidating.value = false;
    }
};

const submitForReview = () => {
    submitReviewForm.post(route('admin.game_reviews.submit', props.game.id), {
        onSuccess: () => {
            isSubmitReviewModalOpen.value = false;
            submitReviewForm.reset();
        },
    });
};

const executeReplacePackage = () => {
    if (!replacePackageForm.staging_folder) return;
    replacePackageForm.post(route('admin.games.replace_package', props.game.id), {
        onSuccess: () => {
            isReplacePackageModalOpen.value = false;
            replacePackageForm.reset();
        },
    });
};

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

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

const formatActionName = (action: string) => {
    switch (action) {
        case 'submit': return 'Submitted for Review';
        case 'approve': return 'Approved';
        case 'approve_and_publish': return 'Approved & Published';
        case 'request_changes': return 'Changes Requested';
        case 'reject': return 'Rejected';
        case 'return_to_draft': return 'Returned to Draft';
        default: return action;
    }
};
</script>

<template>
    <AdminLayout :title="`Game: ${game.title}`">
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
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="game.status" size="md" />
                        <StatusBadge v-if="game.status === 'draft'" :status="game.review_status || 'not_submitted'" size="md" />
                    </div>
                </template>

                <template #actions>
                    <!-- Submit for Review Button (When Draft & Not In Active Review) -->
                    <button
                        v-if="game.status === 'draft' && (!game.review_status || ['not_submitted', 'changes_requested', 'rejected'].includes(game.review_status))"
                        @click="isSubmitReviewModalOpen = true"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-amber-500/20 transition-all flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Submit for Review</span>
                    </button>

                    <!-- Link to Review Queue if Pending -->
                    <Link
                        v-if="game.status === 'draft' && game.review_status === 'pending'"
                        :href="route('admin.game_reviews.show', game.id)"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Open in Review Queue</span>
                    </Link>

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

            <!-- Game Operations & Maintenance Toolbar -->
            <div class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div>
                    <h3 class="font-bold text-white text-xs uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Game Maintenance Operations</span>
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Deep package inspection, thumbnail extraction, metadata sync, and transactional package replacement.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Rescan Button -->
                    <button
                        type="button"
                        @click="rescanGame"
                        title="Re-validate entry file and file structure"
                        class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700/80 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Rescan Files</span>
                    </button>

                    <!-- Revalidate Button -->
                    <button
                        type="button"
                        @click="runRevalidate"
                        title="Run full diagnostic suite on package"
                        class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700/80 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Revalidate</span>
                    </button>

                    <!-- Regenerate Thumbnail -->
                    <button
                        type="button"
                        @click="regenerateThumbnail"
                        title="Find thumbnail image in package and update game thumbnail"
                        class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700/80 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Regenerate Thumbnail</span>
                    </button>

                    <!-- Refresh Metadata -->
                    <button
                        type="button"
                        @click="refreshMetadata"
                        title="Re-extract HTML title, description, and metadata"
                        class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700/80 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Refresh Metadata</span>
                    </button>

                    <!-- Replace Package -->
                    <button
                        type="button"
                        @click="isReplacePackageModalOpen = true"
                        title="Replace game files from a staging package"
                        class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>Replace Package</span>
                    </button>
                </div>
            </div>

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
                                    class="text-[11px] font-semibold text-emerald-400 hover:underline"
                                >
                                    {{ showRawJson ? 'Hide Raw Details' : 'View Raw Details' }}
                                </button>
                            </div>

                            <pre
                                v-if="showRawJson"
                                class="p-4 rounded-xl bg-slate-950 text-slate-300 font-mono text-[11px] overflow-x-auto border border-slate-800/80 max-h-60"
                            >{{ JSON.stringify(game.scan_metadata, null, 2) }}</pre>
                        </div>
                    </div>

                    <!-- Review History Timeline (If Any) -->
                    <div v-if="game.reviews && game.reviews.length > 0" class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4 text-xs">
                        <div class="border-b border-slate-800/60 pb-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                Review & Decision History
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Audit log of staff submissions, evaluations, and state changes.
                            </p>
                        </div>

                        <div class="space-y-3 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-800">
                            <div
                                v-for="review in game.reviews"
                                :key="review.id"
                                class="relative pl-8 space-y-1"
                            >
                                <span class="absolute left-1.5 top-1.5 w-3 h-3 rounded-full bg-indigo-500 ring-4 ring-[#0a0f1d]"></span>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-white text-xs">
                                        {{ formatActionName(review.action) }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ formatDate(review.created_at) }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    By <strong class="text-slate-300">{{ review.user?.name || 'Staff' }}</strong>
                                    <span class="text-slate-500"> ({{ review.from_status }} → {{ review.to_status }})</span>
                                </div>
                                <p v-if="review.notes" class="text-slate-300 text-xs bg-slate-900/80 p-2.5 rounded-lg border border-slate-800/60 mt-1">
                                    {{ review.notes }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Col: Visual Presentation & Taxonomy -->
                <div class="space-y-6">
                    <!-- Media Asset Card -->
                    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Game Presentation Media</h4>

                        <div class="aspect-[4/3] rounded-xl overflow-hidden bg-slate-950 border border-slate-800/80 relative group">
                            <img
                                :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                :alt="game.title"
                                class="w-full h-full object-cover"
                            />
                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center p-4">
                                <span class="text-[11px] font-mono text-slate-300 break-all text-center">
                                    {{ game.thumbnail || 'Default Thumbnail Used' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Category & Tags Card -->
                    <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4 text-xs">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Classification</h4>

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

        <!-- Revalidate Modal -->
        <div v-if="isRevalidateModalOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="w-full max-w-lg bg-[#0a0f1d] border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
                    <h3 class="font-bold text-white text-base">Package Validation Results</h3>
                    <button @click="isRevalidateModalOpen = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <div v-if="isRevalidating" class="py-12 text-center text-slate-400 space-y-3">
                    <div class="w-8 h-8 mx-auto border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-xs">Running validation suite...</p>
                </div>

                <div v-else-if="revalidationResult" class="space-y-4 text-xs">
                    <div
                        class="p-3.5 rounded-xl border flex items-center gap-3"
                        :class="revalidationResult.is_valid ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border-rose-500/30 text-rose-300'"
                    >
                        <span class="font-black text-sm">{{ revalidationResult.is_valid ? '✓ Passed' : '✕ Issues Found' }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800">
                            <span class="text-slate-500 text-[10px] uppercase block font-bold">Size</span>
                            <span class="font-mono text-slate-200 font-semibold">{{ revalidationResult.size_formatted }}</span>
                        </div>
                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800">
                            <span class="text-slate-500 text-[10px] uppercase block font-bold">Files</span>
                            <span class="font-mono text-slate-200 font-semibold">{{ revalidationResult.file_count }}</span>
                        </div>
                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800">
                            <span class="text-slate-500 text-[10px] uppercase block font-bold">Entry File</span>
                            <span class="font-mono text-emerald-400 font-semibold truncate block">{{ revalidationResult.entry_file }}</span>
                        </div>
                    </div>

                    <div v-if="revalidationResult.errors?.length > 0" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 space-y-1">
                        <span class="font-bold block">Errors:</span>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li v-for="(err, i) in revalidationResult.errors" :key="i">{{ err }}</li>
                        </ul>
                    </div>

                    <div v-if="revalidationResult.warnings?.length > 0" class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 space-y-1">
                        <span class="font-bold block">Warnings:</span>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li v-for="(warn, i) in revalidationResult.warnings" :key="i">{{ warn }}</li>
                        </ul>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        type="button"
                        @click="isRevalidateModalOpen = false"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Replace Package Modal -->
        <div v-if="isReplacePackageModalOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-[#0a0f1d] border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
                <h3 class="font-bold text-white text-base">Replace Game Package</h3>
                <p class="text-xs text-slate-400">
                    Replace files for <strong class="text-white">{{ game.title }}</strong> from a staging folder. An atomic backup is created automatically, rolling back safely if replacement fails.
                </p>

                <div class="space-y-1.5 text-xs">
                    <label class="block font-semibold text-slate-300 uppercase text-[10px] tracking-wider">
                        Select Staging Source Folder <span class="text-rose-400">*</span>
                    </label>
                    <select
                        v-model="replacePackageForm.staging_folder"
                        class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                    >
                        <option value="" disabled>Choose a staging folder...</option>
                        <option
                            v-for="folder in stagingFolders"
                            :key="folder.folder"
                            :value="folder.folder"
                        >
                            {{ folder.folder }} ({{ folder.size_formatted }}, {{ folder.file_count }} files)
                        </option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="isReplacePackageModalOpen = false"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="replacePackageForm.processing || !replacePackageForm.staging_folder"
                        @click="executeReplacePackage"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition-colors disabled:opacity-50"
                    >
                        Replace Package Files
                    </button>
                </div>
            </div>
        </div>

        <!-- Submit for Review Modal -->
        <div v-if="isSubmitReviewModalOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-[#0a0f1d] border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
                <h3 class="font-bold text-white text-base">Submit Game for Staff Review</h3>
                <p class="text-xs text-slate-400">
                    This will place <strong class="text-white">{{ game.title }}</strong> into the Game Review Queue with status 'pending' for review by staff.
                </p>

                <div class="space-y-1.5 text-xs">
                    <label class="block font-semibold text-slate-300">Submission Notes (Optional)</label>
                    <textarea
                        v-model="submitReviewForm.notes"
                        rows="3"
                        class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                        placeholder="Notes on controls, gameplay test, or recent fixes..."
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="isSubmitReviewModalOpen = false"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="submitReviewForm.processing"
                        @click="submitForReview"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl transition-colors disabled:opacity-50"
                    >
                        Submit to Queue
                    </button>
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
