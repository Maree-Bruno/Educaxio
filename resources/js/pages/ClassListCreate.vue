<script setup lang="ts">
import Breadcrumb from '@/components/widgets/Breadcrumb.vue';
import ClassGroupForm from '@/components/widgets/ClassGroupForm.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import SidebarLayout from '@/components/widgets/SidebarLayout.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { AcademicYear, Subject } from '@/types';
import { store } from '@/routes/classlist';

setPageTitle('Nouvelle classe');

defineProps<{
    academicYears: Pick<AcademicYear, 'id' | 'year' | 'is_current'>[];
    subjects: Pick<Subject, 'id' | 'name'>[];
    defaults: { school_id: number | null; academic_year_id: number | null };
    breadcrumb: { label: string; href?: string }[];
}>();
</script>

<template>
    <Breadcrumb :items="breadcrumb" />

    <SidebarLayout>
        <section class="min-w-0 overflow-hidden rounded-2xl">
            <div class="flex items-center border-b border-neutral-300/10 bg-white px-6 py-5">
                <h2 class="text-xl font-bold text-text-base">Liste des élèves</h2>
            </div>
            <EmptyState message="Créez la classe pour commencer à ajouter des élèves" class="rounded-b-2xl bg-white" />
        </section>

        <template #sidebar>
            <ClassGroupForm
                mode="create"
                :action="store.url()"
                :academic-years="academicYears"
                :subjects="subjects"
                :initial-data="{
                    grade: '',
                    name: '',
                    school_id: defaults.school_id,
                    academic_year_id: defaults.academic_year_id,
                    subject_id: null,
                }"
            />
        </template>
    </SidebarLayout>
</template>
