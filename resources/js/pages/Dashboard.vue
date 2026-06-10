<script setup lang="ts">
import DashboardJoinRequests from '@/components/admin/DashboardJoinRequests.vue';
import DashboardQuickAccess from '@/components/admin/DashboardQuickAccess.vue';
import DashboardRecentStudents from '@/components/admin/DashboardRecentStudents.vue';
import DashboardStats from '@/components/admin/DashboardStats.vue';
import DashboardTeachers from '@/components/admin/DashboardTeachers.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { PaginationLink, Student, Subject, UserSummary } from '@/types';

setPageTitle('Dashboard');

interface Stats    { students: number; teachers: number; pending: number; lessons: number }
interface JoinRequest { id: number; user: UserSummary; subjects: Subject[] }

type StudentRow = Pick<Student, 'id' | 'firstname' | 'lastname'> & {
    groups: { id: number; grade: string; name: string; slug: string }[];
};
type TeacherRow = UserSummary & { subjects: Subject[] };

interface DashboardPage<T> { data: T[]; current_page: number; last_page: number; links: PaginationLink[] }

interface School {
    id:             number;
    name:           string;
    slug:           string;
    stats:          Stats;
    joinRequests:   JoinRequest[];
    recentStudents: DashboardPage<StudentRow>;
    teachers:       DashboardPage<TeacherRow>;
}

defineProps<{ schools: School[] }>();
</script>

<template>
    <div v-if="schools.length === 0" class="flex items-center justify-center rounded-2xl bg-white py-20">
        <p class="font-bold text-text-base">Aucune école administrée</p>
    </div>

    <div v-for="school in schools" :key="school.id" class="flex flex-col gap-8">
        <h2 v-if="schools.length > 1" class="text-2xl font-bold text-text-base">
            {{ school.name }}
        </h2>

        <DashboardStats :stats="school.stats" />

        <div class="grid grid-cols-1 gap-8 xl:grid-cols-2 xl:items-start">
            <div class="flex flex-col gap-8">
                <DashboardJoinRequests
                    :join-requests="school.joinRequests"
                    :school="school"
                    :pending-count="school.stats.pending"
                />
                <DashboardRecentStudents
                    :students="school.recentStudents"
                    :school-slug="school.slug"
                />
            </div>

            <div class="flex flex-col gap-8">
                <DashboardTeachers :teachers="school.teachers" :school-slug="school.slug" />
                <DashboardQuickAccess :school-slug="school.slug" />
            </div>
        </div>
    </div>
</template>
