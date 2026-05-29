<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, nextTick, ref, watch } from 'vue';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import SortTh from '@/components/widgets/SortTh.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { Paginator } from '@/types';

interface School  { id: number; name: string; slug: string }
interface Group   { id: number; grade: string; name: string; slug: string }
interface Student { id: number; lastname: string; firstname: string; email: string | null; school_id: number; groups: Group[] }

const props = defineProps<{
    school:   School;
    students: Paginator<Student>;
    groups:   Group[];
    filters:  { group?: string | null; sort?: string; dir?: string; search?: string };
}>();

setPageTitle('Élèves');

const base = `/schools/${props.school.slug}/students`;

const groupOptions = props.groups.map((g) => ({ value: String(g.id), label: `${g.grade}${g.name}` }));

const filterGroup = ref<string>(props.filters.group ?? '');
const sortCol     = ref(props.filters.sort === 'firstname' ? 'firstname' : 'lastname');
const sortDir     = ref<'asc' | 'desc'>(props.filters.dir === 'desc' ? 'desc' : 'asc');
const search      = ref(props.filters.search ?? '');

function applyFilters() {
    router.get(base, {
        group:  filterGroup.value || undefined,
        sort:   sortCol.value !== 'lastname' ? sortCol.value : undefined,
        dir:    sortDir.value === 'desc' ? 'desc' : undefined,
        search: search.value.trim() || undefined,
    }, { preserveState: true, replace: true });
}

const applyFiltersDebounced = useDebounceFn(applyFilters, 300);

watch(search, applyFiltersDebounced);

function sortBy(col: string) {
    if (sortCol.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortCol.value = col;
        sortDir.value = 'asc';
    }

    applyFilters();
}

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const form = useForm({ lastname: '', firstname: '', email: '', group_ids: [] as string[] });

const availableGroups = computed(() =>
    props.groups.filter((g) => !form.group_ids.includes(String(g.id))),
);

function addGroup(e: Event) {
    const id = (e.target as HTMLSelectElement).value;

    if (id && !form.group_ids.includes(id)) {
        form.group_ids.push(id);
    }

    (e.target as HTMLSelectElement).value = '';
}

function removeGroup(id: string) {
    form.group_ids = form.group_ids.filter((gid) => gid !== id);
}

function openCreate() {
    form.reset();
    nextTick(() => modalRef.value?.open());
}

function closeModal() {
    modalRef.value?.close();
}

function save() {
    form.transform((data) => ({
        ...data,
        email:     data.email || null,
        group_ids: data.group_ids.map(Number),
    })).post(base, { preserveScroll: true, onSuccess: closeModal });
}

const pendingDelete = ref<Student | null>(null);
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
</script>

<template>
    <ConfirmModal
        :open="pendingDelete !== null"
        :title="`Supprimer ${pendingDelete?.firstname} ${pendingDelete?.lastname}`"
        message="L'élève sera retiré de tous ses groupes."
        :loading="deleteLoading"
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />

    <div class="min-w-0 overflow-hidden rounded-2xl">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-300/10 bg-white px-4 sm:px-6 py-4 sm:py-5">
            <h2 class="text-xl font-bold text-text-base">
                Élèves
                <span class="text-border-figma">({{ students.total }})</span>
            </h2>
            <Button variant="primary" size="sm" label="Nouvel élève" @click="openCreate" />
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
                v-for="(student, index) in students.data"
                :key="student.id"
                class="flex items-start justify-between gap-3 px-4 py-4"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <span class="mt-0.5 w-5 shrink-0 text-xs text-stone-400">
                        {{
                            sortDir === 'desc'
                                ? String(students.total - (students.current_page - 1) * students.per_page - index).padStart(2, '0')
                                : String((students.current_page - 1) * students.per_page + index + 1).padStart(2, '0')
                        }}
                    </span>
                    <div class="flex min-w-0 flex-col gap-1">
                        <span class="truncate text-base font-medium text-text-base">
                            {{ student.lastname }} {{ student.firstname }}
                        </span>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="g in student.groups"
                                :key="g.id"
                                :href="`/classlist/${g.slug}`"
                            >
                                {{ g.grade }}{{ g.name }}
                            </Badge>
                        </div>
                        <span v-if="student.email" class="truncate text-xs text-stone-400">{{ student.email }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 flex-col">
                    <LinkButton :href="`/students/${student.id}`" variant="secondary" size="sm" :icon-only="true" title="Voir / modifier l'élève">
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
                        v-for="(student, index) in students.data"
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
                                    :href="`/classlist/${g.slug}`"
                                >
                                    {{ g.grade }}{{ g.name }}
                                </Badge>
                                <span v-if="student.groups.length === 0" class="text-sm text-border-figma">—</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm text-text-base">{{ student.email ?? '—' }}</td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                <LinkButton :href="`/students/${student.id}`" variant="secondary" size="sm" :icon-only="true" title="Voir / modifier l'élève">
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

    <BaseModal ref="modalRef">
        <form class="flex flex-col gap-5" @submit.prevent="save">
            <h2 class="text-xl font-bold text-black">Nouvel élève</h2>
            <InputLabel v-model="form.lastname" label="Nom" placeholder="Dupont" :error="form.errors.lastname" />
            <InputLabel v-model="form.firstname" label="Prénom" placeholder="Marie" :error="form.errors.firstname" />
            <InputLabel v-model="form.email" type="email" label="Email (optionnel)" placeholder="marie@exemple.be" :error="form.errors.email" />

            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-border-figma">
                    Groupes <span class="normal-case font-normal">(optionnel)</span>
                </label>
                <div v-if="form.group_ids.length" class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="id in form.group_ids"
                        :key="id"
                        removable
                        @remove="removeGroup(id)"
                    >
                        {{ groups.find((g) => String(g.id) === id)?.grade }}{{ groups.find((g) => String(g.id) === id)?.name }}
                    </Badge>
                </div>
                <select
                    v-if="availableGroups.length"
                    class="w-full rounded-2xl bg-white px-3 py-3 text-sm text-text-base outline outline-1 -outline-offset-1 outline-border-figma"
                    @change="addGroup"
                >
                    <option value="">Ajouter un groupe…</option>
                    <option v-for="g in availableGroups" :key="g.id" :value="String(g.id)">
                        {{ g.grade }}{{ g.name }}
                    </option>
                </select>
                <span v-else-if="groups.length && !availableGroups.length" class="text-xs text-border-figma">
                    Tous les groupes sont assignés.
                </span>
            </div>

            <div class="flex gap-3">
                <Button type="submit" variant="primary" size="sm" label="Enregistrer" class="flex-1"
                    :disabled="!form.lastname || !form.firstname" :loading="form.processing" />
                <Button type="button" variant="danger" size="sm" label="Annuler" class="flex-1" @click="closeModal" />
            </div>
        </form>
    </BaseModal>
</template>
