<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ClassGroupForm from '@/components/widgets/ClassGroupForm.vue';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { AcademicYear, School, Subject } from '@/types';

setPageTitle('Nouvelle classe');

defineProps<{
    schools: Pick<School, 'id' | 'name'>[];
    academicYears: Pick<AcademicYear, 'id' | 'year'>[];
    subjects: Pick<Subject, 'id' | 'name'>[];
}>();
</script>

<template>
    <!-- Fil d'ariane -->
    <nav class="mb-6 flex items-center gap-3" aria-label="Fil d'ariane">
        <Link
            href="/classlist"
            class="text-base font-bold text-text-base hover:text-blue transition-colors"
        >
            Liste de classe
        </Link>
        <ChevronDown
            :size="16"
            :stroke-width="2"
            class="-rotate-90 text-text-base shrink-0"
            aria-hidden="true"
        />
        <span class="text-base font-bold text-blue">Nouvelle classe</span>
    </nav>

    <!-- Layout principal -->
    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">

        <!-- Placeholder élèves -->
        <div class="min-w-0 flex-1 overflow-hidden rounded-2xl">
            <div class="flex items-center border-b border-neutral-300/10 bg-white px-6 py-5">
                <h2 class="text-xl font-bold text-text-base">Liste des élèves</h2>
            </div>
            <div class="rounded-b-2xl bg-white px-6 py-16 text-center text-sm font-bold text-border-figma">
                Créez la classe pour commencer à ajouter des élèves
            </div>
        </div>

        <!-- Sidebar droite -->
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-80 sticky top-20">
            <ClassGroupForm
                mode="create"
                action="/classlist"
                :schools="schools"
                :academic-years="academicYears"
                :subjects="subjects"
            />
        </div>
    </div>
</template>