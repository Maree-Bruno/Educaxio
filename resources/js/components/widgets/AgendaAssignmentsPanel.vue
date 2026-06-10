<script setup lang="ts">
import { ref } from 'vue';
import AgendaAssignmentRow from '@/components/widgets/AgendaAssignmentRow.vue';
import type { AgendaAssignment } from '@/components/widgets/AgendaAssignmentRow.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import type { Paginator } from '@/types';

defineProps<{
    upcomingAssignments: Paginator<AgendaAssignment>;
    pastAssignments:     Paginator<AgendaAssignment>;
    visibleUpcoming:     AgendaAssignment[];
    visiblePast:         AgendaAssignment[];
    hasActiveFilters:    boolean;
}>();

const emit = defineEmits<{
    edit:   [id: number];
    delete: [id: number];
}>();

const showPast = ref(false);
</script>

<template>
    <EmptyState
        v-if="upcomingAssignments.total + pastAssignments.total === 0"
        :message="hasActiveFilters ? 'Aucun résultat pour ces filtres' : 'Aucun devoir ni interrogation'"
        class="rounded-2xl bg-white"
    />

    <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm outline -outline-offset-1 outline-neutral-300/10">
        <div class="flex items-center justify-between border-b border-neutral-300/10 px-6 py-4">
            <h2 class="text-xl font-bold text-text-base">
                {{ showPast ? 'Passés' : 'À venir' }}
                <span class="text-border-figma">
                    ({{ showPast ? pastAssignments.total : upcomingAssignments.total }})
                </span>
            </h2>
            <div class="flex gap-1 rounded-xl bg-stone-100 p-1">
                <button
                    type="button"
                    class="rounded-lg px-3 py-1 text-xs font-bold transition-colors"
                    :class="!showPast ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-800'"
                    @click="showPast = false"
                >
                    À venir
                    <span class="ml-1 font-normal text-stone-400">{{ upcomingAssignments.total }}</span>
                </button>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1 text-xs font-bold transition-colors"
                    :class="showPast ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-800'"
                    @click="showPast = true"
                >
                    Passés
                    <span class="ml-1 font-normal text-stone-400">{{ pastAssignments.total }}</span>
                </button>
            </div>
        </div>

        <template v-if="!showPast">
            <ul class="divide-y divide-neutral-100">
                <AgendaAssignmentRow
                    v-for="a in visibleUpcoming"
                    :key="a.id"
                    :assignment="a"
                    @edit="emit('edit', $event)"
                    @delete="emit('delete', $event)"
                />
            </ul>
            <div v-if="upcomingAssignments.last_page > 1" class="border-t border-neutral-100 px-6 py-4">
                <Pagination
                    :links="upcomingAssignments.links"
                    :current-page="upcomingAssignments.current_page"
                    :last-page="upcomingAssignments.last_page"
                />
            </div>
        </template>

        <template v-else>
            <p v-if="pastAssignments.total === 0" class="px-6 py-8 text-center text-sm text-stone-400">
                Aucun devoir ni interrogation passé
            </p>
            <template v-else>
                <ul class="divide-y divide-neutral-100">
                    <AgendaAssignmentRow
                        v-for="a in visiblePast"
                        :key="a.id"
                        :assignment="a"
                        :past="true"
                        @edit="emit('edit', $event)"
                        @delete="emit('delete', $event)"
                    />
                </ul>
                <div v-if="pastAssignments.last_page > 1" class="border-t border-neutral-100 px-6 py-4">
                    <Pagination
                        :links="pastAssignments.links"
                        :current-page="pastAssignments.current_page"
                        :last-page="pastAssignments.last_page"
                    />
                </div>
            </template>
        </template>
    </div>
</template>
