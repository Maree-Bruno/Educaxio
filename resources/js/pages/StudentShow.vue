<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AbsenceHistoryTable from '@/components/widgets/AbsenceHistoryTable.vue';
import AttendanceStats from '@/components/widgets/AttendanceStats.vue';
import type { AttendanceStats as AttendanceStatsType } from '@/components/widgets/AttendanceStats.vue';
import Breadcrumb from '@/components/widgets/Breadcrumb.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import SidebarLayout from '@/components/widgets/SidebarLayout.vue';
import StudentGroupsSidebar from '@/components/widgets/StudentGroupsSidebar.vue';
import StudentHeaderCard from '@/components/widgets/StudentHeaderCard.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { classlist } from '@/routes';
import { index as adminStudentsIndex } from '@/routes/admin/students';
import { show as showClasslist } from '@/routes/classlist';
import type { AttendanceStatus, Student } from '@/types';

interface AbsenceRecord {
    date: string | null;
    type: AttendanceStatus;
    subject: string | null;
    group: string | null;
    time: string | null;
    teacher: string | null;
}

type StudentWithSchool = Student & {
    school?: { id: number; name: string; slug: string };
};

const props = defineProps<{
    student: StudentWithSchool;
    isAdmin: boolean;
    absenceHistory: AbsenceRecord[] | null;
    attendanceStats: AttendanceStatsType;
    academicYears: { id: number; year: string; is_current: boolean; is_archived: boolean }[];
    filters: { year: string | null };
}>();

const fullName = computed(
    () => `${props.student.lastname} ${props.student.firstname}`,
);

setPageTitle(fullName.value);

const filterYear = ref<string | null>(props.filters.year ?? null);

const yearOptions = computed(() =>
    props.academicYears.map((y) => ({
        value: String(y.id),
        label: y.is_current ? `${y.year} — en cours` : y.year,
    })),
);

watch(filterYear, (year) => {
    router.get(window.location.pathname, { year: year ?? undefined }, { preserveState: true, replace: true });
});

const filteredGroups = computed(() => {
    const all = props.student.groups ?? [];
    if (!filterYear.value) return all;
    return all.filter((g: any) => String(g.academic_year_id) === filterYear.value);
});

const firstGroup = computed(() => filteredGroups.value[0] ?? null);

const breadcrumbItems = computed(() => {
    const items: { label: string; href?: string }[] = [];

    if (props.isAdmin && props.student.school) {
        items.push({
            label: 'Élèves',
            href: adminStudentsIndex.url({ school: props.student.school.slug }),
        });
    } else {
        items.push({ label: 'Liste de classe', href: classlist.url() });

        if (firstGroup.value) {
            items.push({
                label: `${firstGroup.value.grade}${firstGroup.value.name} — ${firstGroup.value.school.name}`,
                href: showClasslist.url({ group: firstGroup.value.slug }),
            });
        }
    }

    items.push({ label: fullName.value });

    return items;
});
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" />

    <SidebarLayout>
        <StudentHeaderCard
            :student="student"
            :is-admin="isAdmin"
            :full-name="fullName"
        />

        <div
            v-if="isAdmin && yearOptions.length > 1"
            class="flex items-end gap-4 rounded-2xl bg-white px-6 py-4"
        >
            <SelectField
                label="Année scolaire"
                placeholder="Toutes les années"
                :options="yearOptions"
                :model-value="filterYear"
                class="w-48"
                @update:model-value="(v) => (filterYear = v as string | null)"
            />
        </div>

        <AbsenceHistoryTable
            v-if="absenceHistory !== null"
            :history="absenceHistory as any"
            :is-admin="isAdmin"
        />

        <section v-if="!isAdmin" class="overflow-hidden rounded-2xl bg-white">
            <div class="border-b border-neutral-300/10 px-6 py-5">
                <h3 class="text-xl font-bold text-text-base">Évaluations</h3>
            </div>
            <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                <svg
                    class="size-10 text-stone-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                </svg>
                <p class="text-sm font-bold text-stone-400">Fonctionnalité à venir</p>
                <p class="text-xs text-stone-300">Le suivi des évaluations sera disponible prochainement.</p>
            </div>
        </section>

        <template #sidebar>
            <StudentGroupsSidebar :groups="filteredGroups as any" />
            <AttendanceStats
                :stats="attendanceStats"
                :description="
                    isAdmin
                        ? `Tous les cours de ${student.firstname}`
                        : `Vos cours avec ${student.firstname}`
                "
            />
        </template>
    </SidebarLayout>
</template>
