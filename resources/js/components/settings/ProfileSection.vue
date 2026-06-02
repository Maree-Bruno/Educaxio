<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    variant?: 'default' | 'danger';
}>();
</script>

<template>
    <section
        class="flex flex-col gap-5 rounded-2xl p-6 shadow-sm outline-1 -outline-offset-1 outline-neutral-300/10"
        :class="variant === 'danger' ? 'bg-pink/10' : 'bg-white'"
    >
        <header>
            <h2 class="text-base font-bold text-text-base">{{ title }}</h2>
            <p v-if="description" class="mt-0.5 text-sm text-stone-400">{{ description }}</p>
        </header>
        <slot />
    </section>
</template>
