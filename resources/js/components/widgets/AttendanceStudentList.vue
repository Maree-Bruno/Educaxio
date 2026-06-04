<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { store } from '@/routes/attendances';
import AttendanceStatusButton from '@/components/widgets/AttendanceStatusButton.vue';
import Button from '@/components/widgets/Button.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import SortTh from '@/components/widgets/SortTh.vue';
import StudentCount from '@/components/widgets/StudentCount.vue';
import { useStudentSort, studentRowNumber } from '@/composables/useStudentSort';
import { attendanceStatusClasses, type AttendanceStatus, type PaginationLink } from '@/types';

const emit = defineEmits<{
    'update:localStatuses': [statuses: Record<number, AttendanceStatus | null>]
}>();

interface Student { id: number; lastname: string; firstname: string }
interface Entry   { creneau: string; lesson_id: number }
interface Status  { student_id: number; type: string; motive: string | null }

const props = defineProps<{
    students:      Student[];
    statuses:      Status[];
    entries:       Entry[];
    selectedEntry: string | null;
    date:          string;
    lastSavedAt:   string | null;
}>();

const isEditable = computed(() => props.date <= new Date().toISOString().slice(0, 10));

const initialStatuses: Record<number, AttendanceStatus | null> = {};
for (const s of props.students) {
    const found = props.statuses.find((st) => st.student_id === s.id);
    initialStatuses[s.id] = (found?.type as AttendanceStatus) ?? null;
}
const localStatuses = ref<Record<number, AttendanceStatus | null>>(initialStatuses);

watch(localStatuses, (val) => emit('update:localStatuses', { ...val }), { deep: true });

const form = useForm({});

const STATUS_BUTTONS: { key: AttendanceStatus | null; label: string; title: string }[] = [
    { key: null,       label: 'P',  title: 'Présent'         },
    { key: 'Absent',   label: 'A',  title: 'Absent'          },
    { key: 'Late',     label: 'AT', title: 'Arrivée tardive' },
    { key: 'Excluded', label: 'E',  title: 'Exclu'           },
];

function toggleStatus(studentId: number, status: AttendanceStatus) {
    localStatuses.value[studentId] = localStatuses.value[studentId] === status ? null : status;
}

function setStatusFromSelect(studentId: number, value: string) {
    localStatuses.value[studentId] = (value as AttendanceStatus) || null;
}

function setAllPresent() {
    for (const id in localStatuses.value) {
        localStatuses.value[id] = null;
    }
}

function save() {
    const entry = props.entries.find((e) => e.creneau === props.selectedEntry);
    if (!entry) return;

    const statuses = Object.entries(localStatuses.value)
        .filter(([, type]) => type !== null)
        .map(([student_id, type]) => ({ student_id: Number(student_id), type, motive: null }));

    form.transform(() => ({
        lesson_id: entry.lesson_id,
        date: props.date,
        statuses,
    })).post(store.url(), { preserveScroll: true });
}

const search = ref('');
const filterStatus = ref('');
const { sortCol, sortDir, sortBy } = useStudentSort();

const STATUS_FILTER_OPTIONS = [
    { value: 'Present',  label: 'Présent'        },
    { value: 'Absent',   label: 'Absent'          },
    { value: 'Late',     label: 'Arrivée tardive' },
    { value: 'Excluded', label: 'Exclu'           },
];

const filteredStudents = computed(() => {
    const term = search.value.trim().toLowerCase();
    let list = term
        ? props.students.filter(
            (s) => s.lastname.toLowerCase().includes(term) || s.firstname.toLowerCase().includes(term),
          )
        : [...props.students];

    if (filterStatus.value) {
        const target = filterStatus.value === 'Present' ? null : filterStatus.value as AttendanceStatus;
        list = list.filter((s) => localStatuses.value[s.id] === target);
    }

    const primary   = sortCol.value === 'firstname' ? 'firstname' : 'lastname';
    const secondary = primary === 'lastname' ? 'firstname' : 'lastname';
    list.sort((a, b) => {
        const cmp = a[primary].localeCompare(b[primary], 'fr') || a[secondary].localeCompare(b[secondary], 'fr');
        return sortDir.value === 'desc' ? -cmp : cmp;
    });

    return list;
});

const PAGE_SIZE = 10;
const currentPage = ref(1);

watch([search, filterStatus, sortCol, sortDir], () => { currentPage.value = 1; });

const totalPages  = computed(() => Math.ceil(filteredStudents.value.length / PAGE_SIZE));
const pageStudents = computed(() =>
    filteredStudents.value.slice((currentPage.value - 1) * PAGE_SIZE, currentPage.value * PAGE_SIZE),
);

const paginationLinks = computed<PaginationLink[]>(() => {
    const links: PaginationLink[] = [{ url: null, label: 'Previous', active: false }];

    for (let i = 1; i <= totalPages.value; i++) {
        links.push({ url: null, label: String(i), active: i === currentPage.value });
    }

    links.push({ url: null, label: 'Next', active: false });

    return links;
});

const signalCount = computed(() => Object.values(localStatuses.value).filter((v) => v !== null).length);
</script>

<template>
    <div class="min-w-0 flex-1 overflow-hidden rounded-2xl">
        <div class="border-b border-neutral-300/10 bg-white px-6 py-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-stone-900">
                        Liste des élèves
                        <StudentCount
                            v-if="students.length > 0"
                            :total="students.length"
                            :filtered="(search || filterStatus) ? filteredStudents.length : undefined"
                        />
                    </h2>
                    <p class="mt-0.5 text-xs text-stone-400">
                        Les élèves non marqués sont considérés présents. Cliquez sur Valider pour enregistrer.
                    </p>
                </div>
                <div v-if="students.length > 0" class="flex shrink-0 items-center gap-3">
                    <span v-if="lastSavedAt" class="hidden text-xs text-stone-400 sm:block">
                        Enregistré le {{ lastSavedAt }}
                    </span>
                    <template v-if="isEditable">
                        <Button variant="ghost" size="sm" @click="setAllPresent">Tous présent</Button>
                        <Button variant="primary" size="sm" :loading="form.processing" @click="save">Valider</Button>
                    </template>
                    <span v-else class="text-xs font-bold text-stone-400">Lecture seule</span>
                </div>
            </div>
            <div v-if="students.length > 0" class="mt-4 flex flex-col gap-2">
                <p class="text-xs font-bold uppercase tracking-wide text-border-figma">Filtrer</p>
                <div class="flex gap-2">
                    <SelectField
                        placeholder="Tous les statuts"
                        :options="STATUS_FILTER_OPTIONS"
                        :model-value="filterStatus || null"
                        class="w-44 shrink-0"
                        @update:model-value="filterStatus = ($event as string) ?? ''"
                    />
                    <SearchInput
                        v-model="search"
                        placeholder="Rechercher un élève…"
                        class="flex-1"
                    />
                </div>
            </div>
        </div>

        <EmptyState
            v-if="!selectedEntry"
            :message="entries.length === 0 ? 'Aucun cours planifié ce jour' : 'Sélectionnez un créneau horaire'"
            class="bg-white"
        />

        <template v-else>
            <ul class="divide-y divide-neutral-100 bg-white sm:hidden">
                <li
                    v-for="(student, index) in pageStudents"
                    :key="student.id"
                    class="flex items-center gap-3 px-4 py-3"
                >
                    <span class="w-6 shrink-0 text-xs text-stone-400">
                        {{ studentRowNumber(index, currentPage, PAGE_SIZE, filteredStudents.length, sortDir) }}
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-stone-900">
                        {{ student.lastname }} {{ student.firstname }}
                    </span>
                    <select
                        :aria-label="`Statut de ${student.lastname} ${student.firstname}`"
                        :disabled="!isEditable"
                        class="shrink-0 appearance-none rounded-xl px-3 py-2 text-xs font-bold transition-colors focus:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                        :class="attendanceStatusClasses(localStatuses[student.id])"
                        :value="localStatuses[student.id] ?? ''"
                        @change="setStatusFromSelect(student.id, ($event.target as HTMLSelectElement).value)"
                    >
                        <option value="">Présent</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Arrivée tardive</option>
                        <option value="Excluded">Exclu</option>
                    </select>
                </li>
                <li v-if="students.length === 0">
                    <EmptyState message="Aucun élève dans ce groupe" />
                </li>
            </ul>

            <table class="hidden w-full border-collapse bg-white text-left sm:table">
                <caption class="sr-only">Liste des présences</caption>
                <thead>
                    <tr class="bg-gray-100">
                        <th scope="col" class="w-16 px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">N°</th>
                        <SortTh col="lastname" label="Nom" :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                        <SortTh col="firstname" label="Prénom" :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(student, index) in pageStudents"
                        :key="student.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-6 py-5 text-sm text-stone-400">
                            {{ studentRowNumber(index, currentPage, PAGE_SIZE, filteredStudents.length, sortDir) }}
                        </td>
                        <td class="px-6 py-5 text-base font-medium text-stone-900">{{ student.lastname }}</td>
                        <td class="px-6 py-5 text-base text-stone-900">{{ student.firstname }}</td>
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-center gap-1">
                                <AttendanceStatusButton
                                    v-for="btn in STATUS_BUTTONS"
                                    :key="btn.label"
                                    :status-key="btn.key"
                                    :label="btn.label"
                                    :title="btn.title"
                                    :active="localStatuses[student.id] === btn.key"
                                    :disabled="!isEditable"
                                    @click="btn.key === null ? (localStatuses[student.id] = null) : toggleStatus(student.id, btn.key)"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="students.length === 0">
                        <td colspan="4"><EmptyState message="Aucun élève dans ce groupe" /></td>
                    </tr>
                </tbody>
            </table>

            <div class="rounded-b-2xl bg-gray-100 px-6 py-4">
                <div class="flex items-center justify-between">
                    <p v-if="signalCount > 0" class="text-sm text-border-figma">
                        {{ signalCount }} signalement{{ signalCount > 1 ? 's' : '' }}
                    </p>
                    <span v-else />
                    <Pagination
                        :links="paginationLinks"
                        :current-page="currentPage"
                        :last-page="totalPages"
                        @update:model-value="currentPage = $event"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
