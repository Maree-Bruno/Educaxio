<!--suppress D -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import Button from '@/components/widgets/Button.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import FilterBar from '@/components/widgets/FilterBar.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';
import Edit from '@/components/widgets/svg/Edit.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import { setPageTitle } from '@/composables/usePageTitle';

interface School {
    id: number;
    name: string;
    slug: string;
}
interface Group {
    id: number;
    grade: string;
    name: string;
    slug: string;
}
interface Subject {
    id: number;
    name: string;
}
interface Teacher {
    id: number;
    name: string;
}
interface Lesson {
    id: number;
    group_id: number;
    subject_id: number;
    group: Group;
    subject: Subject;
    users: Teacher[];
}

const props = defineProps<{
    school: School;
    lessons: Lesson[];
    groups: Group[];
    subjects: Subject[];
    teachers: Teacher[];
}>();

setPageTitle(`Attribution des cours`);

const base = `/schools/${props.school.slug}/lessons`;

// ── Filtres ───────────────────────────────────────────────────────────────
const search        = ref('');
const filterSubject = ref('');
const filterTeacher = ref('');

const subjectOptions = props.subjects.map((s) => ({ value: String(s.id), label: s.name }));
const teacherOptions = props.teachers.map((t) => ({ value: String(t.id), label: t.name }));

const hasActiveFilter = computed(() =>
    search.value.trim() !== '' || filterSubject.value !== '' || filterTeacher.value !== '',
);

const activeCount = computed(() =>
    [search.value.trim(), filterSubject.value, filterTeacher.value].filter(Boolean).length,
);

// ── Données groupées + filtrées ───────────────────────────────────────────
const groupedLessons = computed(() => {
    const term       = search.value.toLowerCase().trim();
    const subjectId  = filterSubject.value ? Number(filterSubject.value) : null;
    const teacherId  = filterTeacher.value ? Number(filterTeacher.value) : null;
    const filtering  = hasActiveFilter.value;

    return props.groups
        .map((group) => {
            let lessons = props.lessons.filter((l) => l.group_id === group.id);

            if (subjectId !== null) lessons = lessons.filter((l) => l.subject_id === subjectId);
            if (teacherId !== null) lessons = lessons.filter((l) => l.users.some((u) => u.id === teacherId));
            if (term) {
                const groupMatch = `${group.grade}${group.name}`.toLowerCase().includes(term);
                if (!groupMatch) {
                    lessons = lessons.filter(
                        (l) =>
                            l.subject.name.toLowerCase().includes(term) ||
                            l.users.some((u) => u.name.toLowerCase().includes(term)),
                    );
                }
            }

            return { group, lessons };
        })
        .filter(({ lessons }) => !filtering || lessons.length > 0);
});

// ── Accordéon ─────────────────────────────────────────────────────────────
const openGroupIds = ref<Set<number>>(new Set());

function toggleGroup(id: number) {
    if (openGroupIds.value.has(id)) {
        openGroupIds.value.delete(id);
    } else {
        openGroupIds.value.add(id);
    }
}

function isGroupOpen(id: number): boolean {
    return openGroupIds.value.has(id) || hasActiveFilter.value;
}

// ── Modal : gérer les profs d'un cours ───────────────────────────────────
const teacherDialogRef = ref<HTMLDialogElement | null>(null);
const editingLesson = ref<Lesson | null>(null);
const selectedTeacherIds = ref<number[]>([]);

function openTeachers(lesson: Lesson) {
    editingLesson.value = lesson;
    selectedTeacherIds.value = lesson.users.map((u) => u.id);
    nextTick(() => teacherDialogRef.value?.showModal());
}

function closeTeachers() {
    teacherDialogRef.value?.close();
}

function toggleTeacher(id: number) {
    const idx = selectedTeacherIds.value.indexOf(id);

    if (idx === -1) {
        selectedTeacherIds.value.push(id);
    } else {
        selectedTeacherIds.value.splice(idx, 1);
    }
}

function syncTeachers() {
    if (!editingLesson.value) {
        return;
    }

    router.put(
        `${base}/${editingLesson.value.id}/teachers`,
        {
            teacher_ids: selectedTeacherIds.value,
        },
        { preserveScroll: true, onSuccess: closeTeachers },
    );
}

// ── Modal : ajouter une matière à un groupe ───────────────────────────────
const addDialogRef = ref<HTMLDialogElement | null>(null);
const addingToGroup = ref<Group | null>(null);
const addForm = ref({ subject_id: '', teacher_ids: [] as number[] });

function availableSubjects(group: Group) {
    const assigned = props.lessons
        .filter((l) => l.group_id === group.id)
        .map((l) => l.subject_id);

    return props.subjects.filter((s) => !assigned.includes(s.id));
}

function openAdd(group: Group) {
    addingToGroup.value = group;
    addForm.value = { subject_id: '', teacher_ids: [] };
    nextTick(() => addDialogRef.value?.showModal());
}

function closeAdd() {
    addDialogRef.value?.close();
}

function toggleAddTeacher(id: number) {
    const idx = addForm.value.teacher_ids.indexOf(id);

    if (idx === -1) {
        addForm.value.teacher_ids.push(id);
    } else {
        addForm.value.teacher_ids.splice(idx, 1);
    }
}

function submitAdd() {
    if (!addingToGroup.value) {
        return;
    }

    router.post(
        base,
        {
            group_id: addingToGroup.value.id,
            subject_id: addForm.value.subject_id,
            teacher_ids: addForm.value.teacher_ids,
        },
        { preserveScroll: true, onSuccess: closeAdd },
    );
}

// ── Suppression ───────────────────────────────────────────────────────────
const pendingDelete = ref<Lesson | null>(null);
const deleteLoading = ref(false);

function confirmDelete() {
    if (!pendingDelete.value) {
        return;
    }

    deleteLoading.value = true;
    router.delete(`${base}/${pendingDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            pendingDelete.value = null;
        },
        onFinish: () => {
            deleteLoading.value = false;
        },
    });
}

function onBackdrop(
    dialogEl: HTMLDialogElement | null,
    e: MouseEvent,
    close: () => void,
) {
    if (e.target === dialogEl) {
        close();
    }
}
</script>

<template>
    <ConfirmModal
        :open="pendingDelete !== null"
        :title="`Supprimer ${pendingDelete?.subject.name} · ${pendingDelete?.group.grade}${pendingDelete?.group.name}`"
        message="Les créneaux horaires associés seront aussi supprimés."
        :loading="deleteLoading"
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />

    <FilterBar :active-count="activeCount">
        <template #filters>
            <SearchInput
                id="filter-search"
                v-model="search"
                label="Rechercher"
                placeholder="Classe, matière ou prof…"
                class="w-full lg:w-72"
            />
            <SelectField
                id="filter-subject"
                v-model="filterSubject"
                label="Matière"
                placeholder="Toutes les matières"
                :options="subjectOptions"
                class="w-full lg:w-52"
            />
            <SelectField
                id="filter-teacher"
                v-model="filterTeacher"
                label="Prof"
                placeholder="Tous les profs"
                :options="teacherOptions"
                class="w-full lg:w-52"
            />
        </template>
        <template #action>
            <LinkButton
                :href="`/classlist/create?school=${school.slug}&from=lessons`"
                variant="primary"
                size="sm"
                label="Nouvelle classe"
            />
        </template>
    </FilterBar>

    <div class="flex flex-col gap-2">
        <div
            v-for="{ group, lessons } in groupedLessons"
            :key="group.id"
            class="overflow-hidden rounded-2xl bg-white"
        >
            <!-- En-tête de la classe -->
            <div class="flex items-center justify-between px-4 sm:px-6 py-4">
                <button
                    type="button"
                    class="flex flex-1 items-center gap-3 text-left transition-colors"
                    @click="toggleGroup(group.id)"
                >
                    <span class="text-base font-bold text-text-base">
                        {{ group.grade }}{{ group.name }}
                    </span>
                    <span class="text-sm text-border-figma">
                        {{ lessons.length }} cours
                    </span>
                </button>
                <div class="flex shrink-0 items-center gap-2">
                    <LinkButton
                        :href="`/classlist/${group.slug}`"
                        variant="secondary"
                        size="sm"
                        label="Modifier la classe"
                        mobile-size="xs"
                    />
                    <ChevronDown
                        :size="18"
                        :stroke-width="2"
                        class="shrink-0 text-border-figma transition-transform duration-200 cursor-pointer"
                        :class="{ 'rotate-180': isGroupOpen(group.id) }"
                        @click="toggleGroup(group.id)"
                    />
                </div>
            </div>

            <!-- Contenu déroulant -->
            <div v-if="isGroupOpen(group.id)">
                <div class="border-t border-neutral-100">
                    <!-- Ligne vide -->
                    <p
                        v-if="lessons.length === 0"
                        class="px-6 py-4 text-sm text-border-figma italic"
                    >
                        Aucun cours attribué
                    </p>

                    <!-- Liste des cours -->
                    <div
                        v-for="lesson in lessons"
                        :key="lesson.id"
                        class="flex flex-wrap items-center gap-x-4 gap-y-1.5 border-b border-neutral-100 px-4 sm:px-6 py-3 last:border-b-0 hover:bg-gray-50"
                    >
                        <!-- Matière -->
                        <span
                            class="w-full text-sm font-bold text-text-base sm:w-40 sm:shrink-0"
                        >
                            {{ lesson.subject.name }}
                        </span>

                        <!-- Profs assignés -->
                        <div class="flex min-w-0 flex-1 flex-wrap gap-1.5">
                            <span
                                v-for="teacher in lesson.users"
                                :key="teacher.id"
                                class="rounded-md bg-blue/10 px-2 py-0.5 text-xs font-bold text-blue"
                            >
                                {{ teacher.name }}
                            </span>
                            <span
                                v-if="lesson.users.length === 0"
                                class="text-xs text-border-figma italic"
                            >
                                Aucun prof
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex shrink-0 items-center gap-2">
                            <Button
                                variant="secondary"
                                size="sm"
                                :icon-only="true"
                                title="Gérer les profs"
                                @click="openTeachers(lesson)"
                            >
                                <template #icon>
                                    <Edit
                                        :size="16"
                                        :stroke-width="2"
                                        aria-hidden="true"
                                    />
                                </template>
                            </Button>
                            <Button
                                variant="danger"
                                size="sm"
                                :icon-only="true"
                                title="Supprimer"
                                @click="pendingDelete = lesson"
                            >
                                <template #icon>
                                    <Trash
                                        :size="16"
                                        :stroke-width="2"
                                        aria-hidden="true"
                                    />
                                </template>
                            </Button>
                        </div>
                    </div>

                    <!-- Ajouter une matière -->
                    <div class="px-4 sm:px-6 py-3">
                        <Button
                            variant="secondary"
                            size="sm"
                            :label="`+ Ajouter une matière`"
                            :disabled="availableSubjects(group).length === 0"
                            @click="openAdd(group)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <p
            v-if="groupedLessons.length === 0"
            class="rounded-2xl bg-white px-6 py-16 text-center text-sm font-bold text-border-figma"
        >
            {{ hasActiveFilter ? 'Aucun résultat pour ces filtres' : 'Aucun groupe pour cette école' }}
        </p>
    </div>

    <!-- Modal : gérer les profs d'un cours -->
    <dialog
        ref="teacherDialogRef"
        class="w-[90vw] max-w-md overflow-hidden rounded-2xl bg-bg-primary p-4 sm:p-6"
        @click="onBackdrop(teacherDialogRef, $event, closeTeachers)"
        @cancel.prevent="closeTeachers"
    >
        <div v-if="editingLesson" class="flex flex-col gap-5">
            <div>
                <h2 class="text-xl font-bold text-black">Profs assignés</h2>
                <p class="mt-1 text-sm font-bold text-border-figma">
                    {{ editingLesson.subject.name }} ·
                    {{ editingLesson.group.grade
                    }}{{ editingLesson.group.name }}
                </p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label
                    v-for="teacher in teachers"
                    :key="teacher.id"
                    class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-2.5 outline outline-1 -outline-offset-1"
                    :class="
                        selectedTeacherIds.includes(teacher.id)
                            ? 'outline-blue'
                            : 'outline-border-figma'
                    "
                >
                    <input
                        type="checkbox"
                        class="accent-blue"
                        :checked="selectedTeacherIds.includes(teacher.id)"
                        @change="toggleTeacher(teacher.id)"
                    />
                    <span class="text-sm font-bold text-text-base">{{
                        teacher.name
                    }}</span>
                </label>
                <p
                    v-if="teachers.length === 0"
                    class="text-xs text-border-figma italic"
                >
                    Aucun prof dans cette école
                </p>
            </div>

            <div class="flex gap-3">
                <Button
                    variant="primary"
                    size="sm"
                    label="Enregistrer"
                    class="flex-1"
                    @click="syncTeachers"
                />
                <Button
                    variant="danger"
                    size="sm"
                    label="Annuler"
                    class="flex-1"
                    @click="closeTeachers"
                />
            </div>
        </div>
    </dialog>

    <!-- Modal : ajouter une matière à un groupe -->
    <dialog
        ref="addDialogRef"
        class="w-[90vw] max-w-md overflow-hidden rounded-2xl bg-bg-primary p-4 sm:p-6"
        @click="onBackdrop(addDialogRef, $event, closeAdd)"
        @cancel.prevent="closeAdd"
    >
        <div v-if="addingToGroup" class="flex flex-col gap-5">
            <div>
                <h2 class="text-xl font-bold text-black">
                    Ajouter une matière
                </h2>
                <p class="mt-1 text-sm font-bold text-border-figma">
                    {{ addingToGroup.grade }}{{ addingToGroup.name }}
                </p>
            </div>

            <div class="flex flex-col gap-2">
                <label
                    class="text-xs font-bold tracking-wider text-border-figma uppercase"
                    >Matière</label
                >
                <select
                    v-model="addForm.subject_id"
                    class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline outline-1 -outline-offset-1 outline-border-figma"
                >
                    <option value="">Choisir une matière</option>
                    <option
                        v-for="s in availableSubjects(addingToGroup)"
                        :key="s.id"
                        :value="String(s.id)"
                    >
                        {{ s.name }}
                    </option>
                </select>
            </div>

            <div class="flex flex-col gap-2">
                <label
                    class="text-xs font-bold tracking-wider text-border-figma uppercase"
                >
                    Profs
                    <span class="font-normal normal-case">(optionnel)</span>
                </label>
                <div class="flex flex-col gap-1.5">
                    <label
                        v-for="teacher in teachers"
                        :key="teacher.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-2.5 outline outline-1 -outline-offset-1"
                        :class="
                            addForm.teacher_ids.includes(teacher.id)
                                ? 'outline-blue'
                                : 'outline-border-figma'
                        "
                    >
                        <input
                            type="checkbox"
                            class="accent-blue"
                            :checked="addForm.teacher_ids.includes(teacher.id)"
                            @change="toggleAddTeacher(teacher.id)"
                        />
                        <span class="text-sm font-bold text-text-base">{{
                            teacher.name
                        }}</span>
                    </label>
                    <p
                        v-if="teachers.length === 0"
                        class="text-xs text-border-figma italic"
                    >
                        Aucun prof dans cette école
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <Button
                    variant="primary"
                    size="sm"
                    label="Enregistrer"
                    class="flex-1"
                    :disabled="!addForm.subject_id"
                    @click="submitAdd"
                />
                <Button
                    variant="danger"
                    size="sm"
                    label="Annuler"
                    class="flex-1"
                    @click="closeAdd"
                />
            </div>
        </div>
    </dialog>
</template>

<style scoped>
dialog {
    margin: auto;
}
dialog::backdrop {
    background-color: rgb(0 0 0 / 0.4);
    backdrop-filter: blur(4px);
}
</style>
