<script setup lang="ts">
defineProps<{
    title?:       string;
    description?: string;
}>();
</script>

<template>
    <div class="overflow-hidden rounded-2xl">
        <div class="flex items-start justify-between border-b border-neutral-300/10 bg-white px-6 py-5">
            <div>
                <h3 class="text-base font-bold text-stone-900">
                    <slot name="title">{{ title }}</slot>
                </h3>
                <p v-if="description" class="mt-0.5 text-xs text-stone-400">{{ description }}</p>
            </div>
            <slot name="action" />
        </div>
        <slot />
    </div>
</template>
