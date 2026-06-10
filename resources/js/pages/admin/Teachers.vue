<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import JoinRequestsModal from '@/components/admin/JoinRequestsModal.vue';
import Badge from '@/components/widgets/Badge.vue';
import Button from '@/components/widgets/Button.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import SortTh from '@/components/widgets/SortTh.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import UserAvatar from '@/components/widgets/UserAvatar.vue';
import { useHiddenIds } from '@/composables/useHiddenIds';
import { setPageTitle } from '@/composables/usePageTitle';
import { useSort } from '@/composables/useStudentSort';
import { resolveSubjectLabel } from '@/composables/useSubjectLabel';
import { index as adminLessonsIndex } from '@/routes/admin/lessons';
import { index as adminTeachersIndex, destroy as adminTeachersDestroy } from '@/routes/admin/teachers';
import { useToasterStore } from '@/stores/toaster';
import type { Paginator } from '@/types';

interface School      { id: number; name: string; slug: string }
interface Group       { id: number; grade: string; name: string }
interface Subject     { id: number; name: string }
interface Lesson      { id: number; group: Group; subject: Subject; lm_level: number | null }
interface Teacher     { id: number; name: string; email: string; picture: string | null; lessons: Lesson[]; subjects: Subject[] }
interface JoinRequest { id: number; user: { id: number; name: string; email: string }; subjects: Subject[] }

const props = defineProps<{
    school:       School;
    teachers:     Paginator<Teacher>;
    filters:      { search?: string; sort?: string; dir?: string };
    joinRequests: JoinRequest[];
}>();

setPageTitle('Professeurs');

const search = ref(props.filters.search ?? '');
const { sortCol, sortDir, sortBy } = useSort({
    col: props.filters.sort ?? 'name',
    dir: props.filters.dir === 'desc' ? 'desc' : 'asc',
});

function applyFilters() {
    router.get(adminTeachersIndex.url({ school: props.school.slug }), {
        search: search.value.trim() || undefined,
        sort:   sortCol.value !== 'name' ? sortCol.value : undefined,
        dir:    sortDir.value === 'desc' ? 'desc' : undefined,
    }, { preserveState: true, replace: true });
}

watch(search, useDebounceFn(applyFilters, 300));
watch([sortCol, sortDir], applyFilters);

const toaster = useToasterStore();
const { hide, show, isHidden } = useHiddenIds();
const pendingDelete = ref<Teacher | null>(null);

function confirmUnlink() {
    if (!pendingDelete.value) {
        return;
    }

    const { id, name } = pendingDelete.value;
    pendingDelete.value = null;
    hide(id);

    toaster.deletable(
        `${name} retiré de l'établissement`,
        () => router.delete(adminTeachersDestroy.url({ school: props.school.slug, user: id }), { preserveScroll: true }),
        () => show(id),
    );
}
</script>

<template>
    <ConfirmModal
        :open="pendingDelete !== null"
        :title="`Retirer ${pendingDelete?.name ?? ''} de l'établissement`"
        message="Le professeur perdra l'accès à l'établissement et ses cours associés."
        @confirm="confirmUnlink"
        @cancel="pendingDelete = null"
    />

    <div class="min-w-0 overflow-hidden rounded-2xl">

        <div class="flex flex-col gap-3 border-b border-neutral-300/10 bg-white px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6 sm:py-5">
            <div>
                <h2 class="text-xl font-bold text-text-base">
                    Professeurs
                    <span class="text-border-figma">({{ teachers.total }})</span>
                </h2>
                <p class="mt-0.5 text-xs text-stone-400">Gérez les professeurs et leurs cours attribués.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <JoinRequestsModal :join-requests="joinRequests" :school="school" />
                <div class="flex-1 sm:w-64 sm:flex-none">
                    <SearchInput id="teacher-search" v-model="search" placeholder="Rechercher…" />
                </div>
            </div>
        </div>

        <ul class="lg:hidden divide-y divide-neutral-100 bg-white">
            <li
                v-for="(teacher, index) in teachers.data.filter((t) => !isHidden(t.id))"
                :key="teacher.id"
                class="flex items-start gap-3 px-4 py-4"
            >
                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-5 shrink-0 text-xs text-stone-400">
                            {{ String((teachers.current_page - 1) * teachers.per_page + index + 1).padStart(2, '0') }}
                        </span>
                        <UserAvatar
                            :name="teacher.name"
                            :picture="teacher.picture"
                            image-size="xs"
                            class="size-8 shrink-0 text-xs"
                        />
                        <span class="text-base font-medium text-text-base">{{ teacher.name }}</span>
                    </div>
                    <span v-if="teacher.email" class="pl-7 text-xs text-stone-400">{{ teacher.email }}</span>
                    <div class="flex flex-wrap gap-1.5 pl-7">
                        <Badge v-for="s in teacher.subjects" :key="s.id" variant="neutral">{{ s.name }}</Badge>
                        <span v-if="teacher.subjects.length === 0" class="text-xs text-border-figma">Aucune matière</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5 pl-7">
                        <Badge v-for="l in teacher.lessons" :key="l.id">
                            {{ l.group.grade }}{{ l.group.name }} — {{ resolveSubjectLabel(l.subject.name, l.lm_level) }}
                        </Badge>
                        <LinkButton v-if="teacher.lessons.length === 0" :href="adminLessonsIndex.url({ school: school.slug })" variant="secondary" size="sm" label="Attribuer des cours" />
                    </div>
                </div>
                <Button variant="danger" size="sm" :icon-only="true" title="Retirer de l'établissement" @click="pendingDelete = teacher">
                    <template #icon><Trash :size="16" :stroke-width="2" aria-hidden="true" /></template>
                </Button>
            </li>
            <li v-if="teachers.total === 0"><EmptyState message="Aucun professeur trouvé" /></li>
        </ul>

        <div class="hidden lg:block overflow-x-auto bg-white">
            <table class="w-full border-collapse text-left">
                <caption class="sr-only">Liste des professeurs</caption>
                <thead>
                    <tr class="bg-gray-100">
                        <th scope="col" class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">N°</th>
                        <th scope="col" class="px-3 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Photo</th>
                        <SortTh col="name" :current-col="sortCol" :current-dir="sortDir" label="Nom" @sort="sortBy" />
                        <SortTh col="email" :current-col="sortCol" :current-dir="sortDir" label="Email" @sort="sortBy" />
                        <th scope="col" class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Matières</th>
                        <th scope="col" class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Cours attribués</th>
                        <th scope="col" class="px-6 py-4 text-xs font-bold uppercase leading-4 tracking-wider text-stone-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(teacher, index) in teachers.data.filter((t) => !isHidden(t.id))"
                        :key="teacher.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-6 py-5 text-sm text-stone-400">
                            {{ String((teachers.current_page - 1) * teachers.per_page + index + 1).padStart(2, '0') }}
                        </td>
                        <td class="w-12 px-3 py-5">
                            <UserAvatar
                                :name="teacher.name"
                                :picture="teacher.picture"
                                image-size="xs"
                                class="size-8 text-xs"
                            />
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-base font-medium text-text-base">{{ teacher.name }}</span>
                        </td>
                        <td class="px-6 py-5 text-sm text-stone-400">{{ teacher.email || '—' }}</td>
                        <td class="px-6 py-5">
                            <div class="flex flex-wrap gap-1.5">
                                <Badge v-for="s in teacher.subjects" :key="s.id" variant="neutral">{{ s.name }}</Badge>
                                <span v-if="teacher.subjects.length === 0" class="text-sm text-border-figma">—</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-wrap gap-1.5">
                                <Badge v-for="l in teacher.lessons" :key="l.id" variant="blue">
                                    {{ l.group.grade }}{{ l.group.name }} — {{ resolveSubjectLabel(l.subject.name, l.lm_level) }}
                                </Badge>
                                <LinkButton v-if="teacher.lessons.length === 0" :href="adminLessonsIndex.url({ school: school.slug })" variant="secondary" size="sm" label="Attribuer des cours" />
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                <Button variant="danger" size="sm" :icon-only="true" title="Retirer de l'établissement" @click="pendingDelete = teacher">
                                    <template #icon><Trash :size="16" :stroke-width="2" aria-hidden="true" /></template>
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="teachers.total === 0">
                        <td colspan="7"><EmptyState message="Aucun professeur trouvé" /></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="rounded-b-2xl bg-gray-100 px-4 sm:px-6 py-4">
            <Pagination :links="teachers.links" :current-page="teachers.current_page" :last-page="teachers.last_page" />
        </div>
    </div>
</template>
