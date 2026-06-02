<!--suppress D -->
<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { store as adminLessonsStore, destroy as adminLessonsDestroy } from '@/routes/admin/lessons';
import { sync as syncLessonTeachers } from '@/routes/admin/lessons/teachers';
import { create, show as showClasslist } from '@/routes/classlist';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import FilterBar from '@/components/widgets/FilterBar.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';
import Edit from '@/components/widgets/svg/Edit.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { resolveSubjectLabel } from '@/composables/useSubjectLabel';
import { useToasterStore } from '@/stores/toaster';

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
    subject_ids: number[];
}
interface Lesson {
    id: number;
    group_id: number;
    subject_id: number;
    lm_level: number | null;
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
            let lessons = props.lessons.filter((l) => l.group_id === group.id && !hiddenIds.value.has(l.id));

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

const toaster   = useToasterStore();
const hiddenIds = ref(new Set<number>());

// ── Modal : gérer les profs d'un cours ───────────────────────────────────
const teacherModalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const editingLesson = ref<Lesson | null>(null);
const teacherForm = useForm({ teacher_ids: [] as number[] });

function openTeachers(lesson: Lesson) {
    editingLesson.value = lesson;
    teacherForm.teacher_ids = lesson.users.map((u) => u.id);
    teacherForm.clearErrors();
    nextTick(() => teacherModalRef.value?.open());
}

function closeTeachers() {
    teacherModalRef.value?.close();
}

function toggleTeacher(id: number) {
    const idx = teacherForm.teacher_ids.indexOf(id);

    if (idx === -1) {
        teacherForm.teacher_ids.push(id);
    } else {
        teacherForm.teacher_ids.splice(idx, 1);
    }
}

function syncTeachers() {
    if (!editingLesson.value) {
        return;
    }

    const lessonId = editingLesson.value.id;
    teacherForm.put(syncLessonTeachers.url({ school: props.school.slug, lesson: lessonId }), {
        preserveScroll: true,
        onSuccess: () => {
            closeTeachers();
            toaster.success('Profs enregistrés');
        },
    });
}

// ── Mise à jour inline du niveau LM ──────────────────────────────────────
function updateLmLevel(lesson: Lesson, lmLevel: string) {
    router.patch(
        adminLessonsDestroy.url({ school: props.school.slug, lesson: lesson.id }),
        { lm_level: lmLevel === '' ? null : Number(lmLevel) },
        { preserveScroll: true },
    );
}

// ── Modal : ajouter une matière à un groupe ───────────────────────────────
const addModalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const addingToGroup = ref<Group | null>(null);
const addForm = useForm<{ subject_id: string | number | null; lm_level: string; teacher_ids: number[] }>({ subject_id: null, lm_level: '', teacher_ids: [] });
const addSubjectOptions = computed(() =>
    addingToGroup.value ? availableSubjects(addingToGroup.value).map((s) => ({ value: s.id, label: s.name })) : [],
);

const addTeacherOptions = computed(() =>
    addForm.subject_id
        ? props.teachers.filter((t) => t.subject_ids.includes(Number(addForm.subject_id)))
        : [],
);

watch(() => addForm.subject_id, () => {
    addForm.teacher_ids = [];
    addForm.lm_level = '';
});

function availableSubjects(group: Group) {
    const assigned = props.lessons
        .filter((l) => l.group_id === group.id)
        .map((l) => l.subject_id);

    return props.subjects.filter((s) => !assigned.includes(s.id));
}

function openAdd(group: Group) {
    addingToGroup.value = group;
    addForm.reset();
    nextTick(() => addModalRef.value?.open());
}

function closeAdd() {
    addModalRef.value?.close();
}

function toggleAddTeacher(id: number) {
    const idx = addForm.teacher_ids.indexOf(id);

    if (idx === -1) {
        addForm.teacher_ids.push(id);
    } else {
        addForm.teacher_ids.splice(idx, 1);
    }
}

function submitAdd() {
    if (!addingToGroup.value) {
        return;
    }

    const groupId = addingToGroup.value.id;
    addForm.transform((data) => ({
        ...data,
        group_id: groupId,
        lm_level: data.lm_level === '' ? null : Number(data.lm_level),
    }))
        .post(adminLessonsStore.url({ school: props.school.slug }), {
            preserveScroll: true,
            onSuccess: () => {
                closeAdd();
                toaster.success('Cours ajouté');
            },
        });
}

// ── Suppression ───────────────────────────────────────────────────────────
const pendingDelete = ref<Lesson | null>(null);
const deleteLoading = ref(false);

function confirmDelete() {
    if (!pendingDelete.value) {
        return;
    }

    const { id, subject, group, lm_level } = pendingDelete.value;

    pendingDelete.value = null;
    hiddenIds.value = new Set([...hiddenIds.value, id]);
    toaster.deletable(
        `${resolveSubjectLabel(subject.name, lm_level)} · ${group.grade}${group.name} supprimé`,
        () => router.delete(adminLessonsDestroy.url({ school: props.school.slug, lesson: id }), { preserveScroll: true }),
        () => { hiddenIds.value.delete(id); hiddenIds.value = new Set(hiddenIds.value); },
    );
}
</script>

<template>
    <ConfirmModal
        :open="pendingDelete !== null"
        :title="`Supprimer ${pendingDelete ? resolveSubjectLabel(pendingDelete.subject.name, pendingDelete.lm_level) : ''} · ${pendingDelete?.group.grade}${pendingDelete?.group.name}`"
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
                :href="create.url({ query: { school: school.slug, from: 'lessons' } })"
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
                        :href="showClasslist.url({ group: group.slug })"
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
                    <p
                        v-if="lessons.length === 0"
                        class="px-6 py-4 text-sm text-border-figma italic"
                    >
                        Aucun cours attribué
                    </p>

                    <div
                        v-for="lesson in lessons"
                        :key="lesson.id"
                        class="flex flex-wrap items-center gap-x-4 gap-y-1.5 border-b border-neutral-100 px-4 sm:px-6 py-3 last:border-b-0 hover:bg-gray-50"
                    >
                        <span class="flex w-full items-center gap-2 sm:w-40 sm:shrink-0">
                            <span class="text-sm font-bold text-text-base">
                                {{ resolveSubjectLabel(lesson.subject.name, lesson.lm_level) }}
                            </span>
                        </span>
                        <select
                            class="h-7 rounded-lg border border-neutral-200 bg-white px-2 text-xs text-stone-500 focus:border-blue focus:outline-none"
                            :value="lesson.lm_level ?? ''"
                            @change="updateLmLevel(lesson, ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">—</option>
                            <option value="1">LM1</option>
                            <option value="2">LM2</option>
                            <option value="3">LM3</option>
                        </select>

                        <div class="flex min-w-0 flex-1 flex-wrap gap-1.5">
                            <Badge v-for="teacher in lesson.users" :key="teacher.id">
                                {{ teacher.name }}
                            </Badge>
                            <span v-if="lesson.users.length === 0" class="text-xs text-border-figma italic">
                                Aucun prof
                            </span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Button variant="secondary" size="sm" :icon-only="true" title="Gérer les profs" @click="openTeachers(lesson)">
                                <template #icon><Edit :size="16" :stroke-width="2" aria-hidden="true" /></template>
                            </Button>
                            <Button variant="danger" size="sm" :icon-only="true" title="Supprimer" @click="pendingDelete = lesson">
                                <template #icon><Trash :size="16" :stroke-width="2" aria-hidden="true" /></template>
                            </Button>
                        </div>
                    </div>

                    <div class="px-4 sm:px-6 py-3">
                        <Button
                            variant="secondary"
                            size="sm"
                            label="+ Ajouter une matière"
                            :disabled="availableSubjects(group).length === 0"
                            @click="openAdd(group)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="groupedLessons.length === 0"
            :message="hasActiveFilter ? 'Aucun résultat pour ces filtres' : 'Aucun groupe pour cette école'"
            class="rounded-2xl bg-white"
        />
    </div>

    <!-- Modal : gérer les profs d'un cours -->
    <BaseModal ref="teacherModalRef">
        <form v-if="editingLesson" class="flex flex-col gap-5" @submit.prevent="syncTeachers">
            <div>
                <h2 class="text-xl font-bold text-black">Profs assignés</h2>
                <p class="mt-1 text-sm font-bold text-border-figma">
                    {{ resolveSubjectLabel(editingLesson.subject.name, editingLesson.lm_level) }} · {{ editingLesson.group.grade }}{{ editingLesson.group.name }}
                </p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label
                    v-for="teacher in teachers"
                    :key="teacher.id"
                    class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-2.5 outline outline-1 -outline-offset-1"
                    :class="teacherForm.teacher_ids.includes(teacher.id) ? 'outline-blue' : 'outline-border-figma'"
                >
                    <input
                        type="checkbox"
                        class="accent-blue"
                        :checked="teacherForm.teacher_ids.includes(teacher.id)"
                        @change="toggleTeacher(teacher.id)"
                    />
                    <span class="text-sm font-bold text-text-base">{{ teacher.name }}</span>
                </label>
                <p v-if="teachers.length === 0" class="text-xs text-border-figma italic">
                    Aucun prof dans cette école
                </p>
            </div>

            <div class="flex gap-3">
                <Button type="submit" variant="primary" size="sm" label="Enregistrer" class="flex-1" :loading="teacherForm.processing" />
                <Button type="button" variant="danger" size="sm" label="Annuler" class="flex-1" @click="closeTeachers" />
            </div>
        </form>
    </BaseModal>

    <!-- Modal : ajouter une matière à un groupe -->
    <BaseModal ref="addModalRef">
        <form v-if="addingToGroup" class="flex flex-col gap-5" @submit.prevent="submitAdd">
            <div>
                <h2 class="text-xl font-bold text-black">Ajouter une matière</h2>
                <p class="mt-1 text-sm font-bold text-border-figma">
                    {{ addingToGroup.grade }}{{ addingToGroup.name }}
                </p>
            </div>

            <SelectField
                v-model="addForm.subject_id"
                label="Matière"
                placeholder="Choisir une matière"
                :options="addSubjectOptions"
            />

            <SelectField
                v-model="addForm.lm_level"
                label="Niveau LM (langue étrangère)"
                placeholder="Sans niveau LM"
                :options="[{ value: '1', label: 'LM1' }, { value: '2', label: 'LM2' }, { value: '3', label: 'LM3' }]"
            />

            <div v-if="addForm.subject_id" class="flex flex-col gap-2">
                <label class="text-xs font-bold tracking-wider text-border-figma uppercase">
                    Profs <span class="font-normal normal-case">(optionnel)</span>
                </label>
                <div class="flex flex-col gap-1.5">
                    <label
                        v-for="teacher in addTeacherOptions"
                        :key="teacher.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-2.5 outline outline-1 -outline-offset-1"
                        :class="addForm.teacher_ids.includes(teacher.id) ? 'outline-blue' : 'outline-border-figma'"
                    >
                        <input
                            type="checkbox"
                            class="accent-blue"
                            :checked="addForm.teacher_ids.includes(teacher.id)"
                            @change="toggleAddTeacher(teacher.id)"
                        />
                        <span class="text-sm font-bold text-text-base">{{ teacher.name }}</span>
                    </label>
                    <p v-if="addTeacherOptions.length === 0" class="text-xs text-border-figma italic">
                        Aucun prof rattaché à cette matière
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" variant="primary" size="sm" label="Enregistrer" class="flex-1" :disabled="!addForm.subject_id" :loading="addForm.processing" />
                <Button type="button" variant="danger" size="sm" label="Annuler" class="flex-1" @click="closeAdd" />
            </div>
        </form>
    </BaseModal>
</template>
