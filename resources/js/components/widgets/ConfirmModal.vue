<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import Button from '@/components/widgets/Button.vue';

const props = withDefaults(
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

const dialogRef = ref<HTMLDialogElement | null>(null);

onMounted(() => {
    if (props.open) {
        dialogRef.value?.showModal();
    }
});

watch(
    () => props.open,
    (isOpen) => {
        if (!dialogRef.value) {
            return;
        }

        if (isOpen) {
            dialogRef.value.showModal();
        } else {
            dialogRef.value.close();
        }
    },
);

function onBackdropClick(event: MouseEvent) {
    if (event.target === dialogRef.value) {
        emit('cancel');
    }
}

function onCancel(event: Event) {
    event.preventDefault();
    emit('cancel');
}
</script>

<template>
    <dialog
        ref="dialogRef"
        class="w-full max-w-md rounded-3xl bg-white px-8 py-8 shadow-lg"
        :aria-labelledby="title"
        @click="onBackdropClick"
        @cancel="onCancel"
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
    </dialog>
</template>

<style scoped>
dialog {
    margin: auto;
}

dialog::backdrop {
    background-color: rgb(0 0 0 / 0.4);
}
</style>