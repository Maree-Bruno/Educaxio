<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { attendances } from '@/routes';
import AttendanceAssignments from '@/components/widgets/AttendanceAssignments.vue';
import type { Assignment } from '@/components/widgets/AttendanceAssignments.vue';
import AttendanceJournal from '@/components/widgets/AttendanceJournal.vue';
import AttendanceStats from '@/components/widgets/AttendanceStats.vue';
import AttendanceStudentList from '@/components/widgets/AttendanceStudentList.vue';
import DateField from '@/components/widgets/DateField.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { setPageTitle } from '@/composables/usePageTitle';

setPageTitle('Présences');

interface Entry {
    creneau: string;
    label: string;
    lesson_id: number;
    subject: string;
    group: string;
    school: string;
}
interface Student {
    id: number;
    lastname: string;
    firstname: string;
}
interface Status {
    student_id: number;
    type: string;
    motive: string | null;
}
interface LessonNoteData {
    id: number;
    notes: string | null;
    savedAt: string | null;
}

const props = defineProps<{
    entries: Entry[];
    selectedEntry: string | null;
    selectedSchool: string | null;
    selectedGroup: string | null;
    date: string;
    students: Student[];
    statuses: Status[];
    attendanceId: number | null;
    lastSavedAt: string | null;
    lessonNote: LessonNoteData | null;
    lessonId: number | null;
    assignments: Assignment[];
    nextAssignmentDate: string | null;
    schedulePattern: { day_of_week: number; slot_label: string }[];
}>();

function nav(params: Record<string, string | number | null | undefined>) {
    router.get(attendances.url(), params, { preserveState: false });
}

const entryOptions = computed(() =>
    props.entries.map((e) => ({
        value: e.creneau,
        label: `${e.label} — ${e.subject} · ${e.group}`,
    })),
);

const liveStatuses = ref<Record<number, string | null>>(
    Object.fromEntries(props.statuses.map((s) => [s.student_id, s.type])),
);

const sessionStats = computed(() => {
    const total = props.students.length;
    const values = Object.values(liveStatuses.value);
    const absences = values.filter((v) => v === 'Absent').length;
    const lates = values.filter((v) => v === 'Late').length;
    const exclusions = values.filter((v) => v === 'Excluded').length;
    return {
        sessions: props.attendanceId ? 1 : 0,
        absences,
        lates,
        exclusions,
        rate:
            props.attendanceId && total > 0
                ? Math.round(((total - absences) / total) * 1000) / 10
                : null,
    };
});
</script>

<template>
    <!-- Filtres -->
    <div
        class="rounded-2xl bg-white shadow-sm outline -outline-offset-1 outline-neutral-300/10"
    >
        <div class="border-b border-neutral-300/10 px-6 py-4">
            <p class="text-sm font-bold text-text-base">
                Sélection de l'heure de cours
            </p>
            <p class="mt-0.5 text-xs text-stone-400">
                Sélectionnez une date et un créneau pour afficher et saisir les
                présences
            </p>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:gap-6">
                <DateField
                    label="Date"
                    :model-value="date"
                    :max="new Date().toISOString().slice(0, 10)"
                    class="w-full"
                    @update:model-value="(val) => nav({ date: val })"
                />

                <SelectField
                    label="Heure de cours"
                    placeholder="Choisir…"
                    :options="entryOptions"
                    :model-value="selectedEntry"
                    :disabled="entries.length === 0"
                    @update:model-value="
                        (val) => val !== null && nav({ date, creneau: val })
                    "
                />

                <div class="flex flex-col gap-2">
                    <label
                        class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase"
                        >École</label
                    >
                    <div
                        class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base"
                    >
                        <span v-if="selectedSchool">{{ selectedSchool }}</span>
                        <span v-else class="text-border-figma">—</span>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label
                        class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase"
                        >Classe</label
                    >
                    <div
                        class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base"
                    >
                        <span v-if="selectedGroup">{{ selectedGroup }}</span>
                        <span v-else class="text-border-figma">—</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu -->
    <div class="flex flex-col gap-8 xl:flex-row xl:items-start">
        <AttendanceStudentList
            :students="students"
            :statuses="statuses"
            :entries="entries"
            :selected-entry="selectedEntry"
            :date="date"
            :last-saved-at="lastSavedAt"
            @update:local-statuses="liveStatuses = $event"
        />

        <!-- Sidebar -->
        <div class="flex w-full shrink-0 flex-col gap-8 xl:w-72">
            <AttendanceJournal
                :lesson-note="lessonNote"
                :lesson-id="lessonId"
                :date="date"
                :selected-entry="selectedEntry"
            />
            <AttendanceStats
                :stats="sessionStats"
                title="Présences du cours"
                :description="
                    attendanceId && students.length
                        ? `${students.length - sessionStats.absences} / ${students.length} élèves présents`
                        : undefined
                "
            />

            <AttendanceAssignments
                :assignments="assignments"
                :lesson-id="lessonId"
                :next-assignment-date="nextAssignmentDate"
                :schedule-pattern="schedulePattern"
                :group-name="selectedGroup"
                :selected-entry="selectedEntry"
            />
        </div>
    </div>
</template>
