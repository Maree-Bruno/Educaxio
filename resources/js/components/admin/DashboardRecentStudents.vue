<script setup lang="ts">
import Badge from '@/components/widgets/Badge.vue';
import DashboardCard from '@/components/admin/DashboardCard.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import { index as studentsIndex } from '@/routes/admin/students';
import { show as showClasslist } from '@/routes/classlist';
import type { PaginationLink, Student } from '@/types';

type StudentRow = Pick<Student, 'id' | 'firstname' | 'lastname'> & {
    groups: { id: number; grade: string; name: string; slug: string }[];
};

interface StudentPage { data: StudentRow[]; current_page: number; last_page: number; links: PaginationLink[] }

defineProps<{
    students:   StudentPage;
    schoolSlug: string;
}>();
</script>

<template>
    <DashboardCard title="Élèves récents" description="Derniers élèves inscrits dans l'établissement.">
        <template #action>
            <LinkButton :href="studentsIndex.url({ school: schoolSlug })" variant="primary" size="sm" label="Voir tout" class="mt-0.5 shrink-0" />
        </template>

        <EmptyState v-if="students.data.length === 0" message="Aucun élève" class="bg-white" />
        <template v-else>
            <ul class="divide-y divide-neutral-100 bg-white">
                <li
                    v-for="student in students.data"
                    :key="student.id"
                    class="flex items-center justify-between gap-3 px-6 py-4"
                >
                    <span class="truncate text-sm font-medium text-text-base">{{ student.lastname }} {{ student.firstname }}</span>
                    <div class="flex shrink-0 flex-wrap gap-1">
                        <Badge v-for="g in student.groups" :key="g.id" :href="showClasslist.url({ group: g.slug })">
                            {{ g.grade }}{{ g.name }}
                        </Badge>
                    </div>
                </li>
            </ul>
            <div class="bg-gray-100 px-6 py-3">
                <Pagination :links="students.links" :current-page="students.current_page" :last-page="students.last_page" />
            </div>
        </template>
    </DashboardCard>
</template>
