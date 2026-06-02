<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import Badge from '@/components/widgets/Badge.vue';
import Button from '@/components/widgets/Button.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SortTh from '@/components/widgets/SortTh.vue';
import StudentCount from '@/components/widgets/StudentCount.vue';
import Attendance from '@/components/widgets/svg/Attendance.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import { useStudentSort, studentRowNumber } from '@/composables/useStudentSort';
import { attendances } from '@/routes';
import { index as adminLessonsIndex } from '@/routes/admin/lessons';
import { show as showClasslist } from '@/routes/classlist';
import { detach as detachRoute } from '@/routes/classlist/students';
import { show as showStudent } from '@/routes/students';
import { useToasterStore } from '@/stores/toaster';
import type { Group, Paginator, Student } from '@/types';

const props = defineProps<{
    students: Paginator<Student>;
    group: Group;
    canManage: boolean;
    isTeacher: boolean;
    filters: { sort?: string; dir?: 'asc' | 'desc'; search?: string };
}>();

const emit = defineEmits<{
    add: [];
}>();

const { sortCol, sortDir, sortBy } = useStudentSort({
    col: props.filters.sort === 'firstname' ? 'firstname' : 'lastname',
    dir: props.filters.dir === 'desc' ? 'desc' : 'asc',
});
const search = ref(props.filters.search ?? '');

const toaster = useToasterStore();
const hiddenIds = ref(new Set<number>());

function removeFromGroup(student: Student) {
    hiddenIds.value = new Set([...hiddenIds.value, student.id]);
    toaster.deletable(
        `${student.firstname} ${student.lastname} retiré du groupe`,
        () => router.delete(detachRoute.url({ group: props.group.slug, student: student.slug }), { preserveScroll: true }),
        () => { hiddenIds.value.delete(student.id); hiddenIds.value = new Set(hiddenIds.value); },
    );
}

function applyFilters() {
    router.get(
        showClasslist.url({ group: props.group.slug }),
        {
            sort:   sortCol.value !== 'lastname' ? sortCol.value : undefined,
            dir:    sortDir.value === 'desc' ? 'desc' : undefined,
            search: search.value.trim() || undefined,
        },
        { preserveState: true, replace: true },
    );
}

const applyFiltersDebounced = useDebounceFn(applyFilters, 300);

watch(search, applyFiltersDebounced);
watch([sortCol, sortDir], applyFilters);

function rowNum(index: number): string {
    return studentRowNumber(index, props.students.current_page, props.students.per_page, props.students.total, sortDir.value);
}

const className = `${props.group.grade}${props.group.name}`;
</script>

<template>
    <div class="min-w-0 flex-1 rounded-2xl">

        <!-- En-tête du tableau -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-t-2xl border-b border-neutral-300/10 bg-white px-4 sm:px-6 py-4 sm:py-5"
        >
            <h2 class="text-xl font-bold text-text-base">
                Liste des élèves
                <StudentCount :total="students.total" />
            </h2>
            <div class="flex flex-wrap justify-end gap-2">
                <LinkButton
                    v-if="canManage"
                    :href="adminLessonsIndex.url({ school: group.school.slug })"
                    variant="secondary"
                    size="sm"
                    label="Attribution des cours"
                />
                <Button
                    v-if="canManage"
                    variant="primary"
                    size="sm"
                    label="Ajouter"
                    @click="emit('add')"
                />
                <LinkButton
                    v-if="isTeacher"
                    :href="attendances.url({ query: { group: group.slug } })"
                    variant="secondary"
                    size="sm"
                    label="Présence"
                >
                    <template #icon>
                        <Attendance :size="16" :stroke-width="2" aria-hidden="true" />
                    </template>
                </LinkButton>
            </div>
            <SearchInput
                v-model="search"
                placeholder="Rechercher un élève…"
                class="w-full"
            />
        </div>

        <!-- Mobile : liste de cartes -->
        <ul class="sm:hidden divide-y divide-neutral-100 bg-white">
            <li
                v-for="(student, index) in students.data.filter((s) => !hiddenIds.has(s.id))"
                :key="student.id"
                class="flex items-center justify-between gap-3 px-4 py-4"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span class="w-5 shrink-0 text-xs text-stone-400">{{ rowNum(index) }}</span>
                    <span class="truncate text-sm font-medium text-text-base">
                        {{ student.lastname }} {{ student.firstname }}
                    </span>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <LinkButton
                        :href="showStudent.url({ student: student.slug })"
                        variant="secondary"
                        size="sm"
                        :icon-only="true"
                        :title="canManage ? 'Voir / modifier l\'élève' : 'Voir l\'élève'"
                    >
                        <template #icon>
                            <Eye :size="16" :stroke-width="2" aria-hidden="true" />
                        </template>
                    </LinkButton>
                    <Button
                        v-if="canManage"
                        variant="danger"
                        size="sm"
                        :icon-only="true"
                        title="Retirer du groupe"
                        @click="removeFromGroup(student)"
                    >
                        <template #icon>
                            <Trash :size="16" :stroke-width="2" aria-hidden="true" />
                        </template>
                    </Button>
                </div>
            </li>
            <li v-if="students.total === 0"><EmptyState message="Aucun élève dans cette classe" /></li>
        </ul>

        <!-- Desktop : tableau -->
        <div class="hidden sm:block overflow-x-auto bg-white">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th
                            class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                        >
                            N°
                        </th>
                        <SortTh col="lastname" label="Nom" :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                        <SortTh col="firstname" label="Prénom" :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
                        <th
                            class="px-6 py-4 text-center text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                        >
                            Classe
                        </th>
<!--                            <th
                            class="px-6 py-4 text-center text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                        >
                            Moyenne
                        </th>-->
                        <th
                            class="px-6 py-4 text-center text-xs font-bold uppercase leading-4 tracking-wider text-stone-500"
                        >
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(student, index) in students.data.filter((s) => !hiddenIds.has(s.id))"
                        :key="`${sortCol}-${sortDir}-${student.id}`"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-6 py-5 text-sm text-stone-400">{{ rowNum(index) }}</td>
                        <td class="px-6 py-5">
                            <span class="text-base font-medium text-text-base">{{ student.lastname }}</span>
                        </td>
                        <td class="px-6 py-5 text-base text-text-base">{{ student.firstname }}</td>
                        <td class="px-6 py-5 text-center">
                            <Badge variant="neutral">{{ className }}</Badge>
                        </td>
<!--                            <td class="px-6 py-5 text-center text-base text-text-base">
                            —
                        </td>-->
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-center gap-2">
                                <LinkButton
                                    :href="showStudent.url({ student: student.slug })"
                                    variant="primary"
                                    size="sm"
                                    :icon-only="true"
                                    :title="canManage ? 'Voir / modifier l\'élève' : 'Voir l\'élève'"
                                >
                                    <template #icon>
                                        <Eye :size="16" :stroke-width="2" aria-hidden="true" />
                                    </template>
                                </LinkButton>
                                <Button
                                    v-if="canManage"
                                    variant="danger"
                                    size="sm"
                                    :icon-only="true"
                                    title="Retirer du groupe"
                                    @click="removeFromGroup(student)"
                                >
                                    <template #icon>
                                        <Trash :size="16" :stroke-width="2" aria-hidden="true" />
                                    </template>
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <!-- État vide -->
                    <tr v-if="students.total === 0">
                        <td colspan="6"><EmptyState message="Aucun élève dans cette classe" /></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pied : pagination -->
        <div class="rounded-b-2xl bg-gray-100 px-4 sm:px-6 py-4">
            <Pagination
                :links="students.links"
                :current-page="students.current_page"
                :last-page="students.last_page"
            />
        </div>
    </div>
</template>
