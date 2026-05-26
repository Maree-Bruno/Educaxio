<script setup lang="ts">
import { ref } from 'vue';

const dialogRef = ref<HTMLDialogElement | null>(null);

function open() {
    dialogRef.value?.showModal();
    document.body.style.overflow = 'hidden';
}

function close() {
    dialogRef.value?.close();
    document.body.style.overflow = '';
}

function onBackdrop(e: MouseEvent) {
    if (e.target === dialogRef.value) {
        close();
    }
}

defineExpose({ open, close });
</script>

<template>
    <dialog
        ref="dialogRef"
        class="w-[90vw] max-w-md overflow-hidden rounded-2xl bg-bg-primary p-4 sm:p-6"
        @click="onBackdrop"
        @cancel.prevent="close"
    >
        <slot />
    </dialog>
</template>

<style scoped>
dialog { margin: auto; }
dialog::backdrop {
    background-color: rgb(0 0 0 / 0.4);
    backdrop-filter: blur(4px);
}
</style>