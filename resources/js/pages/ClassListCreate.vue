<script setup lang="ts">
import Breadcrumb from '@/components/widgets/Breadcrumb.vue';
import ClassGroupForm from '@/components/widgets/ClassGroupForm.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { AcademicYear, Subject } from '@/types';

setPageTitle('Nouvelle classe');

defineProps<{
    academicYears: Pick<AcademicYear, 'id' | 'year'>[];
    subjects: Pick<Subject, 'id' | 'name'>[];
    defaults: { school_id: number | null; academic_year_id: number | null };
    breadcrumb: { label: string; href?: string }[];
}>();
</script>

<template>
    <Breadcrumb :items="breadcrumb" />

    <!-- Layout principal -->
    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">

        <!-- Placeholder élèves -->
        <div class="min-w-0 flex-1 overflow-hidden rounded-2xl">
            <div class="flex items-center border-b border-neutral-300/10 bg-white px-6 py-5">
                <h2 class="text-xl font-bold text-text-base">Liste des élèves</h2>
            </div>
            <EmptyState message="Créez la classe pour commencer à ajouter des élèves" class="rounded-b-2xl bg-white" />
        </div>

        <!-- Sidebar droite -->
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-80 sticky top-20">
            <ClassGroupForm
                mode="create"
                action="/classlist"
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
        </div>
    </div>
</template>