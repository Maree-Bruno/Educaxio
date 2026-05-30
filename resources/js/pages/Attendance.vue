<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AttendanceAssignments from '@/components/widgets/AttendanceAssignments.vue';
import type { Assignment } from '@/components/widgets/AttendanceAssignments.vue';
import AttendanceJournal from '@/components/widgets/AttendanceJournal.vue';
import AttendanceStudentList from '@/components/widgets/AttendanceStudentList.vue';
import DateField from '@/components/widgets/DateField.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { setPageTitle } from '@/composables/usePageTitle';

setPageTitle('Présences');

interface Entry {
    id: number;
    label: string;
    lesson_id: number;
    subject: string;
    group: string;
    school: string;
}
interface Student   { id: number; lastname: string; firstname: string }
interface Status    { student_id: number; type: string; motive: string | null }
interface LessonNoteData { id: number; notes: string | null; savedAt: string | null }

const props = defineProps<{
    entries:            Entry[];
    selectedEntry:      number | null;
    selectedSchool:     string | null;
    selectedGroup:      string | null;
    date:               string;
    students:           Student[];
    statuses:           Status[];
    attendanceId:       number | null;
    lastSavedAt:        string | null;
    lessonNote:         LessonNoteData | null;
    lessonId:           number | null;
    assignments:        Assignment[];
    nextAssignmentDate: string | null;
    schedulePattern:    { day_of_week: number; slot_label: string }[];
}>();

function nav(params: Record<string, string | number | null | undefined>) {
    router.get('/attendances', params, { preserveState: false });
}

const entryOptions = computed(() =>
    props.entries.map((e) => ({
        value: e.id,
        label: `${e.label} — ${e.subject} · ${e.group}`,
    })),
);
</script>

<template>
    <!-- Filtres -->
    <div class="rounded-2xl bg-white px-6 py-5 shadow-sm outline -outline-offset-1 outline-neutral-300/10">
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
                @update:model-value="(val) => val !== null && nav({ date, entry: val })"
            />

            <div class="flex flex-col gap-2">
                <label class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">École</label>
                <div class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base">
                    <span v-if="selectedSchool">{{ selectedSchool }}</span>
                    <span v-else class="text-border-figma">—</span>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">Classe</label>
                <div class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base">
                    <span v-if="selectedGroup">{{ selectedGroup }}</span>
                    <span v-else class="text-border-figma">—</span>
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
        />

        <!-- Sidebar -->
        <div class="flex w-full shrink-0 flex-col gap-8 xl:w-72">
            <AttendanceJournal
                :lesson-note="lessonNote"
                :lesson-id="lessonId"
                :date="date"
                :selected-entry="selectedEntry"
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