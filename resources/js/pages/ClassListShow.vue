<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AddStudentModal from '@/components/widgets/AddStudentModal.vue';
import AttendanceStats from '@/components/widgets/AttendanceStats.vue';
import type { AttendanceStats as AttendanceStatsType } from '@/components/widgets/AttendanceStats.vue';
import Breadcrumb from '@/components/widgets/Breadcrumb.vue';
import ClassGroupForm from '@/components/widgets/ClassGroupForm.vue';
import ClassGroupStudentTable from '@/components/widgets/ClassGroupStudentTable.vue';
import SidebarLayout from '@/components/widgets/SidebarLayout.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { classlist } from '@/routes';
import { update as updateClasslist } from '@/routes/classlist';
import type { AcademicYear, Group, Paginator, Student, Subject } from '@/types';

const props = defineProps<{
    group: Group;
    students: Paginator<Student>;
    canManage: boolean;
    isTeacher: boolean;
    academicYears: Pick<AcademicYear, 'id' | 'year'>[];
    subjects: Pick<Subject, 'id' | 'name'>[];
    filters: { sort?: string; dir?: 'asc' | 'desc'; search?: string };
    schoolStudents: {
        id: number;
        lastname: string;
        firstname: string;
        groups: { id: number; grade: string; name: string }[];
    }[];
    attendanceStats: AttendanceStatsType;
}>();

const className = computed(() => `${props.group.grade}${props.group.name}`);

watch(
    () => `${className.value} - ${props.group.school.name}`,
    (title) => setPageTitle(title),
    { immediate: true },
);

const formData = computed(() => ({
    grade: props.group.grade,
    name: props.group.name,
    school_id: props.group.school_id,
    academic_year_id: props.group.academic_year_id,
    subject_id: props.group.lessons[0]?.subject_id ?? null,
}));

const addModalRef = ref<InstanceType<typeof AddStudentModal> | null>(null);
</script>

<template>
    <Breadcrumb
        :items="[
            { label: 'Liste de classe', href: classlist.url() },
            { label: `${className} — ${group.school.name}` },
        ]"
    />

    <SidebarLayout>
        <ClassGroupStudentTable
            :students="students"
            :group="group"
            :can-manage="canManage"
            :is-teacher="isTeacher"
            :filters="filters"
            @add="addModalRef?.open()"
        />

        <template #sidebar>
            <AttendanceStats :stats="attendanceStats" />
            <ClassGroupForm
                v-if="canManage"
                mode="edit"
                :action="updateClasslist.url({ group: group.slug })"
                :academic-years="academicYears"
                :subjects="subjects"
                :initial-data="formData"
            />
        </template>
    </SidebarLayout>

    <AddStudentModal
        v-if="canManage"
        ref="addModalRef"
        :group-slug="group.slug"
        :school-students="schoolStudents"
    />
</template>
