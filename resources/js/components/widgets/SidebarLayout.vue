<script setup lang="ts">
withDefaults(defineProps<{
    sidebarWidth?: 'sm' | 'md';
    gap?: 'sm' | 'md';
}>(), {
    sidebarWidth: 'md',
    gap: 'md',
});
</script>

<template>
    <div
        class="flex flex-col xl:flex-row xl:items-start"
        :class="gap === 'sm' ? 'gap-4' : 'gap-6'"
    >
        <div class="flex min-w-0 flex-1 flex-col gap-6">
            <slot />
        </div>
        <aside
            class="flex w-full shrink-0 flex-col gap-4 xl:sticky xl:top-20"
            :class="sidebarWidth === 'sm' ? 'xl:w-72' : 'xl:w-80'"
        >
            <slot name="sidebar" />
        </aside>
    </div>
</template>
