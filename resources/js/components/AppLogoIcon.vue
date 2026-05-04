<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <div class="flex items-center justify-center gap-1">
        <div
            class="inline-flex h-9 w-9 items-center justify-center gap-2.5 rounded-2xl bg-blue p-2.5"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
            >
                <path
                    d="M22 10V16M22 10L12 5L2 10L12 15L22 10Z"
                    stroke="#F5F0EB"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    d="M6 12V17C9 20 15 20 18 17V12"
                    stroke="#F5F0EB"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </div>
        <span class="text-4xl font-black tracking-[-2px] text-text-base">
            Educaxio
        </span>
    </div>
</template>
