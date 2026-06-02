<script setup lang="ts">
import type { ScheduleEntry } from '@/types';

defineProps<{
    entry: ScheduleEntry | null;
    active?: boolean;
}>();
</script>

<template>
    <div
        class="h-full rounded-lg transition-all"
        :class="active ? 'bg-blue/10' : entry ? 'bg-white hover:shadow-sm' : 'bg-white hover:bg-blue/5'"
    >
        <div v-if="entry" class="flex h-full flex-col justify-between p-2">
            <div class="flex items-start justify-between gap-1">
                <span class="shrink-0 text-xs leading-4 font-extrabold text-black">{{ entry.grade }}</span>
                <span class="line-clamp-1 text-right text-xs leading-4 font-extrabold text-black">{{ entry.subject }}</span>
            </div>
            <div class="flex items-center justify-between gap-1">
                <span class="shrink-0 text-xs leading-4 font-extrabold text-black">{{ entry.room ?? '–' }}</span>
                <span class="line-clamp-1 text-right text-xs leading-4 font-extrabold text-black">{{ entry.school }}</span>
            </div>
        </div>
    </div>
</template>