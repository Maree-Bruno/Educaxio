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
import { setPageTitle } from '@/composables/usePageTitle';
import { useGroupsStore } from '@/stores/groups';
import type { Group, School } from '@/types';

setPageTitle('Liste des classes');

const props = defineProps<{
    groups: Group[];
    schools: Pick<School, 'id' | 'name'>[];
    classes: Pick<Group, 'slug' | 'grade' | 'name' | 'school_id'>[];
    filters: { school?: string; class?: string; search?: string };
}>();

const groupsStore = useGroupsStore();

watchEffect(() => groupsStore.setGroups(props.groups));

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
const search = ref(props.filters.search ?? '');

const schoolOptions = props.schools.map((s) => ({
    value: s.id,
    label: s.name,
}));
const classOptions = computed(() => {
    const filtered = filterSchool.value
        ? props.classes.filter(
              (c) => String(c.school_id) === String(filterSchool.value),
          )
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
            search: search.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

const applySearchDebounced = useDebounceFn(applyFilters, 300);

const activeCount = computed(() => {
    return [filterSchool.value, filterClass.value, search.value || null].filter(
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
watch(search, applySearchDebounced);
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
            <SearchInput
                id="filter-search"
                v-model="search"
                label="Rechercher"
                placeholder="Rechercher"
                class="w-full lg:w-[22%]"
            />
        </template>
        <template #action>
            <LinkButton
                href="#"
                variant="primary"
                size="md"
                mobile-size="sm"
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
            v-if="groupsStore.groups.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-3xl bg-white py-20 text-center font-bold text-text-base"
        >
            Aucune classe pour le moment
        </p>

        <ul
            v-else
            class="grid list-none grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <li v-for="group in groupsStore.groups" :key="group.id">
                <GroupCard
                    v-bind="group"
                    :view-href="`/classlist/${group.slug}`"
                    :grades-href="`/classlist/${group.slug}/grades`"
                    @delete="requestDelete"
                />
            </li>
        </ul>
    </div>
</template>
