<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { classlist, attendances } from '@/routes';
import { show as showClasslist, update as updateClasslist } from '@/routes/classlist';
import { attach } from '@/routes/classlist/students';
import { show as showStudent } from '@/routes/students';
import { index as adminLessonsIndex } from '@/routes/admin/lessons';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Breadcrumb from '@/components/widgets/Breadcrumb.vue';
import Button from '@/components/widgets/Button.vue';
import ClassGroupForm from '@/components/widgets/ClassGroupForm.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SortTh from '@/components/widgets/SortTh.vue';
import Attendance from '@/components/widgets/svg/Attendance.vue';
import ClipboardCheck from '@/components/widgets/svg/ClipboardCheck.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { AcademicYear, Group, Paginator, Student, Subject } from '@/types';

const props = defineProps<{
    group: Group;
    students: Paginator<Student>;
    canManage: boolean;
    isTeacher: boolean;
    academicYears: Pick<AcademicYear, 'id' | 'year'>[];
    subjects: Pick<Subject, 'id' | 'name'>[];
    filters: { sort?: string; dir?: 'asc' | 'desc' };
    schoolStudents: { id: number; lastname: string; firstname: string; groups: { id: number; grade: string; name: string }[] }[];
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

const sortCol = ref(props.filters.sort ?? 'lastname');
const sortDir = ref<'asc' | 'desc'>(props.filters.dir ?? 'asc');

function sortBy(col: string) {
    if (sortCol.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortCol.value = col;
        sortDir.value = 'asc';
    }

    router.get(
        showClasslist.url({ group: props.group.slug }),
        {
            sort: sortCol.value,
            dir: sortDir.value === 'desc' ? 'desc' : undefined,
        },
        { preserveState: true, replace: true },
    );
}

// --- Add student modal ---
const addModalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const addTab = ref<'new' | 'existing'>('new');
const addForm    = useForm({ lastname: '', firstname: '', email: '' });
const attachForm = useForm({ student_ids: [] as number[] });
const studentSearch = ref('');
const selectedStudentIds = ref<number[]>([]);

function openAddModal() {
    addTab.value = 'new';
    addForm.reset();
    studentSearch.value = '';
    selectedStudentIds.value = [];
    nextTick(() => addModalRef.value?.open());
}

watch(addTab, () => {
    studentSearch.value = '';
    selectedStudentIds.value = [];
});

function closeAddModal() {
    addModalRef.value?.close();
}

const filteredSchoolStudents = computed(() => {
    const term = studentSearch.value.trim().toLowerCase();
    if (!term) {
        return props.schoolStudents;
    }

    return props.schoolStudents.filter(
        (s) => s.lastname.toLowerCase().includes(term) || s.firstname.toLowerCase().includes(term),
    );
});

function submitNew() {
    addForm.transform((data) => ({ ...data, email: data.email || null }))
        .post(attach.url({ group: props.group.slug }), {
            preserveScroll: true,
            onSuccess: closeAddModal,
        });
}

function attachSelected() {
    if (!selectedStudentIds.value.length) {
        return;
    }

    attachForm.student_ids = [...selectedStudentIds.value];
    attachForm.post(attach.url({ group: props.group.slug }), {
        preserveScroll: true,
        onSuccess: closeAddModal,
    });
}

</script>

<template>
    <Breadcrumb
        :items="[
            { label: 'Liste de classe', href: classlist.url() },
            { label: `${className} — ${group.school.name}` },
        ]"
    />

    <!-- Layout principal -->
    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">

        <!-- Tableau des élèves -->
        <div class="min-w-0 flex-1 rounded-2xl">

            <!-- En-tête du tableau -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-t-2xl border-b border-neutral-300/10 bg-white px-4 sm:px-6 py-4 sm:py-5"
            >
                <h2 class="text-xl font-bold text-text-base">
                    Liste des élèves
                    <span class="text-border-figma">({{ group.students_count }})</span>
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
                        @click="openAddModal"
                    />
<!--                    <LinkButton
                        v-if="isTeacher"
                        :href="`/classlist/${group.slug}/grades`"
                        variant="primary"
                        size="sm"
                        label="Cahier de côte"
                    >
                        <template #icon>
                            <ClipboardCheck :size="16" :stroke-width="2" aria-hidden="true" />
                        </template>
                    </LinkButton>-->
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
            </div>

            <!-- Mobile : liste de cartes -->
            <ul class="sm:hidden divide-y divide-neutral-100 bg-white">
                <li
                    v-for="(student, index) in students.data"
                    :key="student.id"
                    class="flex items-center justify-between gap-3 px-4 py-4"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="w-5 shrink-0 text-xs text-stone-400">
                            {{
                                sortDir === 'desc'
                                    ? String(students.total - (students.current_page - 1) * students.per_page - index).padStart(2, '0')
                                    : String((students.current_page - 1) * students.per_page + index + 1).padStart(2, '0')
                            }}
                        </span>
                        <span class="truncate text-sm font-medium text-text-base">
                            {{ student.lastname }} {{ student.firstname }}
                        </span>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <LinkButton
                            :href="showStudent.url({ student: student.id })"
                            variant="secondary"
                            size="sm"
                            :icon-only="true"
                            :title="canManage ? 'Voir / modifier l\'élève' : 'Voir l\'élève'"
                        >
                            <template #icon>
                                <Eye :size="16" :stroke-width="2" aria-hidden="true" />
                            </template>
                        </LinkButton>
                        <LinkButton
                            v-if="canManage"
                            :href="showStudent.url({ student: student.id })"
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
                            <SortTh col="lastname" label="Nom de l'élève" :current-col="sortCol" :current-dir="sortDir" @sort="sortBy" />
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
                            :key="`${sortCol}-${sortDir}-${student.id}`"
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
                                <span class="text-base font-medium text-text-base">
                                    {{ student.lastname }} {{ student.firstname }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <Badge variant="neutral">{{ className }}</Badge>
                            </td>
                            <td class="px-6 py-5 text-center text-base text-text-base">
                                —
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center justify-center gap-2">
                                    <LinkButton
                                        :href="showStudent.url({ student: student.id })"
                                        variant="secondary"
                                        size="sm"
                                        :icon-only="true"
                                        :title="canManage ? 'Voir / modifier l\'élève' : 'Voir l\'élève'"
                                    >
                                        <template #icon>
                                            <Eye :size="16" :stroke-width="2" aria-hidden="true" />
                                        </template>
                                    </LinkButton>
                                    <LinkButton
                                        v-if="canManage"
                                        :href="showStudent.url({ student: student.id })"
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
                            <td colspan="5"><EmptyState message="Aucun élève dans cette classe" /></td>
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

        <!-- Sidebar droite — admin seulement -->
        <div v-if="canManage" class="flex w-full shrink-0 flex-col gap-4 xl:w-80 sticky top-20">

            <!-- Import / Export -->
            <div class="flex gap-2.5">
                <Button variant="primary" size="sm" label="Importer" class="flex-1" />
                <Button variant="primary" size="sm" label="Exporter" class="flex-1" />
            </div>

            <ClassGroupForm
                mode="edit"
                :action="updateClasslist.url({ group: group.slug })"
                :academic-years="academicYears"
                :subjects="subjects"
                :initial-data="formData"
            />
        </div>
    </div>

    <!-- Modal : Ajouter un élève -->
    <BaseModal ref="addModalRef">
        <div class="flex flex-col gap-5">
            <h2 class="text-xl font-bold text-black">Ajouter un élève</h2>

            <!-- Tabs -->
            <div class="flex gap-1 rounded-xl bg-gray-100 p-1">
                <button
                    type="button"
                    class="flex-1 rounded-lg py-1.5 text-sm font-bold transition-colors"
                    :class="addTab === 'new' ? 'bg-white text-text-base shadow-sm' : 'text-border-figma hover:text-text-base'"
                    @click="addTab = 'new'"
                >
                    Nouvel élève
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg py-1.5 text-sm font-bold transition-colors"
                    :class="addTab === 'existing' ? 'bg-white text-text-base shadow-sm' : 'text-border-figma hover:text-text-base'"
                    @click="addTab = 'existing'"
                >
                    Élève existant
                </button>
            </div>

            <!-- Tab: Nouvel élève -->
            <form v-if="addTab === 'new'" class="flex flex-col gap-4" @submit.prevent="submitNew">
                <InputLabel v-model="addForm.lastname" label="Nom" placeholder="Dupont" :error="addForm.errors.lastname" />
                <InputLabel v-model="addForm.firstname" label="Prénom" placeholder="Marie" :error="addForm.errors.firstname" />
                <InputLabel v-model="addForm.email" type="email" label="Email (optionnel)" placeholder="marie@exemple.be" :error="addForm.errors.email" />
                <div class="flex gap-3">
                    <Button type="submit" variant="primary" size="md" label="Ajouter" class="flex-1"
                        :disabled="!addForm.lastname || !addForm.firstname" :loading="addForm.processing" />
                    <Button type="button" variant="danger" size="md" label="Annuler" class="flex-1" @click="closeAddModal" />
                </div>
            </form>

            <!-- Tab: Élève existant -->
            <form v-else class="flex flex-col gap-4" @submit.prevent="attachSelected">
                <SearchInput id="student-search" v-model="studentSearch" placeholder="Rechercher un élève…" />
                <div class="flex flex-col divide-y divide-neutral-100 rounded-2xl bg-white overflow-hidden max-h-64 overflow-y-auto">
                    <EmptyState
                        v-if="filteredSchoolStudents.length === 0"
                        :message="schoolStudents.length === 0 ? 'Tous les élèves de l\'école sont déjà dans ce groupe.' : 'Aucun résultat'"
                        size="sm"
                    />
                    <label
                        v-for="s in filteredSchoolStudents"
                        :key="s.id"
                        class="flex cursor-pointer items-center gap-3 px-4 py-3 transition-colors hover:bg-gray-50"
                    >
                        <input
                            type="checkbox"
                            :value="s.id"
                            v-model="selectedStudentIds"
                            class="h-4 w-4 shrink-0 rounded accent-blue"
                        />
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="text-sm font-medium text-text-base">{{ s.lastname }} {{ s.firstname }}</span>
                            <div v-if="s.groups.length" class="flex flex-wrap gap-1">
                                <Badge v-for="g in s.groups" :key="g.id" variant="neutral">
                                    {{ g.grade }}{{ g.name }}
                                </Badge>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="flex gap-3">
                    <Button
                        type="submit"
                        variant="primary"
                        size="md"
                        :label="selectedStudentIds.length ? `Ajouter (${selectedStudentIds.length})` : 'Ajouter'"
                        class="flex-1"
                        :disabled="!selectedStudentIds.length"
                        :loading="attachForm.processing"
                    />
                    <Button type="button" variant="danger" size="md" label="Annuler" class="flex-1" @click="closeAddModal" />
                </div>
            </form>
        </div>
    </BaseModal>
</template>
