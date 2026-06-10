<script setup lang="ts">
import { computed } from 'vue';
import type { ButtonSize } from '@/types';

const props = withDefaults(defineProps<{
    keys: string;
    size?: ButtonSize;
}>(), {
    size: 'sm',
});

const sizeClasses = computed(() => ({
    xs: 'text-[8px]',
    sm: 'text-[10px] sm:text-xs',
    md: 'text-xs sm:text-sm',
    lg: 'text-sm sm:text-base',
})[props.size]);
</script>

<template>
    <kbd class="ml-1 font-mono opacity-60" :class="sizeClasses">{{ keys }}</kbd>
</template>
