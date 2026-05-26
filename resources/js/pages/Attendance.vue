<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AttendanceStatusButton from '@/components/widgets/AttendanceStatusButton.vue';
import Button from '@/components/widgets/Button.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { PaginationLink } from '@/types/models';

setPageTitle('Présences');

interface Entry   { id: number; label: string; lesson_id: number; subject: string; group: string; school: string }
interface Student { id: number; lastname: string; firstname: string }
interface Status  { student_id: number; type: string; motive: string | null }

const props = defineProps<{
    entries:        Entry[];
    selectedEntry:  number | null;
    selectedSchool: string | null;
    selectedGroup:  string | null;
    date:           string;
    students:       Student[];
    statuses:       Status[];
    attendanceId:   number | null;
    lastSavedAt:    string | null;
}>();

type StatusType = 'Absent' | 'Late' | 'Excluded';

const localStatuses = ref<Record<number, StatusType | null>>(
    Object.fromEntries(
        props.students.map((s) => [
            s.id,
            (props.statuses.find((st) => st.student_id === s.id)?.type as StatusType) ?? null,
        ]),
    ),
);

function nav(params: Record<string, string | number | null | undefined>) {
    router.get('/attendances', params, { preserveState: false });
}

function changeDate(e: Event) {
    nav({ date: (e.target as HTMLInputElement).value });
}

function toggleStatus(studentId: number, status: StatusType) {
    localStatuses.value[studentId] = localStatuses.value[studentId] === status ? null : status;
}

function setStatusFromSelect(studentId: number, value: string) {
    localStatuses.value[studentId] = (value as StatusType) || null;
}

function setAllPresent() {
    for (const id in localStatuses.value) {
        localStatuses.value[id] = null;
    }
}

function save() {
    const entry = props.entries.find((e) => e.id === props.selectedEntry);

    if (!entry) {
        return;
    }

    const statuses = Object.entries(localStatuses.value)
        .filter(([, type]) => type !== null)
        .map(([student_id, type]) => ({ student_id: Number(student_id), type, motive: null }));

    router.post('/attendances', {
        lesson_id: entry.lesson_id,
        date:      props.date,
        statuses,
    }, { preserveScroll: true });
}

const journal = ref('');

const STATUS_BUTTONS: { key: StatusType | null; label: string; title: string }[] = [
    { key: null,       label: 'P',  title: 'Présent'         },
    { key: 'Absent',   label: 'A',  title: 'Absent'          },
    { key: 'Late',     label: 'AT', title: 'Arrivée tardive' },
    { key: 'Excluded', label: 'E',  title: 'Exclu'           },
];

const entryOptions = computed(() =>
    props.entries.map((e) => ({
        value: e.id,
        label: `${e.label} — ${e.subject} · ${e.group}`,
    })),
);

function selectClass(status: StatusType | null): string {
    if (status === 'Absent') {
        return 'bg-red-100 text-red-800';
    }

    if (status === 'Late') {
        return 'bg-yellow-100 text-yellow-800';
    }

    if (status === 'Excluded') {
        return 'bg-gray-100 text-gray-700';
    }

    return 'bg-green-100 text-green-800';
}

const isEditable = computed(() => props.date <= new Date().toISOString().slice(0, 10));

const PAGE_SIZE = 10;
const currentPage  = ref(1);
const totalPages   = computed(() => Math.ceil(props.students.length / PAGE_SIZE));
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
</script>

<template>
    <!-- Filtres -->
    <div class="rounded-2xl bg-white px-6 py-5 shadow-sm outline -outline-offset-1 outline-neutral-300/10">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:gap-6">

            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold uppercase leading-4 tracking-wide text-border-figma">Date</label>
                <input
                    type="date"
                    :value="date"
                    class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline -outline-offset-1 outline-border-figma focus:outline-blue"
                    @change="changeDate"
                />
            </div>

            <SelectField
                label="Heure de cours"
                placeholder="Choisir…"
                :options="entryOptions"
                :model-value="selectedEntry"
                :disabled="entries.length === 0"
                @update:model-value="val => val !== null && nav({ date, entry: val })"
            />

            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold uppercase leading-4 tracking-wide text-border-figma">École</label>
                <div class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base">
                    <span v-if="selectedSchool">{{ selectedSchool }}</span>
                    <span v-else class="text-border-figma">—</span>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold uppercase leading-4 tracking-wide text-border-figma">Classe</label>
                <div class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base">
                    <span v-if="selectedGroup">{{ selectedGroup }}</span>
                    <span v-else class="text-border-figma">—</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Contenu -->
    <div class="flex flex-col gap-8 xl:flex-row xl:items-start">

        <!-- Tableau élèves -->
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
                        <Button variant="primary" size="sm" @click="save">Valider</Button>
                    </template>
                    <span v-else class="text-xs font-bold text-stone-400">Lecture seule</span>
                </div>
            </div>

            <!-- État vide -->
            <div v-if="!selectedEntry" class="bg-white px-6 py-16 text-center text-sm font-bold text-border-figma">
                {{
                    entries.length === 0
                        ? 'Aucun cours planifié ce jour'
                        : 'Sélectionnez un créneau horaire'
                }}
            </div>

            <template v-else>

                <!-- Mobile -->
                <ul class="sm:hidden divide-y divide-neutral-100 bg-white">
                    <li
                        v-for="(student, index) in pageStudents"
                        :key="student.id"
                        class="flex items-center gap-3 px-4 py-3"
                    >
                        <span class="w-6 shrink-0 text-xs text-stone-400">{{ String((currentPage - 1) * PAGE_SIZE + index + 1).padStart(2, '0') }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-stone-900">{{ student.lastname }} {{ student.firstname }}</span>
                        <select
                            :disabled="!isEditable"
                            class="shrink-0 appearance-none rounded-xl px-3 py-2 text-xs font-bold transition-colors focus:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                            :class="selectClass(localStatuses[student.id])"
                            :value="localStatuses[student.id] ?? ''"
                            @change="setStatusFromSelect(student.id, ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">Présent</option>
                            <option value="Absent">Absent</option>
                            <option value="Late">Arrivée tardive</option>
                            <option value="Excluded">Exclu</option>
                        </select>
                    </li>
                    <li v-if="students.length === 0" class="px-4 py-16 text-center text-sm font-bold text-border-figma">
                        Aucun élève dans ce groupe
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
                            <td colspan="3" class="px-6 py-16 text-center text-sm font-bold text-border-figma">
                                Aucun élève dans ce groupe
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="rounded-b-2xl bg-gray-100 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-border-figma">
                            {{ students.length }} élève{{ students.length > 1 ? 's' : '' }}
                            <template v-if="Object.values(localStatuses).some(v => v !== null)">
                                · {{ Object.values(localStatuses).filter(v => v !== null).length }} signalement(s)
                            </template>
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

        <!-- Sidebar -->
        <div class="flex w-full shrink-0 flex-col gap-8 xl:w-72">

            <!-- Journal de classe -->
            <div class="flex flex-col gap-4 rounded-2xl bg-white p-6">
                <h3 class="text-base font-bold text-stone-800">Journal de classe</h3>
                <textarea
                    v-model="journal"
                    rows="6"
                    placeholder="Notes du cours…"
                    class="w-full resize-none rounded-xl bg-bg-primary p-4 text-sm text-text-base placeholder:text-border-figma focus:outline-none"
                />
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-stone-500">Fonctionnalité à venir</span>
                    <button type="button" disabled class="text-xs font-bold text-blue opacity-40">
                        Sauvegarder
                    </button>
                </div>
            </div>

            <!-- Devoirs & Interros -->
            <div class="flex flex-col gap-6 rounded-2xl bg-white p-6 shadow-sm outline -outline-offset-1 outline-neutral-300/10">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold leading-6 text-stone-900">Devoirs & Interros</h3>
                    <button
                        type="button"
                        disabled
                        class="flex h-6 w-6 items-center justify-center rounded-lg bg-orange text-sm font-bold text-white opacity-40"
                    >
                        +
                    </button>
                </div>
                <p class="text-xs italic text-stone-400">Fonctionnalité à venir</p>
            </div>

        </div>
    </div>
</template>