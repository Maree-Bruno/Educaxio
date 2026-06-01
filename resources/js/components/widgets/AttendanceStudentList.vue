<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { store } from '@/routes/attendances';
import AttendanceStatusButton from '@/components/widgets/AttendanceStatusButton.vue';
import Button from '@/components/widgets/Button.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import { attendanceStatusClasses, type AttendanceStatus, type PaginationLink } from '@/types';

interface Student { id: number; lastname: string; firstname: string }
interface Entry   { id: number; lesson_id: number }
interface Status  { student_id: number; type: string; motive: string | null }

const props = defineProps<{
    students:      Student[];
    statuses:      Status[];
    entries:       Entry[];
    selectedEntry: number | null;
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
    const entry = props.entries.find((e) => e.id === props.selectedEntry);
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

const PAGE_SIZE = 10;
const currentPage = ref(1);
const totalPages  = computed(() => Math.ceil(props.students.length / PAGE_SIZE));
const pageStudents = computed(() =>
    props.students.slice((currentPage.value - 1) * PAGE_SIZE, currentPage.value * PAGE_SIZE),
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
        <!-- En-tête -->
        <div class="flex items-center justify-between border-b border-neutral-300/10 bg-white px-6 py-5">
            <h2 class="text-xl font-bold text-stone-900">Liste des élèves</h2>
            <div v-if="students.length > 0" class="flex items-center gap-3">
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

        <!-- État vide -->
        <EmptyState
            v-if="!selectedEntry"
            :message="entries.length === 0 ? 'Aucun cours planifié ce jour' : 'Sélectionnez un créneau horaire'"
            class="bg-white"
        />

        <template v-else>
            <!-- Mobile -->
            <ul class="divide-y divide-neutral-100 bg-white sm:hidden">
                <li
                    v-for="(student, index) in pageStudents"
                    :key="student.id"
                    class="flex items-center gap-3 px-4 py-3"
                >
                    <span class="w-6 shrink-0 text-xs text-stone-400">
                        {{ String((currentPage - 1) * PAGE_SIZE + index + 1).padStart(2, '0') }}
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-stone-900">
                        {{ student.lastname }} {{ student.firstname }}
                    </span>
                    <select
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

            <!-- Desktop -->
            <table class="hidden w-full border-collapse bg-white text-left sm:table">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="w-16 px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">N°</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Nom de l'élève</th>
                        <th class="px-6 py-4 text-center text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(student, index) in pageStudents"
                        :key="student.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-6 py-5 text-sm text-stone-400">
                            {{ String((currentPage - 1) * PAGE_SIZE + index + 1).padStart(2, '0') }}
                        </td>
                        <td class="px-6 py-5 text-base text-stone-900">
                            {{ student.lastname }} {{ student.firstname }}
                        </td>
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
                        <td colspan="3"><EmptyState message="Aucun élève dans ce groupe" /></td>
                    </tr>
                </tbody>
            </table>

            <div class="rounded-b-2xl bg-gray-100 px-6 py-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-border-figma">
                        {{ students.length }} élève{{ students.length > 1 ? 's' : '' }}
                        <template v-if="signalCount > 0">· {{ signalCount }} signalement(s)</template>
                    </p>
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