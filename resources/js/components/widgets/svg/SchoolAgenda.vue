<script setup lang="ts">
withDefaults(
    defineProps<{
        size?: number;
        strokeWidth?: number;
    }>(),
    {
        size: 24,
        strokeWidth: 2,
    },
);
</script>
<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
    >
        <path
            d="M8 3H2V18H9C10.7 18 12 19.3 12 21V7C12 4.8 10.2 3 8 3Z"
                 stroke="currentColor"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M16 12L18 14L22 10"
                 stroke="currentColor"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M22 6V3H16C13.8 3 12 4.8 12 7V21C12 19.3 13.3 18 15 18H22V15.7"
                 stroke="currentColor"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>

<style scoped></style>
