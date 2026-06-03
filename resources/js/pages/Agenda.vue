<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AgendaAssignmentRow from '@/components/widgets/AgendaAssignmentRow.vue';
import type { AgendaAssignment } from '@/components/widgets/AgendaAssignmentRow.vue';
import AgendaJournalRow from '@/components/widgets/AgendaJournalRow.vue';
import type { AgendaJournalEntry } from '@/components/widgets/AgendaJournalRow.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import DateField from '@/components/widgets/DateField.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import FilterBar from '@/components/widgets/FilterBar.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { agenda, attendances } from '@/routes';
import { update as updateAssignment, destroy as destroyAssignment } from '@/routes/assignments';
import { useToasterStore } from '@/stores/toaster';
import type { PaginationLink } from '@/types';

setPageTitle('Journal de classe');

interface PaginatedJournal {
    data: AgendaJournalEntry[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface PaginatedAssignments {
    data: AgendaAssignment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    journalEntries: PaginatedJournal;
    upcomingAssignments: PaginatedAssignments;
    pastAssignments: PaginatedAssignments;
    groupOptions: string[];
    schoolOptions: string[];
    academicYears: { id: number; year: string; is_current: boolean; is_archived: boolean }[];
    isAdmin: boolean;
    filters: {
        search: string;
        group: string;
        school: string;
        sort_field: 'date' | 'group' | 'subject';
        sort_dir: 'asc' | 'desc';
        assignment_type: '' | 'homework' | 'test';
        year: string | null;
    };
}>();

// ── Tabs ─────────────────────────────────────────────────────────────────
type Tab = 'journal' | 'assignments';
const initialTab = new URLSearchParams(usePage().url.split('?')[1] ?? '').get('tab');
const activeTab = ref<Tab>(initialTab === 'assignments' ? 'assignments' : 'journal');

// ── Filtres serveur ────────────────────────────────────────────────────────
const search = ref(props.filters.search);
const filterGroup = ref(props.filters.group);
const filterSchool = ref(props.filters.school);
const filterYear = ref<string | null>(props.filters.year ?? null);

const yearOptions = props.academicYears.map((y) => ({
    value: String(y.id),
    label: y.is_archived ? `${y.year} — archivée` : y.is_current ? `${y.year} — en cours` : y.year,
}));

// ── Sous-vue devoirs ──────────────────────────────────────────────────────
const showPast = ref(false);

// ── Filtres client (type + tri sur les devoirs) ───────────────────────────
const filterType = ref<'' | 'homework' | 'test'>(props.filters.assignment_type);
const sortField  = ref<'date' | 'group' | 'subject'>(props.filters.sort_field);
const sortDir    = ref<'asc' | 'desc'>(props.filters.sort_dir);

const today = new Date().toISOString().slice(0, 10);

const sortOptions = [
    { value: 'date', label: 'Date' },
    { value: 'group', label: 'Classe' },
    { value: 'subject', label: 'Matière' },
];

const typeOptions = [
    { value: 'homework', label: 'Devoirs' },
    { value: 'test', label: 'Interrogations' },
];

const groupSelectOptions = computed(() =>
    props.groupOptions.map((g) => ({ value: g, label: g })),
);
const schoolSelectOptions = computed(() =>
    props.schoolOptions.map((s) => ({ value: s, label: s })),
);

const activeFilterCount = computed(() => {
    let n = 0;
    if (search.value) n++;
    if (filterType.value) n++;
    if (filterGroup.value) n++;
    if (filterSchool.value) n++;
    if (sortField.value !== 'date' || sortDir.value !== 'asc') n++;

    return n;
});

function toggleDir() {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
}

// ── Requêtes serveur ──────────────────────────────────────────────────────
let searchTimer: ReturnType<typeof setTimeout> | null = null;

function applyServerFilters() {
    router.get(
        agenda.url(),
        {
            search:           search.value || undefined,
            group:            filterGroup.value || undefined,
            school:           filterSchool.value || undefined,
            sort_field:       sortField.value !== 'date' ? sortField.value : undefined,
            sort_dir:         sortDir.value !== 'desc' ? sortDir.value : undefined,
            assignment_type:  filterType.value || undefined,
            year:             filterYear.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watch(search, () => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(applyServerFilters, 300);
});

watch([filterGroup, filterSchool, filterType, filterYear], applyServerFilters);

watch([sortField, sortDir], applyServerFilters);

// ── Visibilité locale après suppression optimiste ─────────────────────────
const visibleUpcoming = computed(() =>
    props.upcomingAssignments.data.filter((a) => !hiddenIds.value.has(a.id)),
);

const visiblePast = computed(() =>
    props.pastAssignments.data.filter((a) => !hiddenIds.value.has(a.id)),
);

// ── Édition ───────────────────────────────────────────────────────────────
const editModalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const editingAssignment = ref<AgendaAssignment | null>(null);

const confirmDeleteRef = ref<InstanceType<typeof BaseModal> | null>(null);
const pendingDelete    = ref<{ id: number; title: string } | null>(null);
const hiddenIds        = ref(new Set<number>());
const toaster          = useToasterStore();
const editForm = useForm({
    type: 'homework' as 'homework' | 'test',
    title: '',
    scheduled_date: '',
    description: '',
});

function openEdit(id: number) {
    const a = props.upcomingAssignments.data.find((x) => x.id === id);
    if (!a || a.scheduled_date < today) return;

    editingAssignment.value = a;
    editForm.type = a.type;
    editForm.title = a.title;
    editForm.scheduled_date = a.scheduled_date;
    editForm.description = a.description ?? '';
    editModalRef.value?.open();
}

function submitEdit() {
    if (!editingAssignment.value) return;

    editForm.patch(updateAssignment.url({ assignment: editingAssignment.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            editModalRef.value?.close();
            toaster.success('Devoir modifié');
        },
    });
}

function requestDelete(id: number) {
    const a = props.upcomingAssignments.data.find((x) => x.id === id);

    if (!a) {
        return;
    }

    pendingDelete.value = { id, title: a.title };
    confirmDeleteRef.value?.open();
}

function confirmDelete() {
    if (!pendingDelete.value) {
        return;
    }

    const { id, title } = pendingDelete.value;

    confirmDeleteRef.value?.close();
    pendingDelete.value = null;
    hiddenIds.value = new Set([...hiddenIds.value, id]);
    toaster.deletable(
        `« ${title} » supprimé`,
        () => router.delete(destroyAssignment.url({ assignment: id }), { preserveScroll: true }),
        () => { hiddenIds.value.delete(id); hiddenIds.value = new Set(hiddenIds.value); },
    );
}

function goToAttendance(entry: AgendaJournalEntry) {
    router.get(attendances.url(), { date: entry.date, group: entry.group_slug });
}
</script>

<template>
    <!-- Barre de filtres -->
    <FilterBar
        title="Journal de classe"
        description="Retrouvez les notes de cours et planifiez les devoirs et interrogations par classe."
        :active-count="activeFilterCount"
    >
        <template #action>
            <div class="flex w-full gap-2 sm:w-auto">
                <Button
                    size="sm"
                    :variant="activeTab === 'journal' ? 'primary' : 'ghost'"
                    class="flex-1 sm:flex-none"
                    @click="activeTab = 'journal'"
                >
                    Journal
                    <span class="ml-1 text-[10px] font-normal opacity-60">{{ journalEntries.total }}</span>
                </Button>
                <Button
                    size="sm"
                    :variant="activeTab === 'assignments' ? 'primary' : 'ghost'"
                    class="flex-1 sm:flex-none"
                    @click="activeTab = 'assignments'"
                >
                    <span class="sm:hidden">Devoirs</span>
                    <span class="hidden sm:inline">Devoirs & Interros</span>
                    <span class="ml-1 text-[10px] font-normal opacity-60">{{ upcomingAssignments.total + pastAssignments.total }}</span>
                </Button>
            </div>
        </template>

        <template #filters>
            <SelectField
                v-if="isAdmin && yearOptions.length > 1"
                placeholder="Année en cours"
                :options="yearOptions"
                :model-value="filterYear"
                class="w-full lg:w-44"
                @update:model-value="(v) => (filterYear = v as string | null)"
            />

            <SelectField
                v-if="activeTab === 'assignments'"
                placeholder="Tous les types"
                :options="typeOptions"
                :model-value="filterType || null"
                class="w-full lg:w-40"
                @update:model-value="(v) => (filterType = (v as '' | 'homework' | 'test') ?? '')"
            />

            <SelectField
                v-if="groupSelectOptions.length > 1"
                placeholder="Toutes les classes"
                :options="groupSelectOptions"
                :model-value="filterGroup || null"
                class="w-full lg:w-44 lg:flex-1"
                @update:model-value="(v) => (filterGroup = (v as string) ?? '')"
            />

            <SelectField
                v-if="schoolSelectOptions.length > 1"
                placeholder="Toutes les écoles"
                :options="schoolSelectOptions"
                :model-value="filterSchool || null"
                class="w-full lg:w-44 lg:flex-1"
                @update:model-value="(v) => (filterSchool = (v as string) ?? '')"
            />

            <SearchInput
                v-model="search"
                :placeholder="activeTab === 'journal' ? 'Matière, classe, note…' : 'Titre, matière, classe…'"
                class="w-full lg:flex-1"
            />

            <div class="flex w-full flex-col gap-2 lg:w-auto">
                <p class="text-xs font-bold uppercase tracking-wide text-border-figma">Trier</p>
                <div class="flex items-center gap-2">
                    <SelectField
                        placeholder="Par défaut"
                        :options="sortOptions"
                        :model-value="sortField"
                        class="flex-1 lg:w-36"
                        @update:model-value="(v) => (sortField = (v as 'date' | 'group' | 'subject') ?? 'date')"
                    />
                    <button
                        type="button"
                        class="flex h-11.5 w-11 shrink-0 items-center justify-center rounded-2xl text-sm font-bold text-text-base outline-1 -outline-offset-1 outline-border-figma transition-colors hover:bg-gray-50"
                        :title="sortDir === 'asc' ? 'Croissant' : 'Décroissant'"
                        @click="toggleDir"
                    >
                        {{ sortDir === 'asc' ? '↑' : '↓' }}
                    </button>
                </div>
            </div>
        </template>
    </FilterBar>

    <!-- Journal -->
    <template v-if="activeTab === 'journal'">
        <EmptyState
            v-if="journalEntries.data.length === 0"
            :message="
                search || filterGroup || filterSchool
                    ? 'Aucun résultat pour ces filtres'
                    : 'Aucune note de journal'
            "
            class="rounded-2xl bg-white"
        />
        <div
            v-else
            class="overflow-hidden rounded-2xl bg-white shadow-sm outline -outline-offset-1 outline-neutral-300/10"
        >
            <div class="border-b border-neutral-300/10 px-6 py-4">
                <h2 class="text-xl font-bold text-text-base">
                    Journal de classe
                    <span class="text-border-figma"
                        >({{ journalEntries.total }})</span
                    >
                </h2>
            </div>
            <ul class="divide-y divide-neutral-100">
                <AgendaJournalRow
                    v-for="entry in journalEntries.data"
                    :key="entry.id"
                    :entry="entry"
                    @click="goToAttendance(entry)"
                />
            </ul>
            <div class="border-t border-neutral-100 px-6 py-4">
                <Pagination
                    :links="journalEntries.links"
                    :current-page="journalEntries.current_page"
                    :last-page="journalEntries.last_page"
                />
            </div>
        </div>
    </template>

    <!-- Devoirs & Interros -->
    <template v-if="activeTab === 'assignments'">
        <EmptyState
            v-if="upcomingAssignments.total + pastAssignments.total === 0"
            :message="
                search || filterType || filterGroup || filterSchool
                    ? 'Aucun résultat pour ces filtres'
                    : 'Aucun devoir ni interrogation'
            "
            class="rounded-2xl bg-white"
        />

        <div
            v-else
            class="overflow-hidden rounded-2xl bg-white shadow-sm outline -outline-offset-1 outline-neutral-300/10"
        >
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
                        @edit="openEdit"
                        @delete="requestDelete"
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
                            @edit="openEdit"
                            @delete="requestDelete"
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

    <!-- Modale édition -->
    <BaseModal ref="editModalRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">Modifier</h2>
                <p
                    v-if="editingAssignment"
                    class="mt-0.5 text-xs text-stone-400"
                >
                    {{ editingAssignment.subject }} ·
                    {{ editingAssignment.group }}
                </p>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submitEdit">
                <div class="flex flex-col gap-1.5">
                    <span
                        class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase"
                        >Type</span
                    >
                    <div class="flex gap-2">
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center rounded-2xl border py-2.5 font-manrope text-sm font-bold transition-all duration-150"
                            :class="
                                editForm.type === 'homework'
                                    ? 'border-blue bg-blue/5 text-blue ring-2 ring-blue/20'
                                    : 'border-border-figma bg-white text-text-base'
                            "
                        >
                            <input
                                v-model="editForm.type"
                                type="radio"
                                value="homework"
                                class="sr-only"
                            />
                            Devoir
                        </label>
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center rounded-2xl border py-2.5 font-manrope text-sm font-bold transition-all duration-150"
                            :class="
                                editForm.type === 'test'
                                    ? 'border-blue bg-blue/5 text-blue ring-2 ring-blue/20'
                                    : 'border-border-figma bg-white text-text-base'
                            "
                        >
                            <input
                                v-model="editForm.type"
                                type="radio"
                                value="test"
                                class="sr-only"
                            />
                            Interrogation
                        </label>
                    </div>
                </div>

                <InputLabel
                    v-model="editForm.title"
                    label="Titre"
                    placeholder="Ex : Chapitre 3 – exercices"
                />

                <DateField
                    label="Date"
                    :model-value="editForm.scheduled_date"
                    @update:model-value="editForm.scheduled_date = $event"
                />

                <div class="flex flex-col gap-1.5">
                    <label
                        class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase"
                    >
                        Description
                        <span class="font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea
                        v-model="editForm.description"
                        rows="3"
                        placeholder="Précisions…"
                        class="w-full resize-none rounded-2xl border border-border-figma bg-white px-3 py-3 font-manrope text-sm text-text-base transition-all duration-150 outline-none placeholder:font-normal placeholder:text-gray-400 focus:border-blue focus:ring-2 focus:ring-blue/20"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="editModalRef?.close()"
                        >Annuler</Button
                    >
                    <Button
                        type="submit"
                        variant="primary"
                        size="sm"
                        :loading="editForm.processing"
                        >Enregistrer</Button
                    >
                </div>
            </form>
        </div>
    </BaseModal>

    <!-- Modale confirmation suppression -->
    <BaseModal ref="confirmDeleteRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">Supprimer ce devoir ?</h2>
                <p v-if="pendingDelete" class="mt-1 text-sm text-stone-500">
                    « {{ pendingDelete.title }} »
                </p>
                <p class="mt-1 text-xs text-stone-400">Cette action est irréversible.</p>
            </div>
            <div class="flex justify-end gap-2">
                <Button variant="ghost" size="sm" @click="confirmDeleteRef?.close()">Annuler</Button>
                <Button variant="danger" size="sm" @click="confirmDelete">Supprimer</Button>
            </div>
        </div>
    </BaseModal>
</template>
