<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import CreateStudentModal from '@/components/admin/CreateStudentModal.vue';
import Badge from '@/components/widgets/Badge.vue';
import Button from '@/components/widgets/Button.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import SortTh from '@/components/widgets/SortTh.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import { useHiddenIds } from '@/composables/useHiddenIds';
import { setPageTitle } from '@/composables/usePageTitle';
import { useStudentSort, studentRowNumber } from '@/composables/useStudentSort';
import StudentCount from '@/components/widgets/StudentCount.vue';
import { index as adminStudentsIndex, destroy as adminStudentsDestroy } from '@/routes/admin/students';
import { show as showClasslist } from '@/routes/classlist';
import { show as showStudent } from '@/routes/students';
import { useToasterStore } from '@/stores/toaster';
import type { Paginator } from '@/types';

interface School  { id: number; name: string; slug: string }
interface Group   { id: number; grade: string; name: string; slug: string }
interface Student { id: number; slug: string; lastname: string; firstname: string; email: string | null; school_id: number; groups: Group[] }

const props = defineProps<{
    school:   School;
    students: Paginator<Student>;
    groups:   Group[];
    filters:  { group?: string | null; sort?: string; dir?: string; search?: string };
}>();

setPageTitle('Élèves');

const groupOptions = props.groups.map((g) => ({ value: String(g.id), label: `${g.grade}${g.name}` }));

const filterGroup = ref<string>(props.filters.group ?? '');
const search      = ref(props.filters.search ?? '');
const { sortCol, sortDir, sortBy } = useStudentSort({
    col: props.filters.sort === 'firstname' ? 'firstname' : 'lastname',
    dir: props.filters.dir === 'desc' ? 'desc' : 'asc',
});

function applyFilters() {
    router.get(adminStudentsIndex.url({ school: props.school.slug }), {
        group:  filterGroup.value || undefined,
        sort:   sortCol.value !== 'lastname' ? sortCol.value : undefined,
        dir:    sortDir.value === 'desc' ? 'desc' : undefined,
        search: search.value.trim() || undefined,
    }, { preserveState: true, replace: true });
}

watch(search, useDebounceFn(applyFilters, 300));
watch([sortCol, sortDir], applyFilters);

function rowNumber(index: number): string {
    return studentRowNumber(index, props.students.current_page, props.students.per_page, props.students.total, sortDir.value);
}

const createModal = ref<InstanceType<typeof CreateStudentModal> | null>(null);

const toaster = useToasterStore();
const { hide, show, isHidden } = useHiddenIds();
const pendingDelete = ref<Student | null>(null);

function confirmDelete() {
    if (!pendingDelete.value) {
        return;
    }

    const { id, slug, firstname, lastname } = pendingDelete.value;

    pendingDelete.value = null;
    hide(id);
    toaster.deletable(
        `${firstname} ${lastname} supprimé`,
        () => router.delete(adminStudentsDestroy.url({ school: props.school.slug, student: slug }), { preserveScroll: true }),
        () => show(id),
    );
}
</script>

<template>
    <ConfirmModal
        :open="pendingDelete !== null"
        :title="`Supprimer ${pendingDelete?.firstname} ${pendingDelete?.lastname}`"
        message="L'élève sera retiré de tous ses groupes et supprimé définitivement."
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />

    <CreateStudentModal ref="createModal" :school="school" :groups="groups" />

    <div class="min-w-0 overflow-hidden rounded-2xl">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-neutral-300/10 bg-white px-4 sm:px-6 py-4 sm:py-5">
            <div>
                <h2 class="text-xl font-bold text-text-base">
                    Élèves <StudentCount :total="students.total" />
                </h2>
                <p class="mt-0.5 text-xs text-stone-400">Gérez les élèves et leurs groupes.</p>
            </div>
            <Button variant="primary" size="sm" label="Nouvel élève" class="mt-0.5 shrink-0" @click="createModal?.open()" />
            <div class="flex w-full flex-col gap-2 sm:flex-row">
                <SelectField
                    id="filter-group"
                    v-model="filterGroup"
                    label=""
                    placeholder="Tous les groupes"
                    :options="groupOptions"
                    class="w-full sm:w-40"
                    @update:model-value="applyFilters"
                />
                <SearchInput
                    id="filter-search"
                    v-model="search"
                    placeholder="Rechercher…"
                    class="w-full sm:flex-1"
                />
            </div>
        </div>
        <ul class="sm:hidden divide-y divide-neutral-100 bg-white">
            <li
                v-for="(student, index) in students.data.filter((s) => !isHidden(s.id))"
                :key="student.id"
                class="flex items-start justify-between gap-3 px-4 py-4"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <span class="mt-0.5 w-5 shrink-0 text-xs text-stone-400">
                        {{ rowNumber(index) }}
                    </span>
                    <div class="flex min-w-0 flex-col gap-1">
                        <span class="truncate text-base font-medium text-text-base">
                            {{ student.lastname }} {{ student.firstname }}
                        </span>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="g in student.groups"
                                :key="g.id"
                                :href="showClasslist.url({ group: g.slug })"
                            >
                                {{ g.grade }}{{ g.name }}
                            </Badge>
                        </div>
                        <span v-if="student.email" class="truncate text-xs text-stone-400">{{ student.email }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 flex-col">
                    <LinkButton :href="showStudent.url({ student: student.slug })" variant="secondary" size="sm" :icon-only="true" title="Voir / modifier l'élève">
                        <template #icon><Eye :size="16" :stroke-width="2" aria-hidden="true" /></template>
                    </LinkButton>
                    <Button variant="danger" size="sm" :icon-only="true" title="Supprimer" @click="pendingDelete = student">
                        <template #icon><Trash :size="16" :stroke-width="2" aria-hidden="true" /></template>
                    </Button>
                </div>
            </li>
            <li v-if="students.total === 0"><EmptyState message="Aucun élève trouvé" /></li>
        </ul>
        <div class="hidden sm:block overflow-x-auto bg-white">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">N°</th>
                        <SortTh col="lastname" :current-col="sortCol" :current-dir="sortDir" label="Nom" @sort="sortBy" />
                        <SortTh col="firstname" :current-col="sortCol" :current-dir="sortDir" label="Prénom" @sort="sortBy" />
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Groupes</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Email</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(student, index) in students.data.filter((s) => !isHidden(s.id))"
                        :key="student.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-6 py-5 text-sm text-stone-400">
                            {{
                                sortDir === 'desc'
                                    ? String(students.total - (students.current_page - 1) * students.per_page - index).padStart(2, '0')
                                    : String((students.current_page - 1) * students.per_page + index + 1).padStart(2, '0')
                            }}
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-base font-medium text-text-base">{{ student.lastname }}</span>
                        </td>
                        <td class="px-6 py-5 text-base text-text-base">{{ student.firstname }}</td>
                        <td class="px-6 py-5">
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="g in student.groups"
                                    :key="g.id"
                                    :href="showClasslist.url({ group: g.slug })"
                                >
                                    {{ g.grade }}{{ g.name }}
                                </Badge>
                                <span v-if="student.groups.length === 0" class="text-sm text-border-figma">—</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm text-text-base">{{ student.email ?? '—' }}</td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                <LinkButton :href="showStudent.url({ student: student.slug })" variant="secondary" size="sm" :icon-only="true" title="Voir / modifier l'élève">
                                    <template #icon><Eye :size="16" :stroke-width="2" aria-hidden="true" /></template>
                                </LinkButton>
                                <Button variant="danger" size="sm" :icon-only="true" title="Supprimer" @click="pendingDelete = student">
                                    <template #icon><Trash :size="16" :stroke-width="2" aria-hidden="true" /></template>
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="students.total === 0">
                        <td colspan="6"><EmptyState message="Aucun élève trouvé" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="rounded-b-2xl bg-gray-100 px-4 sm:px-6 py-4">
            <Pagination :links="students.links" :current-page="students.current_page" :last-page="students.last_page" />
        </div>
    </div>
</template>
