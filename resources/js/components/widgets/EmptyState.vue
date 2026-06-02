<script setup lang="ts">
import LinkButton from '@/components/widgets/LinkButton.vue';

defineProps<{
    message: string;
    size?:   'sm' | 'md';
    action?: { label: string; href: string };
}>();
</script>

<template>
    <div
        class="flex flex-col items-center gap-3 px-6 text-center"
        :class="size === 'sm' ? 'py-8' : 'py-16'"
    >
        <p class="text-sm font-bold text-border-figma">{{ message }}</p>
        <LinkButton v-if="action" :href="action.href" variant="secondary" size="sm" :label="action.label" />
    </div>
</template>