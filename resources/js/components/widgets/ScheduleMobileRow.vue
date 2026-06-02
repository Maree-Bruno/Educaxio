<script setup lang="ts">
import type { ScheduleEntry, SlotRow } from '@/types';

defineProps<{
    row: SlotRow;
    entry: ScheduleEntry | null;
    isLast?: boolean;
    isActive?: boolean;
}>();

defineEmits<{ click: [] }>();
</script>

<template>
    <div
        v-if="row.type === 'lunch'"
        role="separator"
        class="flex items-center justify-center border-b border-zinc-400/10 py-3"
        :class="[isLast && 'border-b-0', isActive ? 'bg-blue/10' : 'bg-white']"
    >
        <span class="text-xs font-bold tracking-wider text-zinc-400 uppercase" aria-hidden="true">Pause</span>
    </div>

    <button
        v-else
        type="button"
        class="flex w-full items-center gap-3 border-b border-zinc-400/10 px-4 py-3 text-left transition-colors"
        :class="[
            isLast && 'border-b-0',
            isActive ? 'bg-blue/10 hover:bg-blue/15' : 'bg-white hover:bg-blue/5',
        ]"
        @click="$emit('click')"
    >
        <span class="w-20 shrink-0 text-xs font-extrabold text-border-figma" aria-hidden="true">{{ row.label }}</span>
        <div v-if="entry" class="min-w-0 flex-1">
            <p class="truncate text-sm font-extrabold text-black">{{ entry.grade }} · {{ entry.subject }}</p>
            <p class="truncate text-xs text-border-figma">{{ entry.room ?? '–' }} · {{ entry.school }}</p>
        </div>
        <p v-else class="flex-1 text-sm text-zinc-300">Libre</p>
    </button>
</template>