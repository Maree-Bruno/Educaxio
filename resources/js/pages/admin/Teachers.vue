<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import ArrowUpDown from '@/components/widgets/svg/ArrowUpDown.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { Paginator } from '@/types';

interface School   { id: number; name: string; slug: string }
interface Group    { id: number; grade: string; name: string }
interface Subject  { id: number; name: string }
interface Lesson   { id: number; group: Group; subject: Subject }
interface Teacher  { id: number; name: string; email: string; lessons: Lesson[] }

const props = defineProps<{
    school:   School;
    teachers: Paginator<Teacher>;
    filters:  { search?: string; sort?: string; dir?: string };
}>();

setPageTitle('Professeurs');

const base    = `/schools/${props.school.slug}/teachers`;
const search  = ref(props.filters.search ?? '');
const sortCol = ref(props.filters.sort ?? 'name');
const sortDir = ref<'asc' | 'desc'>(props.filters.dir === 'desc' ? 'desc' : 'asc');

function applyFilters() {
    router.get(base, {
        search: search.value.trim() || undefined,
        sort:   sortCol.value !== 'name' ? sortCol.value : undefined,
        dir:    sortDir.value === 'desc' ? 'desc' : undefined,
    }, { preserveState: true, replace: true });
}

const applyFiltersDebounced = useDebounceFn(applyFilters, 300);

watch(search, applyFiltersDebounced);

function sortBy(col: 'name' | 'email') {
    if (sortCol.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortCol.value = col;
        sortDir.value = 'asc';
    }

    applyFilters();
}
</script>

<template>
    <div class="min-w-0 overflow-hidden rounded-2xl">

        <!-- En-tête -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-300/10 bg-white px-4 sm:px-6 py-4 sm:py-5">
            <h2 class="text-xl font-bold text-text-base">
                Professeurs
                <span class="text-border-figma">({{ teachers.total }})</span>
            </h2>
            <div class="w-full sm:w-64">
                <SearchInput
                    id="teacher-search"
                    v-model="search"
                    placeholder="Rechercher…"
                />
            </div>
        </div>

        <!-- Mobile : liste de cartes -->
        <ul class="sm:hidden divide-y divide-neutral-100 bg-white">
            <li
                v-for="(teacher, index) in teachers.data"
                :key="teacher.id"
                class="flex flex-col gap-2 px-4 py-4"
            >
                <div class="flex items-center gap-2">
                    <span class="w-5 shrink-0 text-xs text-stone-400">
                        {{ String((teachers.current_page - 1) * teachers.per_page + index + 1).padStart(2, '0') }}
                    </span>
                    <span class="text-base font-medium text-text-base">{{ teacher.name }}</span>
                </div>
                <span v-if="teacher.email" class="pl-7 text-xs text-stone-400">{{ teacher.email }}</span>
                <div class="flex flex-wrap gap-1.5 pl-7">
                    <span
                        v-for="lesson in teacher.lessons"
                        :key="lesson.id"
                        class="rounded-lg bg-blue/10 px-2 py-0.5 text-xs font-bold text-blue"
                    >
                        {{ lesson.group.grade }}{{ lesson.group.name }} — {{ lesson.subject.name }}
                    </span>
                    <span v-if="teacher.lessons.length === 0" class="text-xs text-border-figma">Aucun cours attribué</span>
                </div>
            </li>
            <li v-if="teachers.total === 0" class="px-4 py-16 text-center text-sm font-bold text-border-figma">
                Aucun professeur trouvé
            </li>
        </ul>

        <!-- Desktop : tableau -->
        <div class="hidden sm:block overflow-x-auto bg-white">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">N°</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">
                            <button
                                type="button"
                                class="flex items-center gap-1.5 transition-colors hover:text-text-base"
                                @click="sortBy('name')"
                            >
                                Nom
                                <ArrowUpDown
                                    :size="13"
                                    :stroke-width="2.5"
                                    class="transition-transform duration-200"
                                    :class="{
                                        'rotate-180': sortCol === 'name' && sortDir === 'desc',
                                        'opacity-30': sortCol !== 'name',
                                    }"
                                    aria-hidden="true"
                                />
                            </button>
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">
                            <button
                                type="button"
                                class="flex items-center gap-1.5 transition-colors hover:text-text-base"
                                @click="sortBy('email')"
                            >
                                Email
                                <ArrowUpDown
                                    :size="13"
                                    :stroke-width="2.5"
                                    class="transition-transform duration-200"
                                    :class="{
                                        'rotate-180': sortCol === 'email' && sortDir === 'desc',
                                        'opacity-30': sortCol !== 'email',
                                    }"
                                    aria-hidden="true"
                                />
                            </button>
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">
                            Cours attribués
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(teacher, index) in teachers.data"
                        :key="teacher.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-6 py-5 text-sm text-stone-400">
                            {{ String((teachers.current_page - 1) * teachers.per_page + index + 1).padStart(2, '0') }}
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-base font-medium text-text-base">{{ teacher.name }}</span>
                        </td>
                        <td class="px-6 py-5 text-sm text-stone-400">
                            {{ teacher.email || '—' }}
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="lesson in teacher.lessons"
                                    :key="lesson.id"
                                    class="rounded-lg bg-blue/10 px-2 py-0.5 text-xs font-bold text-blue"
                                >
                                    {{ lesson.group.grade }}{{ lesson.group.name }} — {{ lesson.subject.name }}
                                </span>
                                <span v-if="teacher.lessons.length === 0" class="text-sm text-border-figma">—</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="teachers.total === 0">
                        <td colspan="4" class="px-6 py-16 text-center text-sm font-bold text-border-figma">
                            Aucun professeur trouvé
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pied : pagination -->
        <div class="rounded-b-2xl bg-gray-100 px-4 sm:px-6 py-4">
            <Pagination
                :links="teachers.links"
                :current-page="teachers.current_page"
                :last-page="teachers.last_page"
            />
        </div>
    </div>
</template>