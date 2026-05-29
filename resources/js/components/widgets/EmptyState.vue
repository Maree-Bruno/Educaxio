<script setup lang="ts">
defineProps<{
    message: string;
    size?: 'sm' | 'md';
}>();
</script>

<template>
    <p
        class="px-6 text-center text-sm font-bold text-border-figma"
        :class="size === 'sm' ? 'py-8' : 'py-16'"
    >
        {{ message }}
    </p>
</template>
