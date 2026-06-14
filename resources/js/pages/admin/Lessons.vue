<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { destroy as adminLessonsDestroy } from '@/routes/admin/lessons';
import { create, show as showClasslist } from '@/routes/classlist';
import AddLessonModal from '@/components/admin/AddLessonModal.vue';
import LessonTeachersModal from '@/components/admin/LessonTeachersModal.vue';
import Badge from '@/components/widgets/Badge.vue';
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
import { useHiddenIds } from '@/composables/useHiddenIds';
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
    is_language: boolean;
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

const { hide, show, isHidden } = useHiddenIds();

const groupedLessons = computed(() => {
    const term       = search.value.toLowerCase().trim();
    const subjectId  = filterSubject.value ? Number(filterSubject.value) : null;
    const teacherId  = filterTeacher.value ? Number(filterTeacher.value) : null;
    const filtering  = hasActiveFilter.value;

    return props.groups
        .map((group) => {
            let lessons = props.lessons.filter((l) => l.group_id === group.id && !isHidden(l.id));

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

const teachersModal = ref<InstanceType<typeof LessonTeachersModal> | null>(null);
const addModal      = ref<InstanceType<typeof AddLessonModal> | null>(null);

function hasAvailableSubjects(group: Group): boolean {
    const assigned = props.lessons.filter((l) => l.group_id === group.id).map((l) => l.subject_id);
    return props.subjects.some((s) => !assigned.includes(s.id));
}

function updateLmLevel(lesson: Lesson, lmLevel: string) {
    router.patch(
        adminLessonsDestroy.url({ school: props.school.slug, lesson: lesson.id }),
        { lm_level: lmLevel === '' ? null : Number(lmLevel) },
        { preserveScroll: true },
    );
}

const toaster       = useToasterStore();
const pendingDelete = ref<Lesson | null>(null);

function confirmDelete() {
    if (!pendingDelete.value) {
        return;
    }

    const { id, subject, group, lm_level } = pendingDelete.value;

    pendingDelete.value = null;
    hide(id);
    toaster.deletable(
        `${resolveSubjectLabel(subject.name, lm_level)} · ${group.grade}${group.name} supprimé`,
        () => router.delete(adminLessonsDestroy.url({ school: props.school.slug, lesson: id }), { preserveScroll: true }),
        () => show(id),
    );
}
</script>

<template>
    <ConfirmModal
        :open="pendingDelete !== null"
        :title="`Supprimer ${pendingDelete ? resolveSubjectLabel(pendingDelete.subject.name, pendingDelete.lm_level) : ''} · ${pendingDelete?.group.grade}${pendingDelete?.group.name}`"
        message="Les créneaux horaires associés seront aussi supprimés."
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />

    <LessonTeachersModal ref="teachersModal" :school="school" :teachers="teachers" />
    <AddLessonModal ref="addModal" :school="school" :subjects="subjects" :teachers="teachers" :lessons="lessons" />

    <FilterBar
        title="Attribution des cours"
        description="Associez des matières et des professeurs à chaque classe. Cliquez sur une classe pour la déplier."
        :active-count="activeCount"
    >
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
            <div class="flex items-center justify-between px-4 sm:px-6 py-4">
                <button
                    type="button"
                    :aria-expanded="isGroupOpen(group.id)"
                    :aria-controls="`group-${group.id}-content`"
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

            <div :id="`group-${group.id}-content`" v-if="isGroupOpen(group.id)">
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
                        <template v-if="lesson.subject.is_language">
                            <label :for="`lm-${lesson.id}`" class="sr-only">Niveau de langue</label>
                            <select
                                :id="`lm-${lesson.id}`"
                                class="h-7 rounded-lg border border-neutral-200 bg-white px-2 text-xs text-stone-500 focus:border-blue focus:outline-none"
                                :value="lesson.lm_level ?? ''"
                                @change="updateLmLevel(lesson, ($event.target as HTMLSelectElement).value)"
                            >
                                <option value="">—</option>
                                <option value="1">LM1</option>
                                <option value="2">LM2</option>
                                <option value="3">LM3</option>
                            </select>
                        </template>

                        <div class="flex min-w-0 flex-1 flex-wrap gap-1.5">
                            <Badge v-for="teacher in lesson.users" :key="teacher.id">
                                {{ teacher.name }}
                            </Badge>
                            <span v-if="lesson.users.length === 0" class="text-xs text-border-figma italic">
                                Aucun prof
                            </span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Button variant="secondary" size="sm" :icon-only="true" title="Gérer les profs" @click="teachersModal?.open(lesson)">
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
                            :disabled="!hasAvailableSubjects(group)"
                            @click="addModal?.open(group)"
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
</template>
