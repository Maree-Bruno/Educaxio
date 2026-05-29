<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/components/widgets/Badge.vue';
import Button from '@/components/widgets/Button.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { approve, reject } from '@/routes/admin/join-requests';
import { index as lessonsIndex } from '@/routes/admin/lessons';
import { index as studentsIndex } from '@/routes/admin/students';
import { index as teachersIndex } from '@/routes/admin/teachers';
import type { PaginationLink, Student, Subject, UserSummary } from '@/types';

setPageTitle('Dashboard');

interface Stats       { students: number; teachers: number; pending: number; lessons: number }
interface JoinRequest { id: number; user: UserSummary; subjects: Subject[] }

type StudentRow = Pick<Student, 'id' | 'firstname' | 'lastname'> & { groups: { id: number; grade: string; name: string; slug: string }[] }
type TeacherRow = UserSummary & { subjects: Subject[] }

interface DashboardPage<T> { data: T[]; current_page: number; last_page: number; links: PaginationLink[] }

interface School {
    id: number;
    name: string;
    slug: string;
    stats: Stats;
    joinRequests: JoinRequest[];
    recentStudents: DashboardPage<StudentRow>;
    teachers: DashboardPage<TeacherRow>;
}

defineProps<{ schools: School[] }>();

const loadingApprove = ref<Record<number, boolean>>({});
const loadingReject  = ref<Record<number, boolean>>({});

function approveRequest(school: School, req: JoinRequest) {
    loadingApprove.value[req.id] = true;
    router.patch(approve.url({ school, joinRequest: req }), {}, {
        preserveScroll: true,
        onFinish: () => {
            loadingApprove.value[req.id] = false;
        },
    });
}

function rejectRequest(school: School, req: JoinRequest) {
    loadingReject.value[req.id] = true;
    router.patch(reject.url({ school, joinRequest: req }), {}, {
        preserveScroll: true,
        onFinish: () => {
            loadingReject.value[req.id] = false;
        },
    });
}
</script>

<template>
    <div v-if="schools.length === 0" class="flex items-center justify-center rounded-2xl bg-white py-20">
        <p class="font-bold text-text-base">Aucune école administrée</p>
    </div>

    <div v-for="school in schools" :key="school.id" class="flex flex-col gap-8">
        <h2 v-if="schools.length > 1" class="text-2xl font-bold text-text-base">{{ school.name }}</h2>
        <!-- Stats -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="flex flex-col gap-1 rounded-2xl bg-white px-5 py-4 shadow-sm outline -outline-offset-1 outline-neutral-300/10">
                <span class="text-3xl font-extrabold text-text-base">{{ school.stats.students }}</span>
                <span class="text-xs font-bold uppercase tracking-wider text-border-figma">Élèves</span>
            </div>
            <div class="flex flex-col gap-1 rounded-2xl bg-white px-5 py-4 shadow-sm outline -outline-offset-1 outline-neutral-300/10">
                <span class="text-3xl font-extrabold text-text-base">{{ school.stats.teachers }}</span>
                <span class="text-xs font-bold uppercase tracking-wider text-border-figma">Professeurs</span>
            </div>
            <div class="flex flex-col gap-1 rounded-2xl bg-white px-5 py-4 shadow-sm outline -outline-offset-1 outline-neutral-300/10">
                <span class="text-3xl font-extrabold" :class="school.stats.pending > 0 ? 'text-orange' : 'text-text-base'">{{ school.stats.pending }}</span>
                <span class="text-xs font-bold uppercase tracking-wider text-border-figma">En attente d'adhésion</span>
            </div>
            <div class="flex flex-col gap-1 rounded-2xl bg-white px-5 py-4 shadow-sm outline -outline-offset-1 outline-neutral-300/10">
                <span class="text-3xl font-extrabold text-text-base">{{ school.stats.lessons }}</span>
                <span class="text-xs font-bold uppercase tracking-wider text-border-figma">Cours</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 xl:grid-cols-2 xl:items-start">

            <!-- Colonne gauche -->
            <div class="flex flex-col gap-8">

                <!-- Demandes en attente -->
                <div class="overflow-hidden rounded-2xl">
                    <div class="flex items-center justify-between border-b border-neutral-300/10 bg-white px-6 py-5">
                        <h3 class="text-base font-bold text-stone-900">
                            Demandes d'adhésion
                            <span v-if="school.stats.pending > 0" class="ml-1 rounded-full bg-orange px-2 py-0.5 text-xs font-bold text-white">
                                {{ school.stats.pending }}
                            </span>
                        </h3>
                        <LinkButton :href="teachersIndex.url(school)" variant="primary" size="sm" label="Voir tout" />
                    </div>

                    <EmptyState v-if="school.joinRequests.length === 0" message="Aucune demande en attente" class="bg-white" />

                    <ul v-else class="divide-y divide-neutral-100 bg-white">
                        <li
                            v-for="req in school.joinRequests"
                            :key="req.id"
                            class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:gap-4"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-text-base">{{ req.user.name }}</p>
                                <p class="truncate text-xs text-border-figma">{{ req.user.email }}</p>
                                <p v-if="req.subjects.length" class="mt-1 truncate text-xs text-stone-400">
                                    {{ req.subjects.map(s => s.name).join(', ') }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <Button
                                    variant="primary"
                                    size="sm"
                                    label="Approuver"
                                    :loading="loadingApprove[req.id]"
                                    @click="approveRequest(school, req)"
                                />
                                <Button
                                    variant="danger"
                                    size="sm"
                                    label="Refuser"
                                    :loading="loadingReject[req.id]"
                                    @click="rejectRequest(school, req)"
                                />
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Élèves récents -->
                <div class="overflow-hidden rounded-2xl">
                    <div class="flex items-center justify-between border-b border-neutral-300/10 bg-white px-6 py-5">
                        <h3 class="text-base font-bold text-stone-900">Élèves récents</h3>
                        <LinkButton :href="studentsIndex.url(school)" variant="primary" size="sm" label="Voir tout" />
                    </div>

                    <EmptyState v-if="school.recentStudents.data.length === 0" message="Aucun élève" class="bg-white" />

                    <template v-else>
                        <ul class="divide-y divide-neutral-100 bg-white">
                            <li
                                v-for="student in school.recentStudents.data"
                                :key="student.id"
                                class="flex items-center justify-between gap-3 px-6 py-4"
                            >
                                <span class="truncate text-sm font-medium text-text-base">{{ student.lastname }} {{ student.firstname }}</span>
                                <div class="flex shrink-0 flex-wrap gap-1">
                                    <Badge
                                        v-for="g in student.groups"
                                        :key="g.id"
                                        :href="`/classlist/${g.slug}`"
                                    >{{ g.grade }}{{ g.name }}</Badge>
                                </div>
                            </li>
                        </ul>
                        <div class="bg-gray-100 px-6 py-3">
                            <Pagination
                                :links="school.recentStudents.links"
                                :current-page="school.recentStudents.current_page"
                                :last-page="school.recentStudents.last_page"
                            />
                        </div>
                    </template>
                </div>

            </div>

            <!-- Colonne droite -->
            <div class="flex flex-col gap-8">

                <!-- Professeurs -->
                <div class="overflow-hidden rounded-2xl">
                    <div class="flex items-center justify-between border-b border-neutral-300/10 bg-white px-6 py-5">
                        <h3 class="text-base font-bold text-stone-900">Professeurs</h3>
                        <LinkButton :href="teachersIndex.url(school)" variant="primary" size="sm" label="Voir tout" />
                    </div>

                    <EmptyState v-if="school.teachers.data.length === 0" message="Aucun professeur" class="bg-white" />

                    <template v-else>
                        <ul class="divide-y divide-neutral-100 bg-white">
                            <li
                                v-for="teacher in school.teachers.data"
                                :key="teacher.id"
                                class="flex items-center justify-between gap-3 px-6 py-4"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-text-base">{{ teacher.name }}</p>
                                    <p class="truncate text-xs text-border-figma">{{ teacher.email }}</p>
                                </div>
                                <p v-if="teacher.subjects.length" class="shrink-0 text-right text-xs text-stone-400">
                                    {{ teacher.subjects.map(s => s.name).join(', ') }}
                                </p>
                            </li>
                        </ul>
                        <div class="bg-gray-100 px-6 py-3">
                            <Pagination
                                :links="school.teachers.links"
                                :current-page="school.teachers.current_page"
                                :last-page="school.teachers.last_page"
                            />
                        </div>
                    </template>
                </div>

                <!-- Accès rapide -->
                <div class="overflow-hidden rounded-2xl">
                    <div class="border-b border-neutral-300/10 bg-white px-6 py-5">
                        <h3 class="text-base font-bold text-stone-900">Accès rapide</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3 bg-white p-6">
                        <LinkButton href="/classlist" variant="secondary" size="sm" label="Groupes" class="justify-center" />
                        <LinkButton :href="studentsIndex.url(school)" variant="secondary" size="sm" label="Élèves" class="justify-center" />
                        <LinkButton :href="teachersIndex.url(school)" variant="secondary" size="sm" label="Professeurs" class="justify-center" />
                        <LinkButton :href="lessonsIndex.url(school)" variant="secondary" size="sm" label="Cours" class="justify-center" />
                    </div>
                </div>

            </div>

        </div>
    </div>
</template>
