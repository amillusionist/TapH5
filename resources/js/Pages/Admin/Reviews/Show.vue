<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import type { Game, GameReview } from '@/types';

const props = defineProps<{
    game: Game;
    validation: {
        is_valid: boolean;
        entry_file: string;
        file_count: number;
        size_bytes: number;
        size_formatted: string;
        errors: string[];
        warnings: string[];
    };
    duplicates: {
        has_duplicate: boolean;
        matches: Array<{
            id: number;
            title: string;
            slug: string;
            status: string;
        }>;
    };
    reviewHistory: GameReview[];
    canPublish: boolean;
    previewUrl: string;
}>();

// Modals state
const isApproveModalOpen = ref(false);
const isPublishModalOpen = ref(false);
const isRequestChangesModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isReturnToDraftModalOpen = ref(false);

// Iframe preview viewport controls
const viewportMode = ref<'desktop' | 'tablet' | 'mobile'>('desktop');
const iframeKey = ref(1);

const reloadIframe = () => {
    iframeKey.value++;
};

// Forms for each decision
const approveForm = useForm({
    notes: '',
});

const publishForm = useForm({
    notes: '',
});

const requestChangesForm = useForm({
    notes: '',
});

const rejectForm = useForm({
    reason: '',
});

const returnToDraftForm = useForm({
    notes: '',
});

const submitApprove = () => {
    approveForm.post(route('admin.game_reviews.approve', props.game.id), {
        onSuccess: () => {
            isApproveModalOpen.value = false;
        },
    });
};

const submitPublish = () => {
    publishForm.post(route('admin.game_reviews.publish', props.game.id), {
        onSuccess: () => {
            isPublishModalOpen.value = false;
        },
    });
};

const submitRequestChanges = () => {
    requestChangesForm.post(route('admin.game_reviews.request_changes', props.game.id), {
        onSuccess: () => {
            isRequestChangesModalOpen.value = false;
        },
    });
};

const submitReject = () => {
    rejectForm.post(route('admin.game_reviews.reject', props.game.id), {
        onSuccess: () => {
            isRejectModalOpen.value = false;
        },
    });
};

const submitReturnToDraft = () => {
    returnToDraftForm.post(route('admin.game_reviews.return_to_draft', props.game.id), {
        onSuccess: () => {
            isReturnToDraftModalOpen.value = false;
        },
    });
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
    <AdminLayout :title="`Review: ${game.title}`">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <AdminPageHeader
                :title="`Review: ${game.title}`"
                :description="`ID: #${game.id} • Slug: ${game.slug} • Storage: ${game.storage_path || 'staging'}`"
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Game Reviews', href: route('admin.game_reviews.index') },
                    { label: game.title },
                ]"
            >
                <template #badge>
                    <StatusBadge :status="game.review_status || 'not_submitted'" size="md" />
                </template>

                <template #actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Request Changes Button -->
                        <button
                            type="button"
                            @click="isRequestChangesModalOpen = true"
                            class="px-3.5 py-2 rounded-xl bg-orange-500/15 hover:bg-orange-500/25 text-orange-300 border border-orange-500/30 text-xs font-bold transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Request Changes</span>
                        </button>

                        <!-- Reject Button -->
                        <button
                            type="button"
                            @click="isRejectModalOpen = true"
                            class="px-3.5 py-2 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 border border-rose-500/30 text-xs font-bold transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>Reject</span>
                        </button>

                        <!-- Approve Only (Content Manager / Admin) -->
                        <button
                            type="button"
                            @click="isApproveModalOpen = true"
                            class="px-4 py-2 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 text-xs font-bold transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Approve</span>
                        </button>

                        <!-- Approve & Publish (Admin / Super Admin Only) -->
                        <button
                            v-if="canPublish"
                            type="button"
                            @click="isPublishModalOpen = true"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 text-xs font-black uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Approve & Publish</span>
                        </button>

                        <!-- Return to Draft -->
                        <button
                            v-if="game.review_status !== 'not_submitted'"
                            type="button"
                            @click="isReturnToDraftModalOpen = true"
                            class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 text-xs font-semibold border border-slate-700 transition-all"
                        >
                            Draft
                        </button>
                    </div>
                </template>
            </AdminPageHeader>

            <!-- Main Content Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Sandboxed Game Preview & Validation Report -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Sandboxed Player Preview Card -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/60 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                    <span>Live Sandboxed Preview</span>
                                    <span class="text-[10px] font-mono font-normal text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded">
                                        CSP Isolated
                                    </span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Iframe runs in a sandbox with restricted origins to safeguard admin sessions.
                                </p>
                            </div>

                            <!-- Viewport toggles & Reload -->
                            <div class="flex items-center gap-2">
                                <div class="bg-slate-900 border border-slate-800 rounded-lg p-0.5 flex text-xs">
                                    <button
                                        type="button"
                                        @click="viewportMode = 'desktop'"
                                        :class="viewportMode === 'desktop' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                        class="px-2 py-1 rounded transition-colors text-[11px]"
                                    >
                                        Desktop
                                    </button>
                                    <button
                                        type="button"
                                        @click="viewportMode = 'tablet'"
                                        :class="viewportMode === 'tablet' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                        class="px-2 py-1 rounded transition-colors text-[11px]"
                                    >
                                        Tablet (768)
                                    </button>
                                    <button
                                        type="button"
                                        @click="viewportMode = 'mobile'"
                                        :class="viewportMode === 'mobile' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                        class="px-2 py-1 rounded transition-colors text-[11px]"
                                    >
                                        Mobile (375)
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="reloadIframe"
                                    title="Reload preview"
                                    class="p-1.5 bg-slate-900 hover:bg-slate-800 text-slate-300 rounded-lg border border-slate-800 transition-colors"
                                >
                                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Iframe Container -->
                        <div class="bg-black/90 rounded-xl border border-slate-800 p-2 flex items-center justify-center min-h-[420px]">
                            <div
                                :class="[
                                    'transition-all duration-300 overflow-hidden rounded-lg bg-black shadow-2xl relative',
                                    viewportMode === 'desktop' ? 'w-full h-[520px]' : '',
                                    viewportMode === 'tablet' ? 'w-[768px] max-w-full h-[520px]' : '',
                                    viewportMode === 'mobile' ? 'w-[375px] max-w-full h-[540px]' : '',
                                ]"
                            >
                                <iframe
                                    :key="iframeKey"
                                    :src="previewUrl"
                                    class="w-full h-full border-0"
                                    sandbox="allow-scripts allow-forms allow-pointer-lock"
                                    loading="lazy"
                                    title="Game Sandbox Preview"
                                ></iframe>
                            </div>
                        </div>
                    </div>

                    <!-- Technical Validation Diagnostics Card -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                        <div class="border-b border-slate-800/60 pb-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                Technical Package Validation
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Automated checks for entry file, asset references, and duplicate fingerprints.
                            </p>
                        </div>

                        <!-- Status badge & quick metrics -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Integrity</span>
                                <span
                                    :class="validation.is_valid ? 'text-emerald-400' : 'text-rose-400'"
                                    class="font-bold text-sm"
                                >
                                    {{ validation.is_valid ? 'Valid Package' : 'Errors Found' }}
                                </span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Package Size</span>
                                <span class="font-mono text-slate-200 font-semibold text-sm">{{ validation.size_formatted }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Files Count</span>
                                <span class="font-mono text-slate-200 font-semibold text-sm">{{ validation.file_count }} files</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Entry File</span>
                                <span class="font-mono text-emerald-400 font-semibold text-sm">{{ validation.entry_file }}</span>
                            </div>
                        </div>

                        <!-- Duplicate Warning -->
                        <div
                            v-if="duplicates.has_duplicate"
                            class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs space-y-1"
                        >
                            <div class="font-bold flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Potential Duplicate Detected
                            </div>
                            <p>
                                Fingerprint matches existing game(s):
                                <span v-for="match in duplicates.matches" :key="match.id" class="font-bold">
                                    {{ match.title }} ({{ match.slug }})
                                </span>
                            </p>
                        </div>

                        <!-- Errors & Warnings List -->
                        <div v-if="validation.errors.length > 0" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                            <span class="font-bold block">Validation Errors:</span>
                            <ul class="list-disc list-inside space-y-0.5">
                                <li v-for="(err, i) in validation.errors" :key="i">{{ err }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Right Col: Metadata Overview & Review History Timeline -->
                <div class="space-y-6">
                    <!-- Game Metadata Summary -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4 text-xs">
                        <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                Game Metadata
                            </h3>
                            <Link
                                :href="route('admin.games.edit', game.id)"
                                class="text-indigo-400 hover:text-indigo-300 font-semibold"
                            >
                                Edit Metadata
                            </Link>
                        </div>

                        <div class="flex items-center gap-3">
                            <img
                                :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                :alt="game.title"
                                class="w-16 h-12 rounded-xl object-cover bg-slate-900 border border-slate-800 shrink-0"
                            />
                            <div class="min-w-0">
                                <h4 class="font-bold text-white text-sm truncate">{{ game.title }}</h4>
                                <span class="text-slate-400 font-mono text-[11px] truncate block">/games/{{ game.slug }}</span>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-slate-800/60">
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Category</span>
                                <span class="text-slate-200">{{ game.category?.name || 'Unassigned' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Tags</span>
                                <div v-if="game.tags && game.tags.length > 0" class="flex flex-wrap gap-1 mt-1">
                                    <span
                                        v-for="tag in game.tags"
                                        :key="tag.id"
                                        class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-slate-300"
                                    >
                                        {{ tag.name }}
                                    </span>
                                </div>
                                <span v-else class="text-slate-500 italic">No tags assigned</span>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Description</span>
                                <p class="text-slate-300 text-xs line-clamp-3">{{ game.description || 'No description' }}</p>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Instructions</span>
                                <p class="text-slate-300 text-xs line-clamp-3">{{ game.instructions || 'No instructions' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Review History Timeline -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4 text-xs">
                        <div class="border-b border-slate-800/60 pb-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                Review History & Audit Trail
                            </h3>
                            <p class="text-[11px] text-slate-400">
                                Complete timeline of submission and review events.
                            </p>
                        </div>

                        <div v-if="reviewHistory.length === 0" class="py-6 text-center text-slate-500 italic">
                            No review events recorded yet.
                        </div>

                        <div v-else class="space-y-4 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-800">
                            <div
                                v-for="review in reviewHistory"
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
            </div>

            <!-- MODALS -->

            <!-- Modal: Approve -->
            <div v-if="isApproveModalOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-[#0a0f1d] border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
                    <h3 class="font-bold text-white text-base">Approve Game for Release</h3>
                    <p class="text-xs text-slate-400">
                        Mark review as 'approved'. (Note: This will not publish the game live yet. A Super Admin or Admin can publish it to the public catalog.)
                    </p>
                    <div class="space-y-1.5 text-xs">
                        <label class="block font-semibold text-slate-300">Approval Notes (Optional)</label>
                        <textarea
                            v-model="approveForm.notes"
                            rows="3"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                            placeholder="Verification passed: Gameplay smooth, controls responsive..."
                        ></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="isApproveModalOpen = false"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="approveForm.processing"
                            @click="submitApprove"
                            class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs rounded-xl transition-colors disabled:opacity-50"
                        >
                            Confirm Approval
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Approve & Publish -->
            <div v-if="isPublishModalOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-[#0a0f1d] border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
                    <h3 class="font-bold text-white text-base">Approve & Publish to Catalog</h3>
                    <p class="text-xs text-slate-400">
                        This action will move staged files to public game storage and immediately make the game publicly playable at <code class="font-mono text-emerald-400">/games/{{ game.slug }}</code>.
                    </p>
                    <div class="space-y-1.5 text-xs">
                        <label class="block font-semibold text-slate-300">Release Notes (Optional)</label>
                        <textarea
                            v-model="publishForm.notes"
                            rows="3"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                            placeholder="Approved for public launch..."
                        ></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="isPublishModalOpen = false"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="publishForm.processing"
                            @click="submitPublish"
                            class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl transition-all disabled:opacity-50"
                        >
                            Confirm & Publish Live
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Request Changes -->
            <div v-if="isRequestChangesModalOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-[#0a0f1d] border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
                    <h3 class="font-bold text-white text-base">Request Revisions</h3>
                    <p class="text-xs text-slate-400">
                        Specify what changes are required before this game can be approved.
                    </p>
                    <div class="space-y-1.5 text-xs">
                        <label class="block font-semibold text-slate-300">
                            Required Notes <span class="text-rose-400">*</span>
                        </label>
                        <textarea
                            v-model="requestChangesForm.notes"
                            rows="4"
                            required
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                            placeholder="e.g. Please update thumbnail aspect ratio to 4:3 and fix mobile touch controls..."
                        ></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="isRequestChangesModalOpen = false"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="requestChangesForm.processing || !requestChangesForm.notes"
                            @click="submitRequestChanges"
                            class="px-4 py-2 bg-orange-500 hover:bg-orange-400 text-slate-950 font-bold text-xs rounded-xl transition-colors disabled:opacity-50"
                        >
                            Submit Change Request
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Reject -->
            <div v-if="isRejectModalOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-[#0a0f1d] border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
                    <h3 class="font-bold text-white text-base">Reject Game Submission</h3>
                    <p class="text-xs text-slate-400">
                        Rejecting this game will record a formal rejection reason in the audit history.
                    </p>
                    <div class="space-y-1.5 text-xs">
                        <label class="block font-semibold text-slate-300">
                            Rejection Reason <span class="text-rose-400">*</span>
                        </label>
                        <textarea
                            v-model="rejectForm.reason"
                            rows="4"
                            required
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                            placeholder="e.g. Broken external assets, copyrighted characters, or non-functional canvas."
                        ></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="isRejectModalOpen = false"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="rejectForm.processing || !rejectForm.reason"
                            @click="submitReject"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl transition-colors disabled:opacity-50"
                        >
                            Confirm Rejection
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Return to Draft -->
            <div v-if="isReturnToDraftModalOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-[#0a0f1d] border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
                    <h3 class="font-bold text-white text-base">Return to Draft</h3>
                    <p class="text-xs text-slate-400">
                        Remove this game from active review and return its review status to 'not_submitted'.
                    </p>
                    <div class="space-y-1.5 text-xs">
                        <label class="block font-semibold text-slate-300">Optional Notes</label>
                        <textarea
                            v-model="returnToDraftForm.notes"
                            rows="3"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                            placeholder="Reason for returning to draft..."
                        ></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="isReturnToDraftModalOpen = false"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="returnToDraftForm.processing"
                            @click="submitReturnToDraft"
                            class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-bold text-xs rounded-xl transition-colors disabled:opacity-50"
                        >
                            Return to Draft
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

