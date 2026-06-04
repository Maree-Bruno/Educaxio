<script setup lang="ts">
import { computed, ref } from 'vue';
import Badge from '@/components/widgets/Badge.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import SortTh from '@/components/widgets/SortTh.vue';
import StudentCount from '@/components/widgets/StudentCount.vue';
import { formatDate } from '@/composables/useFormatter';
import { useStudentSort } from '@/composables/useStudentSort';
import { ATTENDANCE_STATUS_COLORS, ATTENDANCE_STATUS_LABELS } from '@/types';
import type { AttendanceStatus } from '@/types';

interface AbsenceRecord {
    date:    string | null;
    type:    AttendanceStatus;
    subject: string | null;
    group:   string | null;
    time:    string | null;
    teacher: string | null;
}

const props = defineProps<{
    history: AbsenceRecord[];
    isAdmin: boolean;
}>();

const TYPE_OPTIONS = [
    { value: 'Absent',   label: 'Absent'          },
    { value: 'Late',     label: 'Arrivée tardive' },
    { value: 'Excluded', label: 'Exclu'           },
];

const historySearch  = ref('');
const historyType    = ref('');
const historySubject = ref('');
const historyTeacher = ref('');
const { sortCol, sortDir, sortBy } = useStudentSort({ col: 'date', dir: 'desc' });

const subjectOptions = computed(() =>
    [...new Set(props.history.map((r) => r.subject).filter(Boolean) as string[])]
        .sort((a, b) => a.localeCompare(b, 'fr'))
        .map((s) => ({ value: s, label: s })),
);

const teacherOptions = computed(() =>
    [...new Set(props.history.map((r) => r.teacher).filter(Boolean) as string[])]
        .sort((a, b) => a.localeCompare(b, 'fr'))
        .map((t) => ({ value: t, label: t })),
);

const filteredHistory = computed(() => {
    let list = [...props.history];
    const term = historySearch.value.trim().toLowerCase();

    if (term) {
        list = list.filter((r) =>
            r.subject?.toLowerCase().includes(term) ||
            r.group?.toLowerCase().includes(term) ||
            r.teacher?.toLowerCase().includes(term) ||
            (r.date ? formatDate(r.date)?.toLowerCase().includes(term) : false),
        );
    }

    if (historyType.value)    list = list.filter((r) => r.type    === historyType.value);
    if (historySubject.value) list = list.filter((r) => r.subject === historySubject.value);
    if (historyTeacher.value) list = list.filter((r) => r.teacher === historyTeacher.value);

    return list.sort((a, b) => {
        let cmp = 0;
        switch (sortCol.value) {
            case 'subject': cmp = (a.subject ?? '').localeCompare(b.subject ?? '', 'fr'); break;
            case 'group':   cmp = (a.group   ?? '').localeCompare(b.group   ?? '', 'fr'); break;
            case 'type':    cmp = (a.type    ?? '').localeCompare(b.type    ?? '');       break;
            case 'teacher': cmp = (a.teacher ?? '').localeCompare(b.teacher ?? '', 'fr'); break;
            default:        cmp = (a.date    ?? '').localeCompare(b.date    ?? '');       break;
        }
        return sortDir.value === 'desc' ? -cmp : cmp;
    });
});
</script>

<template>
    <div class="min-w-0 flex-1 overflow-hidden rounded-2xl bg-white">
        <div class="border-b border-neutral-300/10 px-6 py-5">
            <h3 class="text-xl font-bold text-text-base">
                Historique des absences
                <StudentCount
                    v-if="history.length"
                    :total="history.length"
                    :filtered="filteredHistory.length !== history.length ? filteredHistory.length : undefined"
                />
            </h3>
            <div v-if="history.length" class="mt-3 flex flex-wrap gap-2">
                <SelectField
                    placeholder="Tous les types"
                    :options="TYPE_OPTIONS"
                    :model-value="historyType || null"
                    class="w-full shrink-0 sm:w-40"
                    @update:model-value="historyType = ($event as string) ?? ''"
                />
                <SelectField
                    v-if="subjectOptions.length > 1"
                    placeholder="Tous les cours"
                    :options="subjectOptions"
                    :model-value="historySubject || null"
                    class="w-full shrink-0 sm:w-48"
                    @update:model-value="historySubject = ($event as string) ?? ''"
                />
                <SelectField
                    v-if="isAdmin && teacherOptions.length > 1"
                    placeholder="Tous les profs"
                    :options="teacherOptions"
                    :model-value="historyTeacher || null"
                    class="w-full shrink-0 sm:w-48"
                    @update:model-value="historyTeacher = ($event as string) ?? ''"
                />
                <SearchInput
                    v-model="historySearch"
                    placeholder="Date, cours, classe…"
                    class="w-full sm:flex-1"
                />
            </div>
        </div>

        <template v-if="history.length">
            <ul class="divide-y divide-neutral-100 sm:hidden">
                <li
                    v-for="(record, i) in filteredHistory"
                    :key="i"
                    class="flex items-start justify-between gap-3 px-4 py-4"
                >
                    <div class="flex min-w-0 flex-col gap-1">
                        <span class="text-sm font-medium text-text-base">
                            {{ formatDate(record.date) }}
                            <span v-if="record.time" class="text-stone-400"> · {{ record.time }}</span>
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-stone-500">
                            {{ record.subject ?? '—' }}
                            <Badge v-if="record.group" variant="neutral">{{ record.group }}</Badge>
                        </span>
                        <span v-if="isAdmin && record.teacher" class="text-xs text-stone-400">{{ record.teacher }}</span>
                    </div>
                    <span
                        class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold"
                        :class="ATTENDANCE_STATUS_COLORS[record.type].join(' ')"
                    >
                        {{ ATTENDANCE_STATUS_LABELS[record.type] ?? record.type }}
                    </span>
                </li>
                <li v-if="filteredHistory.length === 0">
                    <EmptyState message="Aucun résultat pour ces filtres" size="sm" />
                </li>
            </ul>

            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full border-collapse text-left">
                    <caption class="sr-only">Historique des absences</caption>
                    <thead>
                        <tr class="bg-gray-100">
                            <SortTh col="date"    label="Date"       :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                            <th scope="col" class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-stone-500">Heure</th>
                            <SortTh col="subject" label="Cours"      :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                            <SortTh col="group"   label="Classe"     :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                            <SortTh col="type"    label="Type"       :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                            <SortTh v-if="isAdmin" col="teacher" label="Professeur" :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr
                            v-for="(record, i) in filteredHistory"
                            :key="i"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 text-sm text-text-base">{{ formatDate(record.date) }}</td>
                            <td class="px-6 py-4 text-sm text-text-base">{{ record.time ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-text-base">{{ record.subject ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <Badge v-if="record.group" variant="neutral">{{ record.group }}</Badge>
                                <span v-else class="text-sm text-text-base">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-lg px-2 py-1 text-xs font-bold"
                                    :class="ATTENDANCE_STATUS_COLORS[record.type].join(' ')"
                                >
                                    {{ ATTENDANCE_STATUS_LABELS[record.type] ?? record.type }}
                                </span>
                            </td>
                            <td v-if="isAdmin" class="px-6 py-4 text-sm text-text-base">{{ record.teacher ?? '—' }}</td>
                        </tr>
                        <tr v-if="filteredHistory.length === 0">
                            <td :colspan="isAdmin ? 6 : 5">
                                <EmptyState message="Aucun résultat pour ces filtres" size="sm" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <EmptyState v-else message="Aucune absence enregistrée" size="sm" />
    </div>
</template>