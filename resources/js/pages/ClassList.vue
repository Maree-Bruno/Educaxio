<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch, watchEffect } from 'vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import FilterBar from '@/components/widgets/FilterBar.vue';
import GroupCard from '@/components/widgets/GroupCard.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import ArrowUpDown from '@/components/widgets/svg/ArrowUpDown.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { useAuthStore } from '@/stores/auth';
import { useGroupsStore } from '@/stores/groups';
import type { AcademicYear, Group, Lesson, School } from '@/types';

setPageTitle('Liste des classes');

const props = defineProps<{
    groups: Group[];
    schools: Pick<School, 'id' | 'name' | 'slug'>[];
    academicYears: Pick<AcademicYear, 'id' | 'year'>[];
    classes: Pick<Group, 'slug' | 'grade' | 'name' | 'school_id'>[];
    filters: {
        school?: string;
        class?: string;
        year?: string;
        search?: string;
        sort?: string;
        dir?: 'asc' | 'desc';
    };
}>();

const groupsStore = useGroupsStore();
const auth = useAuthStore();

const canCreate = computed(() => auth.adminSchools.length > 0);

function canDeleteGroup(schoolId: number): boolean {
    return auth.adminSchools.some((s) => s.id === schoolId);
}

watchEffect(() => groupsStore.setGroups(props.groups));

type GroupLesson = { group: Group; lesson: Lesson | null };

const flatItems = computed<GroupLesson[]>(() =>
    groupsStore.groups.flatMap((group): GroupLesson[] => {
        if (auth.isPureAdmin || group.lessons.length === 0) return [{ group, lesson: null }];
        return group.lessons.map((lesson) => ({ group, lesson }));
    }),
);

function groupProps(group: Group) {
    const { lessons: _l, ...rest } = group;
    return rest;
}

const pendingDelete = ref<{ id: number; slug: string; name: string } | null>(
    null,
);
const deleteLoading = ref(false);

function requestDelete(id: number, slug: string) {
    const group = groupsStore.groups.find((g) => g.id === id);
    pendingDelete.value = { id, slug, name: `${group?.grade}${group?.name}` };
}

function confirmDelete() {
    if (!pendingDelete.value) {
        return;
    }

    deleteLoading.value = true;
    router.delete(`/classlist/${pendingDelete.value.slug}`, {
        onSuccess: () => {
            groupsStore.removeGroup(pendingDelete.value!.id);
            pendingDelete.value = null;
        },
        onFinish: () => {
            deleteLoading.value = false;
        },
    });
}

const filterSchool = ref<string | null>(props.filters.school ?? null);
const filterClass = ref<string | null>(props.filters.class ?? null);
const filterYear = ref<string | null>(props.filters.year ?? null);
const search = ref(props.filters.search ?? '');
const sortBy = ref(props.filters.sort ?? '');
const sortDir = ref<'asc' | 'desc'>(props.filters.dir ?? 'asc');

const sortOptions = [
    { value: 'grade', label: 'Par classe' },
    { value: 'school', label: 'Par école' },
    { value: 'students', label: "Par nb d'élèves" },
    { value: 'subject', label: 'Par cours' },
];

const schoolOptions = props.schools.map((s) => ({
    value: s.slug,
    label: s.name,
}));
const yearOptions = props.academicYears.map((y) => ({
    value: y.id,
    label: String(y.year),
}));
const classOptions = computed(() => {
    const schoolId = filterSchool.value
        ? props.schools.find((s) => s.slug === filterSchool.value)?.id
        : null;
    const filtered = schoolId
        ? props.classes.filter((c) => c.school_id === schoolId)
        : props.classes;

    return filtered.map((c) => ({
        value: c.slug,
        label: `${c.grade}${c.name}`,
    }));
});

function applyFilters() {
    router.get(
        '/classlist',
        {
            school: filterSchool.value ?? undefined,
            class: filterClass.value ?? undefined,
            year: filterYear.value ?? undefined,
            search: search.value || undefined,
            sort: sortBy.value || undefined,
            dir: sortBy.value && sortDir.value === 'desc' ? 'desc' : undefined,
        },
        { preserveState: true, replace: true },
    );
}

const applySearchDebounced = useDebounceFn(applyFilters, 300);

const activeCount = computed(() => {
    return [filterSchool.value, filterClass.value, filterYear.value, search.value || null].filter(
        Boolean,
    ).length;
});

watch(filterSchool, () => {
    const stillValid = classOptions.value.some(
        (o) => o.value === filterClass.value,
    );

    if (!stillValid) {
        filterClass.value = null;
    }

    applyFilters();
});
watch(filterClass, applyFilters);
watch(filterYear, applyFilters);
watch(search, applySearchDebounced);
watch(sortBy, applyFilters);
watch(sortDir, applyFilters);
</script>

<template>
    <FilterBar :active-count="activeCount">
        <template #filters>
            <SelectField
                id="filter-school"
                v-model="filterSchool"
                label="École"
                placeholder="Toutes les écoles"
                :options="schoolOptions"
                class="w-full lg:w-[22%]"
            />
            <SelectField
                id="filter-class"
                v-model="filterClass"
                label="Classe"
                placeholder="Toutes les classes"
                :options="classOptions"
                class="w-full lg:w-[22%]"
            />
            <SelectField
                id="filter-year"
                v-model="filterYear"
                label="Année scolaire"
                placeholder="Toutes les années"
                :options="yearOptions"
                class="w-full lg:w-[22%]"
            />
            <SearchInput
                id="filter-search"
                v-model="search"
                label="Rechercher"
                placeholder="Rechercher"
                class="w-full lg:w-[22%]"
            />
            <div class="flex items-end gap-1 w-full lg:w-[22%]">
                <SelectField
                    id="filter-sort"
                    v-model="sortBy"
                    label="Trier par"
                    placeholder="Par défaut"
                    :options="sortOptions"
                    class="flex-1"
                />
                <button
                    type="button"
                    :disabled="!sortBy"
                    class="flex h-[46px] w-10 shrink-0 items-center justify-center rounded-2xl bg-white outline outline-1 -outline-offset-1 outline-border-figma transition-all hover:bg-gray-50"
                    :class="sortBy ? 'text-text-base' : 'pointer-events-none opacity-30'"
                    :title="sortDir === 'asc' ? 'Ordre ascendant' : 'Ordre descendant'"
                    @click="sortDir = sortDir === 'asc' ? 'desc' : 'asc'"
                >
                    <ArrowUpDown
                        :size="16"
                        :stroke-width="2"
                        class="transition-transform duration-200"
                        :class="{ 'rotate-180': sortDir === 'desc' }"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </template>
        <template v-if="canCreate" #action>
            <LinkButton
                href="/classlist/create"
                variant="primary"
                size="sm"
                label="Nouvelle classe"
                class="w-full font-bold"
            />
        </template>
    </FilterBar>

    <ConfirmModal
        :open="pendingDelete !== null"
        :title="`Supprimer la classe ${pendingDelete?.name}`"
        message="Toutes les données associées (élèves, cours, présences) seront supprimées définitivement."
        :loading="deleteLoading"
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />

    <div>
        <p
            v-if="flatItems.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-3xl bg-white py-20 text-center font-bold text-text-base"
        >
            Aucune classe pour le moment
        </p>

        <ul
            v-else
            class="grid list-none grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <li v-for="{ group, lesson } in flatItems" :key="`${group.id}-${lesson?.id ?? 0}`">
                <GroupCard
                    v-bind="groupProps(group)"
                    :lesson="lesson"
                    :can-delete="canDeleteGroup(group.school_id)"
                    :view-href="`/classlist/${group.slug}`"
                    :grades-href="lesson ? `/classlist/${group.slug}/lessons/${lesson.id}/grades` : undefined"
                    @delete="requestDelete"
                />
            </li>
        </ul>
    </div>
</template>
