<script setup lang="ts">
import Button from '@/components/widgets/Button.vue';

withDefaults(
    defineProps<{
        open: boolean;
        title?: string;
        message?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        loading?: boolean;
    }>(),
    {
        title: 'Confirmer la suppression',
        message: 'Cette action est irréversible.',
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
        loading: false,
    },
);

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="title"
        >
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-black/40"
                @click="emit('cancel')"
            />

            <!-- Panel -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="open"
                    class="relative w-full max-w-md rounded-3xl bg-white px-8 py-8 shadow-lg"
                >
                    <h2 class="mb-2 text-xl font-bold text-text-base">
                        {{ title }}
                    </h2>
                    <p class="mb-8 text-sm font-medium text-border-figma">
                        {{ message }}
                    </p>

                    <div class="flex items-center justify-end gap-3">
                        <Button
                            variant="secondary"
                            size="sm"
                            :label="cancelLabel"
                            :disabled="loading"
                            @click="emit('cancel')"
                        />
                        <Button
                            variant="danger"
                            size="sm"
                            :label="confirmLabel"
                            :loading="loading"
                            @click="emit('confirm')"
                        />
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>