<script setup lang="ts">
defineProps<{
    size?: number;
    strokeWidth?: number;
}>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="size ?? 24"
        :height="size ?? 24"
        viewBox="0 0 24 24"
        fill="none"
    >
        <path
            d="M7 20V4M7 4L4 8M7 4L10 8"
            stroke="currentColor"
            :stroke-width="strokeWidth ?? 2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M17 4V20M17 20L14 16M17 20L20 16"
            stroke="currentColor"
            :stroke-width="strokeWidth ?? 2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
