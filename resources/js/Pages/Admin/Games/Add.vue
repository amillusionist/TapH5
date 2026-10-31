<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import type { Category, Tag, StagingFolderInfo, StagingInspectionResult } from '@/types';

const props = defineProps<{
    stagingFolders: StagingFolderInfo[];
    categories: Category[];
    tags: Tag[];
    canPublish: boolean;
}>();

// Wizard steps: 1 = Select Folder, 2 = Inspect & Validate, 3 = Metadata & Classification, 4 = Review & Import
const currentStep = ref<1 | 2 | 3 | 4>(1);
const selectedFolder = ref<StagingFolderInfo | null>(null);
const folderSearch = ref('');

// Inspection state
const isInspecting = ref(false);
const inspectionError = ref<string | null>(null);
const inspectionResult = ref<StagingInspectionResult | null>(null);

// Form
const form = useForm({
    folder: '',
    title: '',
    slug: '',
    description: '',
    instructions: '',
    category_id: '' as string | number,
    tag_ids: [] as number[],
    thumbnail: '',
    action_type: 'draft' as 'draft' | 'submit_for_review' | 'publish',
});

// Filter staging folders
const filteredFolders = computed(() => {
    const q = folderSearch.value.toLowerCase().trim();
    if (!q) return props.stagingFolders;
    return props.stagingFolders.filter(f => f.folder.toLowerCase().includes(q));
});

const generateSlug = (val: string) => {
    return val
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
};

const handleTitleChange = () => {
    if (!form.slug || form.slug === generateSlug(form.title.slice(0, -1))) {
        form.slug = generateSlug(form.title);
    }
};

// Select folder and run inspection
const selectFolder = async (folderInfo: StagingFolderInfo) => {
    selectedFolder.value = folderInfo;
    form.folder = folderInfo.folder;
    isInspecting.value = true;
    inspectionError.value = null;
    currentStep.value = 2;

    try {
        const response = await fetch(route('admin.games.inspect_staging'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ folder: folderInfo.folder }),
        });

        if (!response.ok) {
            const errData = await response.json().catch(() => null);
            throw new Error(errData?.message || `Server returned ${response.status}`);
        }

        const data: StagingInspectionResult = await response.json();
        inspectionResult.value = data;

        // Auto-fill form fields from inspection metadata
        form.title = data.metadata.title || folderInfo.folder.replace(/[-_]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        form.slug = generateSlug(form.title);
        form.description = data.metadata.description || '';
        form.instructions = data.metadata.instructions || '';
        
        if (data.suggested_category) {
            form.category_id = data.suggested_category.id;
        } else if (props.categories.length > 0) {
            form.category_id = props.categories[0].id;
        }

        if (data.thumbnail?.relative_path) {
            form.thumbnail = data.thumbnail.relative_path;
        }
    } catch (err: any) {
        inspectionError.value = err.message || 'Failed to inspect staging folder';
    } finally {
        isInspecting.value = false;
    }
};

const proceedToMetadata = () => {
    if (!inspectionResult.value?.is_valid) return;
    currentStep.value = 3;
};

const proceedToReview = () => {
    if (!form.title || !form.slug || !form.category_id) return;
    currentStep.value = 4;
};

const toggleTag = (tagId: number) => {
    const idx = form.tag_ids.indexOf(tagId);
    if (idx > -1) {
        form.tag_ids.splice(idx, 1);
    } else {
        form.tag_ids.push(tagId);
    }
};

const submitGame = (action: 'draft' | 'submit_for_review' | 'publish') => {
    form.action_type = action;
    form.post(route('admin.games.store_staged'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Add Game from Staging">
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <AdminPageHeader
                title="Add Staged Game"
                description="Discover, inspect, validate, and import HTML5 game packages from staging storage."
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Games', href: route('admin.games.index') },
                    { label: 'Add Game' },
                ]"
            />

            <!-- Wizard Stepper Navigation -->
            <div class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80">
                <div class="grid grid-cols-4 gap-2 text-xs">
                    <!-- Step 1 -->
                    <button
                        type="button"
                        @click="currentStep = 1"
                        :class="[
                            'p-3 rounded-xl flex items-center gap-3 transition-all text-left border',
                            currentStep === 1
                                ? 'bg-indigo-500/10 border-indigo-500/40 text-indigo-400 font-bold shadow-lg shadow-indigo-500/10'
                                : currentStep > 1
                                    ? 'bg-slate-900/60 border-slate-800 text-slate-300'
                                    : 'bg-slate-950/40 border-transparent text-slate-500 opacity-60'
                        ]"
                    >
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center font-mono text-xs font-black"
                            :class="currentStep >= 1 ? 'bg-indigo-500 text-slate-950' : 'bg-slate-800 text-slate-400'">
                            1
                        </span>
                        <div class="min-w-0">
                            <span class="block truncate font-semibold">1. Select Folder</span>
                            <span class="text-[10px] text-slate-400 block truncate">Choose staging source</span>
                        </div>
                    </button>

                    <!-- Step 2 -->
                    <button
                        type="button"
                        :disabled="!selectedFolder"
                        @click="selectedFolder && (currentStep = 2)"
                        :class="[
                            'p-3 rounded-xl flex items-center gap-3 transition-all text-left border disabled:opacity-40 disabled:cursor-not-allowed',
                            currentStep === 2
                                ? 'bg-indigo-500/10 border-indigo-500/40 text-indigo-400 font-bold shadow-lg shadow-indigo-500/10'
                                : currentStep > 2
                                    ? 'bg-slate-900/60 border-slate-800 text-slate-300'
                                    : 'bg-slate-950/40 border-transparent text-slate-500'
                        ]"
                    >
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center font-mono text-xs font-black"
                            :class="currentStep >= 2 ? 'bg-indigo-500 text-slate-950' : 'bg-slate-800 text-slate-400'">
                            2
                        </span>
                        <div class="min-w-0">
                            <span class="block truncate font-semibold">2. Scan & Validate</span>
                            <span class="text-[10px] text-slate-400 block truncate">Diagnostics & duplicate check</span>
                        </div>
                    </button>

                    <!-- Step 3 -->
                    <button
                        type="button"
                        :disabled="!inspectionResult?.is_valid"
                        @click="inspectionResult?.is_valid && (currentStep = 3)"
                        :class="[
                            'p-3 rounded-xl flex items-center gap-3 transition-all text-left border disabled:opacity-40 disabled:cursor-not-allowed',
                            currentStep === 3
                                ? 'bg-indigo-500/10 border-indigo-500/40 text-indigo-400 font-bold shadow-lg shadow-indigo-500/10'
                                : currentStep > 3
                                    ? 'bg-slate-900/60 border-slate-800 text-slate-300'
                                    : 'bg-slate-950/40 border-transparent text-slate-500'
                        ]"
                    >
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center font-mono text-xs font-black"
                            :class="currentStep >= 3 ? 'bg-indigo-500 text-slate-950' : 'bg-slate-800 text-slate-400'">
                            3
                        </span>
                        <div class="min-w-0">
                            <span class="block truncate font-semibold">3. Metadata & Tags</span>
                            <span class="text-[10px] text-slate-400 block truncate">Title, category, thumbnail</span>
                        </div>
                    </button>

                    <!-- Step 4 -->
                    <button
                        type="button"
                        :disabled="!form.title || !form.category_id"
                        @click="form.title && form.category_id && (currentStep = 4)"
                        :class="[
                            'p-3 rounded-xl flex items-center gap-3 transition-all text-left border disabled:opacity-40 disabled:cursor-not-allowed',
                            currentStep === 4
                                ? 'bg-indigo-500/10 border-indigo-500/40 text-indigo-400 font-bold shadow-lg shadow-indigo-500/10'
                                : 'bg-slate-950/40 border-transparent text-slate-500'
                        ]"
                    >
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center font-mono text-xs font-black"
                            :class="currentStep === 4 ? 'bg-indigo-500 text-slate-950' : 'bg-slate-800 text-slate-400'">
                            4
                        </span>
                        <div class="min-w-0">
                            <span class="block truncate font-semibold">4. Review & Save</span>
                            <span class="text-[10px] text-slate-400 block truncate">Draft, submit, or publish</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- STEP 1: Select Staging Folder -->
            <div v-if="currentStep === 1" class="space-y-4">
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/60 pb-4">
                        <div>
                            <h2 class="text-base font-bold text-white">Staging Packages Available</h2>
                            <p class="text-xs text-slate-400">
                                Packages located in <code class="font-mono text-cyan-400 bg-slate-900 px-1.5 py-0.5 rounded">storage/staging</code> ready for scanning.
                            </p>
                        </div>
                        <div class="w-full sm:w-64">
                            <input
                                v-model="folderSearch"
                                type="text"
                                placeholder="Filter folders..."
                                class="w-full bg-slate-900/80 border border-slate-800 text-slate-200 text-xs rounded-xl px-3 py-2 focus:border-indigo-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div v-if="stagingFolders.length === 0" class="py-12 text-center text-slate-400 text-xs">
                        <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="font-semibold text-slate-300">No staging folders detected.</p>
                        <p class="mt-1">Place HTML5 game directories inside <code class="font-mono text-cyan-400">storage/staging/</code> to scan and import them.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="folder in filteredFolders"
                            :key="folder.folder"
                            class="p-4 rounded-xl border transition-all flex flex-col justify-between space-y-3"
                            :class="[
                                selectedFolder?.folder === folder.folder
                                    ? 'bg-indigo-500/5 border-indigo-500/40 ring-1 ring-indigo-500/20'
                                    : 'bg-slate-900/50 border-slate-800 hover:border-slate-700'
                            ]"
                        >
                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="font-mono text-sm font-bold text-white break-all flex items-center gap-2">
                                        <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                        {{ folder.folder }}
                                    </h3>
                                    <span
                                        v-if="folder.is_imported"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30 shrink-0"
                                    >
                                        Imported
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 shrink-0"
                                    >
                                        Available
                                    </span>
                                </div>

                                <div class="grid grid-cols-3 gap-2 text-[11px] text-slate-400 pt-1">
                                    <div>
                                        <span class="block text-[10px] text-slate-500 uppercase">Size</span>
                                        <span class="font-mono text-slate-200 font-medium">{{ folder.size_formatted }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] text-slate-500 uppercase">Files</span>
                                        <span class="font-mono text-slate-200 font-medium">{{ folder.file_count }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] text-slate-500 uppercase">Entry</span>
                                        <span :class="folder.has_index ? 'text-emerald-400' : 'text-rose-400'" class="font-mono font-medium">
                                            {{ folder.entry_file || 'Missing' }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="folder.existing_game" class="p-2 rounded bg-slate-950/60 border border-slate-800 text-[11px] text-slate-300">
                                    Linked Game: <strong class="text-white">{{ folder.existing_game.title }}</strong>
                                    <span class="text-slate-500 font-mono text-[10px] ml-1">({{ folder.existing_game.status }})</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="selectFolder(folder)"
                                :disabled="!folder.has_index"
                                class="w-full py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="selectedFolder?.folder === folder.folder
                                    ? 'bg-indigo-600 hover:bg-indigo-500 text-white'
                                    : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700'"
                            >
                                <span>Inspect & Validate</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Scan & Validate -->
            <div v-if="currentStep === 2" class="space-y-4">
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-800/60 pb-4">
                        <div>
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                <span>Technical Inspection & Validation</span>
                                <span v-if="isInspecting" class="animate-spin text-indigo-400">⏳</span>
                            </h2>
                            <p class="text-xs text-slate-400">
                                Inspecting folder: <code class="font-mono text-cyan-400">{{ selectedFolder?.folder }}</code>
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="selectedFolder && selectFolder(selectedFolder)"
                            :disabled="isInspecting"
                            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold border border-slate-700 transition-colors flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Re-run Inspection
                        </button>
                    </div>

                    <!-- Loading skeleton -->
                    <div v-if="isInspecting" class="py-12 text-center text-slate-400 space-y-3">
                        <div class="w-8 h-8 mx-auto border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs">Analyzing file structure, computing cryptographic fingerprint, parsing metadata...</p>
                    </div>

                    <!-- Inspection Error -->
                    <div v-else-if="inspectionError" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Inspection Failed
                        </div>
                        <p>{{ inspectionError }}</p>
                    </div>

                    <!-- Inspection Results -->
                    <div v-else-if="inspectionResult" class="space-y-6">
                        <!-- Validation Status Banner -->
                        <div
                            class="p-4 rounded-xl border flex items-center justify-between gap-4"
                            :class="inspectionResult.is_valid
                                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'
                                : 'bg-rose-500/10 border-rose-500/30 text-rose-300'"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm"
                                    :class="inspectionResult.is_valid ? 'bg-emerald-500 text-slate-950' : 'bg-rose-500 text-slate-950'">
                                    {{ inspectionResult.is_valid ? '✓' : '✕' }}
                                </span>
                                <div>
                                    <h4 class="font-bold text-xs uppercase tracking-wider">
                                        {{ inspectionResult.is_valid ? 'Validation Passed — Package Ready for Import' : 'Validation Failed — Issues Detected' }}
                                    </h4>
                                    <p class="text-[11px] opacity-80">
                                        {{ inspectionResult.file_count }} files scanned • {{ inspectionResult.size_formatted }} • Entry: {{ inspectionResult.entry_file }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Duplicate Warning -->
                        <div
                            v-if="inspectionResult.is_duplicate"
                            class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs space-y-1"
                        >
                            <div class="font-bold flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Duplicate Content Detected
                            </div>
                            <p>
                                An existing game with the exact same cryptographic fingerprint already exists in the catalog:
                                <strong class="text-white">{{ inspectionResult.duplicate_game?.title }}</strong>
                                (Slug: <code class="font-mono text-cyan-400">{{ inspectionResult.duplicate_game?.slug }}</code>).
                            </p>
                        </div>

                        <!-- 4 Metric Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Package Size</span>
                                <span class="font-mono text-slate-200 font-semibold text-sm">{{ inspectionResult.size_formatted }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Total Files</span>
                                <span class="font-mono text-slate-200 font-semibold text-sm">{{ inspectionResult.file_count }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Entry File</span>
                                <span class="font-mono text-emerald-400 font-semibold text-sm">{{ inspectionResult.entry_file }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-slate-500 text-[10px] uppercase block font-bold">Suggested Category</span>
                                <span class="text-cyan-400 font-semibold text-sm truncate block">
                                    {{ inspectionResult.suggested_category?.name || 'General' }}
                                </span>
                            </div>
                        </div>

                        <!-- Detected Thumbnail & Extracted Metadata -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Thumbnail Preview -->
                            <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800 space-y-3">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Package Thumbnail</span>
                                <div class="aspect-[4/3] rounded-lg bg-slate-950 flex items-center justify-center border border-slate-800 overflow-hidden relative">
                                    <img
                                        v-if="inspectionResult.thumbnail?.preview_url"
                                        :src="inspectionResult.thumbnail.preview_url"
                                        alt="Thumbnail Preview"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="text-center p-4 text-slate-500 text-xs">
                                        <svg class="w-8 h-8 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        No package thumbnail detected
                                    </div>
                                </div>
                                <div v-if="inspectionResult.thumbnail?.relative_path" class="text-[10px] font-mono text-slate-400 truncate">
                                    Path: {{ inspectionResult.thumbnail.relative_path }}
                                </div>
                            </div>

                            <!-- Extracted Metadata -->
                            <div class="md:col-span-2 p-4 rounded-xl bg-slate-900/50 border border-slate-800 space-y-3 text-xs">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Extracted HTML5 Metadata</span>
                                <div class="space-y-2">
                                    <div>
                                        <span class="text-slate-500 text-[10px] uppercase block">Extracted Title</span>
                                        <span class="text-slate-200 font-medium">{{ inspectionResult.metadata.title || 'None' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 text-[10px] uppercase block">Extracted Description</span>
                                        <p class="text-slate-300 text-xs line-clamp-2">{{ inspectionResult.metadata.description || 'None' }}</p>
                                    </div>
                                    <div v-if="inspectionResult.fingerprint" class="pt-1">
                                        <span class="text-slate-500 text-[10px] uppercase block">Cryptographic Fingerprint</span>
                                        <code class="font-mono text-[11px] text-cyan-400 break-all select-all block bg-slate-950 p-2 rounded border border-slate-800">
                                            {{ inspectionResult.fingerprint }}
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Errors or Warnings list -->
                        <div v-if="inspectionResult.errors.length > 0" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-2">
                            <span class="font-bold block">Validation Errors:</span>
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="(err, i) in inspectionResult.errors" :key="i">{{ err }}</li>
                            </ul>
                        </div>

                        <!-- Action footer -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                            <button
                                type="button"
                                @click="currentStep = 1"
                                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                            >
                                ← Back to Folder Selection
                            </button>
                            <button
                                type="button"
                                :disabled="!inspectionResult.is_valid"
                                @click="proceedToMetadata"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <span>Continue to Metadata</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Metadata Customization & Classification -->
            <div v-if="currentStep === 3" class="space-y-4">
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-6">
                    <div class="border-b border-slate-800/60 pb-4">
                        <h2 class="text-base font-bold text-white">Metadata & Game Classification</h2>
                        <p class="text-xs text-slate-400">
                            Refine title, category, description, and tags before completing import.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
                        <!-- Title -->
                        <div class="space-y-1.5">
                            <label class="block font-semibold text-slate-300 uppercase text-[10px] tracking-wider">
                                Game Title <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                @input="handleTitleChange"
                                type="text"
                                required
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                                placeholder="e.g. Retro Space Invaders"
                            />
                        </div>

                        <!-- Slug -->
                        <div class="space-y-1.5">
                            <label class="block font-semibold text-slate-300 uppercase text-[10px] tracking-wider">
                                URL Slug <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.slug"
                                type="text"
                                required
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2.5 text-slate-200 text-xs font-mono focus:border-indigo-500 focus:outline-none"
                                placeholder="retro-space-invaders"
                            />
                        </div>

                        <!-- Category -->
                        <div class="space-y-1.5">
                            <label class="block font-semibold text-slate-300 uppercase text-[10px] tracking-wider">
                                Category <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.category_id"
                                required
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2.5 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                            >
                                <option value="" disabled>Select a category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Thumbnail path / URL -->
                        <div class="space-y-1.5">
                            <label class="block font-semibold text-slate-300 uppercase text-[10px] tracking-wider">
                                Thumbnail Path or URL
                            </label>
                            <input
                                v-model="form.thumbnail"
                                type="text"
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2.5 text-slate-200 text-xs font-mono focus:border-indigo-500 focus:outline-none"
                                placeholder="thumbnail.png or /storage/thumbnails/game.jpg"
                            />
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label class="block font-semibold text-slate-300 uppercase text-[10px] tracking-wider">
                                Game Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                                placeholder="Engaging description of gameplay, lore, and mechanics..."
                            ></textarea>
                        </div>

                        <!-- Instructions -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label class="block font-semibold text-slate-300 uppercase text-[10px] tracking-wider">
                                Gameplay Controls & Instructions
                            </label>
                            <textarea
                                v-model="form.instructions"
                                rows="3"
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-slate-200 text-xs focus:border-indigo-500 focus:outline-none"
                                placeholder="Controls: Use Arrow Keys or WASD to navigate. Space to shoot. Tap screen on mobile."
                            ></textarea>
                        </div>

                        <!-- Tags Selection -->
                        <div class="md:col-span-2 space-y-2">
                            <label class="block font-semibold text-slate-300 uppercase text-[10px] tracking-wider">
                                Tags
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    type="button"
                                    @click="toggleTag(tag.id)"
                                    class="px-2.5 py-1 rounded-lg text-xs font-medium border transition-all"
                                    :class="form.tag_ids.includes(tag.id)
                                        ? 'bg-indigo-500/20 border-indigo-500/50 text-indigo-300'
                                        : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    {{ tag.name }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                        <button
                            type="button"
                            @click="currentStep = 2"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                        >
                            ← Back to Validation
                        </button>
                        <button
                            type="button"
                            :disabled="!form.title || !form.slug || !form.category_id"
                            @click="proceedToReview"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <span>Proceed to Final Decision</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Review & Decision -->
            <div v-if="currentStep === 4" class="space-y-4">
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-6">
                    <div class="border-b border-slate-800/60 pb-4">
                        <h2 class="text-base font-bold text-white">Review & Decision</h2>
                        <p class="text-xs text-slate-400">
                            Confirm details and choose how to save this game.
                        </p>
                    </div>

                    <!-- Summary Card -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs bg-slate-900/40 p-4 rounded-xl border border-slate-800/60">
                        <div>
                            <span class="text-slate-500 text-[10px] uppercase block font-bold">Target Game Title</span>
                            <span class="text-white font-bold text-sm">{{ form.title }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[10px] uppercase block font-bold">Target URL Slug</span>
                            <span class="text-cyan-400 font-mono text-xs">/games/{{ form.slug }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[10px] uppercase block font-bold">Selected Category</span>
                            <span class="text-slate-200">
                                {{ categories.find(c => c.id === Number(form.category_id))?.name || 'Unassigned' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[10px] uppercase block font-bold">Staging Source Folder</span>
                            <span class="text-slate-300 font-mono">{{ form.folder }}</span>
                        </div>
                    </div>

                    <!-- Available Action Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <!-- Action 1: Save as Draft -->
                        <div class="p-5 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between space-y-4">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700 inline-block mb-2">
                                    Option A
                                </span>
                                <h3 class="font-bold text-white text-sm">Save as Draft</h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Imports the game with draft status and review status 'not_submitted'. Game will remain private and unlisted.
                                </p>
                            </div>
                            <button
                                type="button"
                                :disabled="form.processing"
                                @click="submitGame('draft')"
                                class="w-full py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all border border-slate-700 disabled:opacity-50"
                            >
                                Save as Draft
                            </button>
                        </div>

                        <!-- Action 2: Submit for Review -->
                        <div class="p-5 rounded-xl bg-amber-500/5 border border-amber-500/30 flex flex-col justify-between space-y-4">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/40 inline-block mb-2">
                                    Option B (Recommended)
                                </span>
                                <h3 class="font-bold text-white text-sm">Submit for Review</h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Enters the game into the Staff Review Queue with status 'pending'. Reviewers can test and verify before launch.
                                </p>
                            </div>
                            <button
                                type="button"
                                :disabled="form.processing"
                                @click="submitGame('submit_for_review')"
                                class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-amber-500/20 disabled:opacity-50"
                            >
                                Submit for Review
                            </button>
                        </div>

                        <!-- Action 3: Publish Immediately -->
                        <div class="p-5 rounded-xl border flex flex-col justify-between space-y-4"
                            :class="canPublish
                                ? 'bg-emerald-500/5 border-emerald-500/30'
                                : 'bg-slate-950/40 border-slate-800 opacity-60'">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider inline-block mb-2"
                                    :class="canPublish ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-slate-800 text-slate-500'">
                                    Option C (Admin Only)
                                </span>
                                <h3 class="font-bold text-white text-sm">Publish Immediately</h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Publishes game directly to public catalog, moves assets to public games storage, and marks status as 'published'.
                                </p>
                            </div>
                            <button
                                v-if="canPublish"
                                type="button"
                                :disabled="form.processing"
                                @click="submitGame('publish')"
                                class="w-full py-2.5 px-4 bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-emerald-500/20 disabled:opacity-50"
                            >
                                Publish Immediately
                            </button>
                            <div v-else class="text-[11px] text-slate-500 italic text-center py-2">
                                Requires Admin or Super Admin role
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                        <button
                            type="button"
                            @click="currentStep = 3"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold"
                        >
                            ← Back to Metadata
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

