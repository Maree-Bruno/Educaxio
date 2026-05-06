<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Button from '@/components/widgets/Button.vue';
import ClassGroupForm from '@/components/widgets/ClassGroupForm.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';
import ClipboardCheck from '@/components/widgets/svg/ClipboardCheck.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { AcademicYear, Group, Paginator, School, Student, Subject } from '@/types';

const props = defineProps<{
    group: Group;
    students: Paginator<Student>;
    schools: Pick<School, 'id' | 'name'>[];
    academicYears: Pick<AcademicYear, 'id' | 'year'>[];
    subjects: Pick<Subject, 'id' | 'name'>[];
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
        <span class="text-base font-bold text-blue">
            {{ className }} — {{ group.school.name }}
        </span>
    </nav>

    <!-- Layout principal -->
    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">

        <!-- Tableau des élèves -->
        <div class="min-w-0 flex-1 overflow-hidden rounded-2xl">

            <!-- En-tête du tableau -->
            <div
                class="flex flex-wrap items-center justify-between gap-4 border-b border-neutral-300/10 bg-white px-6 py-5"
            >
                <h2 class="text-xl font-bold text-text-base">
                    Liste des élèves
                    <span class="text-border-figma">({{ group.students_count }})</span>
                </h2>
                <div class="flex items-center gap-3">
                    <Button variant="secondary" size="sm" label="Ajouter" />
                    <LinkButton
                        :href="`/classlist/${group.slug}/grades`"
                        variant="primary"
                        size="sm"
                        label="Cahier de côte"
                    >
                        <template #icon>
                            <ClipboardCheck :size="16" :stroke-width="2" aria-hidden="true" />
                        </template>
                    </LinkButton>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto bg-white">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-gray-100">
                            <th
                                class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                            >
                                N°
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                            >
                                Nom de l'élève
                            </th>
                            <th
                                class="px-6 py-4 text-center text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                            >
                                Classe
                            </th>
                            <th
                                class="px-6 py-4 text-center text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                            >
                                Moyenne
                            </th>
                            <th
                                class="px-6 py-4 text-center text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr
                            v-for="(student, index) in students.data"
                            :key="student.id"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <td class="px-6 py-5 text-sm text-stone-400">
                                {{ String((students.from ?? 0) + index).padStart(2, '0') }}
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-base font-medium text-text-base">
                                    {{ student.lastname }} {{ student.firstname }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center text-base text-text-base">
                                {{ className }}
                            </td>
                            <td class="px-6 py-5 text-center text-base text-text-base">
                                —
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center justify-center gap-2">
                                    <LinkButton
                                        :href="`/students/${student.id}`"
                                        variant="secondary"
                                        size="sm"
                                        :icon-only="true"
                                        title="Voir l'élève"
                                    >
                                        <template #icon>
                                            <Eye :size="16" :stroke-width="2" aria-hidden="true" />
                                        </template>
                                    </LinkButton>
                                    <LinkButton
                                        :href="`/students/${student.id}`"
                                        method="delete"
                                        variant="danger"
                                        size="sm"
                                        :icon-only="true"
                                        title="Supprimer l'élève"
                                    >
                                        <template #icon>
                                            <Trash :size="16" :stroke-width="2" aria-hidden="true" />
                                        </template>
                                    </LinkButton>
                                </div>
                            </td>
                        </tr>

                        <!-- État vide -->
                        <tr v-if="students.total === 0">
                            <td
                                colspan="5"
                                class="px-6 py-16 text-center text-sm font-bold text-border-figma"
                            >
                                Aucun élève dans cette classe
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pied : pagination -->
            <div class="rounded-b-2xl bg-gray-100 px-6 py-4">
                <Pagination
                    :links="students.links"
                    :current-page="students.current_page"
                    :last-page="students.last_page"
                />
            </div>
        </div>

        <!-- Sidebar droite -->
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-80 sticky top-20">

            <!-- Import / Export -->
            <div class="flex gap-2.5">
                <Button variant="primary" size="sm" label="Importer" class="flex-1" />
                <Button variant="primary" size="sm" label="Exporter" class="flex-1" />
            </div>

            <ClassGroupForm
                mode="edit"
                :action="`/classlist/${group.slug}`"
                :schools="schools"
                :academic-years="academicYears"
                :subjects="subjects"
                :initial-data="formData"
            />
        </div>
    </div>
</template>