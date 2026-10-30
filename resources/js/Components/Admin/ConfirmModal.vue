<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show: boolean;
        title: string;
        message: string;
        confirmText?: string;
        cancelText?: string;
        variant?: 'danger' | 'warning' | 'primary';
        loading?: boolean;
    }>(),
    {
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        variant: 'danger',
        loading: false,
    }
);

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'close'): void;
}>();

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show && !props.loading) {
        emit('close');
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-sm"
                @click.self="!loading && emit('close')"
            >
                <!-- Modal Card -->
                <Transition
                    enter-active-class="transition duration-200 ease-out transform"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in transform"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div
                        v-if="show"
                        class="w-full max-w-lg bg-[#0a0f1d] border border-slate-800 rounded-2xl shadow-2xl shadow-black/80 overflow-hidden"
                        role="dialog"
                        aria-modal="true"
                    >
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <!-- Variant Icon -->
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                    :class="{
                                        'bg-rose-500/10 text-rose-400 border border-rose-500/20': variant === 'danger',
                                        'bg-amber-500/10 text-amber-400 border border-amber-500/20': variant === 'warning',
                                        'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': variant === 'primary',
                                    }"
                                >
                                    <svg v-if="variant === 'danger'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <svg v-else-if="variant === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>

                                <!-- Header Content -->
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-bold text-white tracking-tight">
                                        {{ title }}
                                    </h3>
                                    <p class="mt-2 text-xs sm:text-sm text-slate-400 leading-relaxed">
                                        {{ message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Footer -->
                        <div class="px-6 py-4 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="emit('close')"
                                :disabled="loading"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 transition-colors disabled:opacity-50"
                            >
                                {{ cancelText }}
                            </button>

                            <button
                                type="button"
                                @click="emit('confirm')"
                                :disabled="loading"
                                class="px-5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2 shadow-lg disabled:opacity-50"
                                :class="{
                                    'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-950/30': variant === 'danger',
                                    'bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-amber-950/30': variant === 'warning',
                                    'bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-emerald-950/30': variant === 'primary',
                                }"
                            >
                                <svg
                                    v-if="loading"
                                    class="w-3.5 h-3.5 animate-spin text-current"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ confirmText }}</span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

