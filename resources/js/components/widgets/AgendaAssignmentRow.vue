<script setup lang="ts">
import Badge from '@/components/widgets/Badge.vue';
import Trash from '@/components/widgets/svg/Trash.vue';

export interface AgendaAssignment {
    id: number;
    type: 'homework' | 'test';
    title: string;
    scheduled_date: string;
    description: string | null;
    group: string;
    group_slug: string;
    subject: string;
    school: string;
    slot_label: string | null;
}

defineProps<{
    assignment: AgendaAssignment;
    past?:      boolean;
}>();

const emit = defineEmits<{
    edit:   [id: number];
    delete: [id: number];
}>();

function dayOfWeek(dateStr: string): string {
    const [y, m, d] = dateStr.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('fr-BE', { weekday: 'long' });
}

function shortDate(dateStr: string): string {
    const [y, m, d] = dateStr.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('fr-BE', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <li
        class="flex items-center gap-4 px-6 py-4 transition-colors"
        :class="past ? 'opacity-60' : 'cursor-pointer hover:bg-gray-50'"
        @click="!past && emit('edit', assignment.id)"
    >
        <!-- Date + jour -->
        <div class="w-20 shrink-0">
            <p class="text-xs font-bold text-stone-900">{{ shortDate(assignment.scheduled_date) }}</p>
            <p class="text-[11px] capitalize text-stone-400">{{ dayOfWeek(assignment.scheduled_date) }}</p>
        </div>

        <!-- Type -->
        <span
            class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
            :class="assignment.type === 'test' ? 'bg-red-100 text-red-600' : 'bg-blue/10 text-blue'"
        >{{ assignment.type === 'test' ? 'Interro' : 'Devoir' }}</span>

        <!-- Contenu -->
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-stone-900">{{ assignment.title }}</p>
            <div class="mt-0.5 flex items-center gap-1.5">
                <span class="text-xs text-stone-400">{{ assignment.subject }}</span>
                <Badge variant="neutral">{{ assignment.group }}</Badge>
                <span class="text-xs text-stone-400">· {{ assignment.school }}</span>
                <span v-if="assignment.slot_label" class="text-xs text-stone-400">· {{ assignment.slot_label }}</span>
            </div>
        </div>

        <!-- Supprimer -->
        <button
            v-if="!past"
            type="button"
            class="shrink-0 text-stone-300 transition-colors hover:text-red-500"
            title="Supprimer"
            @click.stop="emit('delete', assignment.id)"
        ><Trash :size="16" /></button>
    </li>
</template>