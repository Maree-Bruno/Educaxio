<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AttendanceAssignments from '@/components/widgets/AttendanceAssignments.vue';
import type { Assignment } from '@/components/widgets/AttendanceAssignments.vue';
import AttendanceJournal from '@/components/widgets/AttendanceJournal.vue';
import AttendanceStats from '@/components/widgets/AttendanceStats.vue';
import AttendanceStudentList from '@/components/widgets/AttendanceStudentList.vue';
import DateField from '@/components/widgets/DateField.vue';
import type { AfterSave } from '@/components/widgets/SaveSplitButton.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { agenda, attendances } from '@/routes';

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
    academicYears: {
        id: number;
        year: string;
        is_current: boolean;
        is_archived: boolean;
    }[];
    isAdmin: boolean;
    filters: { year: string | null };
}>();

const filterYear = ref<string | null>(props.filters.year ?? null);

const yearOptions = computed(() =>
    props.academicYears.map((y) => ({
        value: String(y.id),
        label: y.is_archived
            ? `${y.year} — archivée`
            : y.is_current
              ? `${y.year} — en cours`
              : y.year,
    })),
);

function nav(params: Record<string, string | number | null | undefined>) {
    router.get(
        attendances.url(),
        { year: filterYear.value, ...params },
        { preserveState: false },
    );
}

watch(filterYear, (year) => nav({ date: props.date, year: year ?? undefined }));

const entryOptions = computed(() =>
    props.entries.map((e) => ({
        value: e.creneau,
        label: `${e.label} — ${e.subject} · ${e.group}`,
    })),
);

const liveStatuses = ref<Record<number, string | null>>(
    Object.fromEntries(props.statuses.map((s) => [s.student_id, s.type])),
);

function onSaved(afterSave: AfterSave) {
    if (afterSave === 'journal') {
        router.get(agenda.url());
    }
}

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
    <section
        class="rounded-2xl bg-white shadow-sm outline -outline-offset-1 outline-neutral-300/10"
    >
        <div class="border-b border-neutral-300/10 px-6 py-4">
            <h2 class="text-sm font-bold text-text-base">
                Sélection de l'heure de cours
            </h2>
            <p class="mt-0.5 text-xs text-stone-400">
                Sélectionnez une date et un créneau pour afficher et saisir les
                présences
            </p>
        </div>
        <div class="px-6 py-5">
            <div
                class="grid grid-cols-1 gap-4 sm:gap-6"
                :class="
                    isAdmin && yearOptions.length > 1
                        ? 'sm:grid-cols-5'
                        : 'sm:grid-cols-4'
                "
            >
                <SelectField
                    v-if="isAdmin && yearOptions.length > 1"
                    label="Année"
                    placeholder="En cours"
                    :options="yearOptions"
                    :model-value="filterYear"
                    class="w-full"
                    @update:model-value="
                        (v) => (filterYear = v as string | null)
                    "
                />

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
    </section>

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

        <aside class="flex w-full shrink-0 flex-col gap-8 xl:w-72">
            <AttendanceStats
                :stats="sessionStats"
                title="Présences du cours"
                :description="
                    attendanceId && students.length
                        ? `${students.length - sessionStats.absences} / ${students.length} élèves présents`
                        : undefined
                "
            />
            <AttendanceJournal
                :lesson-note="lessonNote"
                :lesson-id="lessonId"
                :date="date"
                :selected-entry="selectedEntry"
                @saved="onSaved"
            />

            <AttendanceAssignments
                :assignments="assignments"
                :lesson-id="lessonId"
                :next-assignment-date="nextAssignmentDate"
                :schedule-pattern="schedulePattern"
                :group-name="selectedGroup"
                :selected-entry="selectedEntry"
            />
        </aside>
    </div>
</template>
