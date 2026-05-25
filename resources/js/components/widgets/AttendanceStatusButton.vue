<script setup lang="ts">
import { computed } from 'vue';

export type StatusType = 'Absent' | 'Late' | 'Excluded';

const COLORS: Record<string, [string, string]> = {
    '':         ['bg-green-100',  'text-green-800'],
    Absent:     ['bg-red-100',    'text-red-800'],
    Late:       ['bg-yellow-100', 'text-yellow-800'],
    Excluded:   ['bg-gray-100',   'text-gray-700'],
};

const props = defineProps<{
    statusKey: StatusType | null;
    label:     string;
    title:     string;
    active:    boolean;
    disabled?: boolean;
}>();

defineEmits<{ click: [] }>();

const activeClasses = computed(() => COLORS[props.statusKey ?? '']);
</script>

<template>
    <button
        type="button"
        :title="title"
        :disabled="disabled"
        class="size-10 rounded-xl text-xs font-bold shadow-sm transition-colors disabled:cursor-not-allowed disabled:opacity-40"
        :class="active
            ? activeClasses
            : ['bg-stone-100', 'text-stone-400', 'hover:bg-stone-200']"
        @click="$emit('click')"
    >
        {{ label }}
    </button>
</template>