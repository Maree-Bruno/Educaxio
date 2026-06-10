<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { AgendaAssignment } from '@/components/widgets/AgendaAssignmentRow.vue';
import AgendaAssignmentsPanel from '@/components/widgets/AgendaAssignmentsPanel.vue';
import AgendaCreateAssignmentModal from '@/components/widgets/AgendaCreateAssignmentModal.vue';
import AgendaCreateJournalModal from '@/components/widgets/AgendaCreateJournalModal.vue';
import AgendaEditAssignmentModal from '@/components/widgets/AgendaEditAssignmentModal.vue';
import AgendaJournalPanel from '@/components/widgets/AgendaJournalPanel.vue';
import type { AgendaJournalEntry } from '@/components/widgets/AgendaJournalRow.vue';
import Button from '@/components/widgets/Button.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import FilterBar from '@/components/widgets/FilterBar.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { agenda, attendances } from '@/routes';
import { destroy as destroyAssignment } from '@/routes/assignments';
import { useToasterStore } from '@/stores/toaster';
import type { Paginator } from '@/types';

setPageTitle('Agenda');

interface AgendaLessonOption {
    id: number;
    label: string;
    schedule_pattern: { day_of_week: number; slot_label: string }[];
    next_assignment_date: string | null;
}

const props = defineProps<{
    journalEntries:      Paginator<AgendaJournalEntry>;
    upcomingAssignments: Paginator<AgendaAssignment>;
    pastAssignments:     Paginator<AgendaAssignment>;
    groupOptions:        string[];
    schoolOptions:       string[];
    lessonOptions:       AgendaLessonOption[];
    academicYears: {
        id: number;
        year: string;
        is_current: boolean;
        is_archived: boolean;
    }[];
    isAdmin: boolean;
    filters: {
        search:          string;
        group:           string;
        school:          string;
        sort_field:      'date' | 'group' | 'subject';
        sort_dir:        'asc' | 'desc';
        assignment_type: '' | 'homework' | 'test';
        year:            string | null;
    };
}>();

// ── Tabs ─────────────────────────────────────────────────────────────────────
type Tab = 'journal' | 'assignments';
const initialTab = new URLSearchParams(usePage().url.split('?')[1] ?? '').get('tab');
const activeTab = ref<Tab>(initialTab === 'assignments' ? 'assignments' : 'journal');

// ── Filtres serveur ───────────────────────────────────────────────────────────
const search       = ref(props.filters.search);
const filterGroup  = ref(props.filters.group);
const filterSchool = ref(props.filters.school);
const filterYear   = ref<string | null>(props.filters.year ?? null);

const yearOptions = props.academicYears.map((y) => ({
    value: String(y.id),
    label: y.is_archived
        ? `${y.year} — archivée`
        : y.is_current
          ? `${y.year} — en cours`
          : y.year,
}));

// ── Filtres client (type + tri sur les devoirs) ───────────────────────────────
const filterType = ref<'' | 'homework' | 'test'>(props.filters.assignment_type);
const sortField  = ref<'date' | 'group' | 'subject'>(props.filters.sort_field);
const sortDir    = ref<'asc' | 'desc'>(props.filters.sort_dir);

const sortOptions = [
    { value: 'date',    label: 'Date' },
    { value: 'group',   label: 'Classe' },
    { value: 'subject', label: 'Matière' },
];

const typeOptions = [
    { value: 'homework', label: 'Devoirs' },
    { value: 'test',     label: 'Interrogations' },
];

const groupSelectOptions  = computed(() => props.groupOptions.map((g) => ({ value: g, label: g })));
const schoolSelectOptions = computed(() => props.schoolOptions.map((s) => ({ value: s, label: s })));

const activeFilterCount = computed(() => {
    let n = 0;

    if (search.value) {
        n++;
    }

    if (filterType.value) {
        n++;
    }

    if (filterGroup.value) {
        n++;
    }

    if (filterSchool.value) {
        n++;
    }

    if (sortField.value !== 'date' || sortDir.value !== 'asc') {
        n++;
    }

    return n;
});

const hasJournalFilters    = computed(() => !!(search.value || filterGroup.value || filterSchool.value));
const hasAssignmentFilters = computed(() => !!(search.value || filterType.value || filterGroup.value || filterSchool.value));

function toggleDir() {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;

function applyServerFilters() {
    router.get(
        agenda.url(),
        {
            search:          search.value || undefined,
            group:           filterGroup.value || undefined,
            school:          filterSchool.value || undefined,
            sort_field:      sortField.value !== 'date' ? sortField.value : undefined,
            sort_dir:        sortDir.value !== 'desc' ? sortDir.value : undefined,
            assignment_type: filterType.value || undefined,
            year:            filterYear.value || undefined,
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

// ── Suppression optimiste ─────────────────────────────────────────────────────
const hiddenIds = ref(new Set<number>());

const visibleUpcoming = computed(() => props.upcomingAssignments.data.filter((a) => !hiddenIds.value.has(a.id)));
const visiblePast     = computed(() => props.pastAssignments.data.filter((a) => !hiddenIds.value.has(a.id)));

// ── Création ─────────────────────────────────────────────────────────────────
const createAssignmentRef = ref<InstanceType<typeof AgendaCreateAssignmentModal> | null>(null);
const createJournalRef    = ref<InstanceType<typeof AgendaCreateJournalModal> | null>(null);

// ── Édition ───────────────────────────────────────────────────────────────────
const editModalRef = ref<InstanceType<typeof AgendaEditAssignmentModal> | null>(null);

function openEdit(id: number) {
    const a = props.upcomingAssignments.data.find((x) => x.id === id);

    if (a) {
        editModalRef.value?.open(a);
    }
}

// ── Suppression ───────────────────────────────────────────────────────────────
const toaster       = useToasterStore();
const pendingDelete = ref<{ id: number; title: string } | null>(null);

function requestDelete(id: number) {
    const a = props.upcomingAssignments.data.find((x) => x.id === id);

    if (!a) {
        return;
    }

    pendingDelete.value = { id, title: a.title };
}

function confirmDelete() {
    if (!pendingDelete.value) {
        return;
    }

    const { id, title } = pendingDelete.value;

    pendingDelete.value = null;
    hiddenIds.value = new Set([...hiddenIds.value, id]);
    toaster.deletable(
        `« ${title} » supprimé`,
        () => router.delete(destroyAssignment.url({ assignment: id }), { preserveScroll: true }),
        () => {
            hiddenIds.value.delete(id);
            hiddenIds.value = new Set(hiddenIds.value);
        },
    );
}

function goToAttendance(entry: AgendaJournalEntry) {
    router.get(attendances.url(), { date: entry.date, group: entry.group_slug });
}
</script>

<template>
    <ConfirmModal
        :open="pendingDelete !== null"
        :title="`Supprimer « ${pendingDelete?.title ?? ''} » ?`"
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />

    <AgendaEditAssignmentModal ref="editModalRef" />
    <AgendaCreateAssignmentModal ref="createAssignmentRef" :lesson-options="lessonOptions" />
    <AgendaCreateJournalModal ref="createJournalRef" :lesson-options="lessonOptions" />

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
                label="Année"
                placeholder="Année en cours"
                :options="yearOptions"
                :model-value="filterYear"
                class="w-full lg:w-44"
                @update:model-value="(v) => (filterYear = v as string | null)"
            />

            <SelectField
                v-if="activeTab === 'assignments'"
                label="Type"
                placeholder="Tous les types"
                :options="typeOptions"
                :model-value="filterType || null"
                class="w-full lg:w-40"
                @update:model-value="(v) => (filterType = (v as '' | 'homework' | 'test') ?? '')"
            />

            <SelectField
                v-if="groupSelectOptions.length > 1"
                label="Classe"
                placeholder="Toutes les classes"
                :options="groupSelectOptions"
                :model-value="filterGroup || null"
                class="w-full lg:w-44 lg:flex-1"
                @update:model-value="(v) => (filterGroup = (v as string) ?? '')"
            />

            <SelectField
                v-if="schoolSelectOptions.length > 1"
                label="École"
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
                <p class="text-xs font-bold tracking-wide text-border-figma uppercase">Trier</p>
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
                        :aria-label="sortDir === 'asc' ? 'Croissant' : 'Décroissant'"
                        @click="toggleDir"
                    >
                        {{ sortDir === 'asc' ? '↑' : '↓' }}
                    </button>
                </div>
            </div>
        </template>
    </FilterBar>

    <AgendaJournalPanel
        v-if="activeTab === 'journal'"
        :journal-entries="journalEntries"
        :has-active-filters="hasJournalFilters"
        @click-entry="goToAttendance"
        @create="createJournalRef?.open()"
    />

    <AgendaAssignmentsPanel
        v-if="activeTab === 'assignments'"
        :upcoming-assignments="upcomingAssignments"
        :past-assignments="pastAssignments"
        :visible-upcoming="visibleUpcoming"
        :visible-past="visiblePast"
        :has-active-filters="hasAssignmentFilters"
        @edit="openEdit"
        @delete="requestDelete"
        @create="createAssignmentRef?.open()"
    />
</template>
