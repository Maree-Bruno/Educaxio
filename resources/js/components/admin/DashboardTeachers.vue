<script setup lang="ts">
import DashboardCard from '@/components/admin/DashboardCard.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import { index as teachersIndex } from '@/routes/admin/teachers';
import type { PaginationLink, Subject, UserSummary } from '@/types';

type TeacherRow = UserSummary & { subjects: Subject[] };
interface TeacherPage { data: TeacherRow[]; current_page: number; last_page: number; links: PaginationLink[] }

defineProps<{
    teachers:   TeacherPage;
    schoolSlug: string;
}>();
</script>

<template>
    <DashboardCard title="Professeurs" description="Professeurs actifs et leurs matières enseignées.">
        <template #action>
            <LinkButton :href="teachersIndex.url({ school: schoolSlug })" variant="primary" size="sm" label="Voir tout" class="mt-0.5 shrink-0" />
        </template>

        <EmptyState v-if="teachers.data.length === 0" message="Aucun professeur" class="bg-white" />
        <template v-else>
            <ul class="divide-y divide-neutral-100 bg-white">
                <li
                    v-for="teacher in teachers.data"
                    :key="teacher.id"
                    class="flex items-center justify-between gap-3 px-6 py-4"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-text-base">{{ teacher.name }}</p>
                        <p class="truncate text-xs text-border-figma">{{ teacher.email }}</p>
                    </div>
                    <p v-if="teacher.subjects.length" class="shrink-0 text-right text-xs text-stone-400">
                        {{ teacher.subjects.map((s) => s.name).join(', ') }}
                    </p>
                </li>
            </ul>
            <div class="bg-gray-100 px-6 py-3">
                <Pagination :links="teachers.links" :current-page="teachers.current_page" :last-page="teachers.last_page" />
            </div>
        </template>
    </DashboardCard>
</template>
