<script setup lang="ts">
import { computed } from 'vue';
import { ATTENDANCE_STATUS_COLORS, type AttendanceStatus } from '@/types';

const props = defineProps<{
    statusKey: AttendanceStatus | null;
    label: string;
    title: string;
    active: boolean;
    disabled?: boolean;
}>();

defineEmits<{ click: [] }>();

const activeClasses = computed(
    () => ATTENDANCE_STATUS_COLORS[props.statusKey ?? ''],
);
</script>

<template>
    <button
        type="button"
        :title="title"
        :disabled="disabled"
        class="size-10 rounded-xl text-xs font-bold shadow-sm transition-colors disabled:cursor-not-allowed disabled:opacity-40"
        :class="
            active
                ? activeClasses
                : ['bg-stone-100', 'text-stone-400', 'hover:bg-stone-200']
        "
        @click="$emit('click')"
    >
    <abbr :title="title">{{ label }}</abbr>
    </button>
</template>
