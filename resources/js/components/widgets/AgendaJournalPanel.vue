<script setup lang="ts">
import AgendaJournalRow from '@/components/widgets/AgendaJournalRow.vue';
import type { AgendaJournalEntry } from '@/components/widgets/AgendaJournalRow.vue';
import Button from '@/components/widgets/Button.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import type { Paginator } from '@/types';

defineProps<{
    journalEntries: Paginator<AgendaJournalEntry>;
    hasActiveFilters: boolean;
}>();

const emit = defineEmits<{
    clickEntry: [entry: AgendaJournalEntry];
    create: [];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm outline -outline-offset-1 outline-neutral-300/10">
        <div class="flex items-center justify-between border-b border-neutral-300/10 px-6 py-4">
            <h2 class="text-xl font-bold text-text-base">
                Journal de classe
                <span class="text-border-figma">({{ journalEntries.total }})</span>
            </h2>
            <Button variant="secondary" size="sm" @click="emit('create')">Ajouter</Button>
        </div>
        <p
            v-if="journalEntries.data.length === 0"
            class="px-6 py-12 text-center text-sm font-bold text-border-figma"
        >
            {{ hasActiveFilters ? 'Aucun résultat pour ces filtres' : 'Aucune note de journal' }}
        </p>
        <template v-else>
            <ul class="divide-y divide-neutral-100">
                <AgendaJournalRow
                    v-for="entry in journalEntries.data"
                    :key="entry.id"
                    :entry="entry"
                    @click="emit('clickEntry', entry)"
                />
            </ul>
            <div class="border-t border-neutral-100 px-6 py-4">
                <Pagination
                    :links="journalEntries.links"
                    :current-page="journalEntries.current_page"
                    :last-page="journalEntries.last_page"
                />
            </div>
        </template>
    </div>
</template>
