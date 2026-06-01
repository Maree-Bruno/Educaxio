<script setup lang="ts">
import { useToasterStore } from '@/stores/toaster';
import type { ToastStatus } from '@/stores/toaster';

const toaster = useToasterStore();

const colorMap: Record<ToastStatus, string> = {
    success: 'bg-blue',
    warning: 'bg-orange',
    error:   'bg-pink',
};
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed top-6 right-6 z-50 flex flex-col items-end gap-3">
            <TransitionGroup
                enter-from-class="translate-x-full opacity-0"
                enter-active-class="transition-all duration-300 ease-out"
                leave-to-class="translate-x-full opacity-0"
                leave-active-class="transition-all duration-200 ease-in"
            >
                <div
                    v-for="toast in toaster.toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-center gap-3 rounded-2xl px-4 py-3 font-manrope text-sm font-medium text-white shadow-lg"
                    :class="colorMap[toast.status]"
                >
                    <span>{{ toast.text }}</span>
                    <button
                        v-if="toast.action"
                        type="button"
                        class="shrink-0 rounded-lg border border-white/40 px-2.5 py-1 text-xs font-bold text-white transition-colors hover:bg-white/20"
                        @click="toast.action.onClick()"
                    >
                        {{ toast.action.label }}
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>