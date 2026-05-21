<script setup lang="ts">
import ArrowUpDown from '@/components/widgets/svg/ArrowUpDown.vue';

defineProps<{
    col: string;
    currentCol: string;
    currentDir: 'asc' | 'desc';
    label: string;
}>();

defineEmits<{ sort: [col: string] }>();
</script>

<template>
    <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">
        <button
            type="button"
            class="flex items-center gap-1.5 transition-colors hover:text-text-base"
            @click="$emit('sort', col)"
        >
            {{ label }}
            <ArrowUpDown
                :size="13"
                :stroke-width="2.5"
                class="transition-transform duration-200"
                :class="{
                    'rotate-180': currentCol === col && currentDir === 'desc',
                    'opacity-30': currentCol !== col,
                }"
                aria-hidden="true"
            />
        </button>
    </th>
</template>