<script setup lang="ts">
defineProps<{
    href?: string;
    variant?: 'blue' | 'neutral';
    size?: 'sm' | 'md';
    removable?: boolean;
}>();

defineEmits<{ remove: [] }>();
</script>

<template>
    <component
        :is="href ? 'a' : 'span'"
        :href="href"
        class="inline-flex items-center gap-1 rounded-lg font-bold"
        :class="[
            size === 'md' ? 'px-3 py-1.5 text-sm' : 'px-2 py-1 text-xs',
            variant === 'neutral'
                ? 'bg-neutral-100 font-normal text-stone-500'
                : ['bg-blue/10 text-blue', href && 'hover:bg-blue/20'],
        ]"
    >
        <slot />
        <button
            v-if="removable"
            type="button"
            class="ml-0.5 leading-none opacity-60 hover:opacity-100"
            @click.prevent="$emit('remove')"
        >
            ×
        </button>
    </component>
</template>
