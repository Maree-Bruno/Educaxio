<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SortTh from '@/components/widgets/SortTh.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { approve, reject } from '@/routes/admin/join-requests';
import type { Paginator } from '@/types';

interface School      { id: number; name: string; slug: string }
interface Group       { id: number; grade: string; name: string }
interface Subject     { id: number; name: string }
interface Lesson      { id: number; group: Group; subject: Subject }
interface Teacher     { id: number; name: string; email: string; lessons: Lesson[] }
interface JoinRequest { id: number; user: { id: number; name: string; email: string }; subjects: Subject[] }

const props = defineProps<{
    school:       School;
    teachers:     Paginator<Teacher>;
    filters:      { search?: string; sort?: string; dir?: string };
    joinRequests: JoinRequest[];
}>();

setPageTitle('Professeurs');

const base    = `/schools/${props.school.slug}/teachers`;
const search  = ref(props.filters.search ?? '');
const sortCol = ref(props.filters.sort ?? 'name');
const sortDir = ref<'asc' | 'desc'>(props.filters.dir === 'desc' ? 'desc' : 'asc');
const requestsModal = ref<InstanceType<typeof BaseModal> | null>(null);

function applyFilters() {
    router.get(base, {
        search: search.value.trim() || undefined,
        sort:   sortCol.value !== 'name' ? sortCol.value : undefined,
        dir:    sortDir.value === 'desc' ? 'desc' : undefined,
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

function approveRequest(id: number) {
    router.patch(approve.url({ school: props.school.slug, joinRequest: id }));
}

function rejectRequest(id: number) {
    router.patch(reject.url({ school: props.school.slug, joinRequest: id }));
}
</script>

<template>
    <div class="min-w-0 overflow-hidden rounded-2xl">

        <div class="flex flex-col gap-3 border-b border-neutral-300/10 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
            <h2 class="text-xl font-bold text-text-base">
                Professeurs
                <span class="text-border-figma">({{ teachers.total }})</span>
            </h2>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="flex shrink-0 items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm font-medium transition-colors"
                    :class="joinRequests.length > 0 ? 'text-text-base hover:bg-gray-50' : 'cursor-not-allowed text-border-figma opacity-50'"
                    :disabled="joinRequests.length === 0"
                    @click="requestsModal?.open()"
                >
                    Demandes d'adhésion
                    <span
                        v-if="joinRequests.length > 0"
                        class="flex size-5 items-center justify-center rounded-full bg-blue text-xs font-bold text-white"
                    >
                        {{ joinRequests.length }}
                    </span>
                </button>
                <div class="flex-1 sm:w-64 sm:flex-none">
                    <SearchInput id="teacher-search" v-model="search" placeholder="Rechercher…" />
                </div>
            </div>
        </div>

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
                    <Badge v-for="lesson in teacher.lessons" :key="lesson.id">
                        {{ lesson.group.grade }}{{ lesson.group.name }} — {{ lesson.subject.name }}
                    </Badge>
                    <span v-if="teacher.lessons.length === 0" class="text-xs text-border-figma">Aucun cours attribué</span>
                </div>
            </li>
            <li v-if="teachers.total === 0"><EmptyState message="Aucun professeur trouvé" /></li>
        </ul>

        <div class="hidden sm:block overflow-x-auto bg-white">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">N°</th>
                        <SortTh col="name" :current-col="sortCol" :current-dir="sortDir" label="Nom" @sort="sortBy" />
                        <SortTh col="email" :current-col="sortCol" :current-dir="sortDir" label="Email" @sort="sortBy" />
                        <th class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Cours attribués</th>
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
                        <td class="px-6 py-5 text-sm text-stone-400">{{ teacher.email || '—' }}</td>
                        <td class="px-6 py-5">
                            <div class="flex flex-wrap gap-1.5">
                                <Badge v-for="lesson in teacher.lessons" :key="lesson.id" variant="blue">
                                    {{ lesson.group.grade }}{{ lesson.group.name }} — {{ lesson.subject.name }}
                                </Badge>
                                <span v-if="teacher.lessons.length === 0" class="text-sm text-border-figma">—</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="teachers.total === 0">
                        <td colspan="4"><EmptyState message="Aucun professeur trouvé" /></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="rounded-b-2xl bg-gray-100 px-4 sm:px-6 py-4">
            <Pagination :links="teachers.links" :current-page="teachers.current_page" :last-page="teachers.last_page" />
        </div>
    </div>

    <BaseModal ref="requestsModal" class="max-w-lg!">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-text-base">Demandes d'adhésion</h3>
                <Button variant="ghost" size="sm" label="Fermer" icon-only @click="requestsModal?.close()">
                    <template #icon>
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                </Button>
            </div>

            <ul class="flex flex-col gap-3">
                <li
                    v-for="req in joinRequests"
                    :key="req.id"
                    class="flex flex-col gap-3 rounded-2xl  outline-border-figma p-4 bg-white"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-sm font-bold text-neutral-500">
                            {{ req.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-text-base">{{ req.user.name }}</p>
                            <p class="truncate text-sm text-stone-400">{{ req.user.email }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <Badge v-for="s in req.subjects" :key="s.id" variant="neutral">{{ s.name }}</Badge>
                        <span v-if="req.subjects.length === 0" class="text-xs text-border-figma">Aucune matière renseignée</span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="danger" size="sm" label="Refuser" @click="rejectRequest(req.id)" />
                        <Button variant="primary" size="sm" label="Accepter" @click="approveRequest(req.id)" />
                    </div>
                </li>
            </ul>
        </div>
    </BaseModal>
</template>
