<script setup lang="ts">
defineProps<{
    gradient: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <div :class="['flex flex-col gap-4 rounded-3xl p-6', gradient]">
        <div class="flex size-12 items-center justify-center text-bg-primary">
            <slot name="icon" />
        </div>
        <div class="flex flex-col gap-4">
            <h3 class="text-lg font-semibold text-bg-primary">{{ title }}</h3>
            <p class="text-sm font-normal text-stone-100">{{ description }}</p>
        </div>
        <div class="min-h-48 flex-1 rounded-3xl bg-white/20" />
    </div>
</template>