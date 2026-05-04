<script setup lang="ts">
withDefaults(defineProps<{ size?: number; strokeWidth?: number }>(), { size: 24, strokeWidth: 2 });
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="size"
        :height="size"
        viewBox="0 0 20 20"
        fill="none"
    >
        <path
            d="M2.5 5H17.5"
            stroke="currentColor"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M15.8334 5V16.6667C15.8334 17.5 15 18.3333 14.1667 18.3333H5.83335C5.00002 18.3333 4.16669 17.5 4.16669 16.6667V5"
            stroke="currentColor"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M6.66669 5.00008V3.33341C6.66669 2.50008 7.50002 1.66675 8.33335 1.66675H11.6667C12.5 1.66675 13.3334 2.50008 13.3334 3.33341V5.00008"
            stroke="currentColor"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M8.33331 9.16675V14.1667"
            stroke="currentColor"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M11.6667 9.16675V14.1667"
            stroke="currentColor"
            :stroke-width="strokeWidth"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>