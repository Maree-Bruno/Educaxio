<script setup lang="ts">
import Badge from '@/components/widgets/Badge.vue';

export interface AgendaJournalEntry {
    id: number;
    date: string;
    notes: string;
    group: string;
    group_slug: string;
    subject: string;
    school: string;
    slot_label: string | null;
}

defineProps<{ entry: AgendaJournalEntry }>();

defineEmits<{ click: [] }>();
</script>

<template>
    <li
        class="group flex cursor-pointer items-start gap-4 px-6 py-5 transition-colors hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue"
        tabindex="0"
        @click="$emit('click')"
        @keydown.enter="$emit('click')"
        @keydown.space.prevent="$emit('click')"
    >
        <div class="flex w-14 shrink-0 flex-col items-center rounded-xl bg-stone-100 py-2 text-center">
            <span class="text-xs font-bold uppercase text-stone-400">
                {{ new Date(entry.date + 'T00:00:00').toLocaleDateString('fr-BE', { month: 'short' }) }}
            </span>
            <span class="text-xl font-bold leading-none text-stone-900">
                {{ new Date(entry.date + 'T00:00:00').getDate() }}
            </span>
            <span class="mt-0.5 text-[10px] capitalize text-stone-400">
                {{ new Date(entry.date + 'T00:00:00').toLocaleDateString('fr-BE', { weekday: 'short' }) }}
            </span>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-bold text-stone-900">{{ entry.subject }}</span>
                <Badge variant="neutral">{{ entry.group }}</Badge>
                <Badge v-if="entry.slot_label" variant="neutral">{{ entry.slot_label }}</Badge>
                <span class="text-xs text-stone-400">{{ entry.school }}</span>
            </div>
            <p class="mt-1 line-clamp-2 text-sm text-stone-500">{{ entry.notes }}</p>
        </div>

        <span class="mt-1 shrink-0 text-stone-300 transition-colors group-hover:text-stone-500">→</span>
    </li>
</template>