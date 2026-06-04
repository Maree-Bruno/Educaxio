<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DashboardCard from '@/components/admin/DashboardCard.vue';
import Badge from '@/components/widgets/Badge.vue';
import Button from '@/components/widgets/Button.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Pagination from '@/components/widgets/Pagination.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { index as academicYearsIndex } from '@/routes/admin/academic-years';
import { approve, reject } from '@/routes/admin/join-requests';
import { index as lessonsIndex } from '@/routes/admin/lessons';
import { index as studentsIndex } from '@/routes/admin/students';
import { index as teachersIndex } from '@/routes/admin/teachers';
import { show as showClasslist } from '@/routes/classlist';
import type { PaginationLink, Student, Subject, UserSummary } from '@/types';

setPageTitle('Dashboard');

interface Stats {
    students: number;
    teachers: number;
    pending: number;
    lessons: number;
}
interface JoinRequest {
    id: number;
    user: UserSummary;
    subjects: Subject[];
}

type StudentRow = Pick<Student, 'id' | 'firstname' | 'lastname'> & {
    groups: { id: number; grade: string; name: string; slug: string }[];
};
type TeacherRow = UserSummary & { subjects: Subject[] };

interface DashboardPage<T> {
    data: T[];
    current_page: number;
    last_page: number;
    links: PaginationLink[];
}

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
const loadingReject = ref<Record<number, boolean>>({});

function approveRequest(school: School, req: JoinRequest) {
    loadingApprove.value[req.id] = true;
    router.patch(
        approve.url({ school, joinRequest: req }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                loadingApprove.value[req.id] = false;
            },
        },
    );
}

function rejectRequest(school: School, req: JoinRequest) {
    loadingReject.value[req.id] = true;
    router.patch(
        reject.url({ school, joinRequest: req }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                loadingReject.value[req.id] = false;
            },
        },
    );
}

function statCards(school: School) {
    return [
        { label: 'Élèves', value: school.stats.students, highlight: false },
        {
            label: 'Professeurs',
            value: school.stats.teachers,
            highlight: false,
        },
        {
            label: "En attente d'adhésion",
            value: school.stats.pending,
            highlight: school.stats.pending > 0,
        },
        { label: 'Cours', value: school.stats.lessons, highlight: false },
    ];
}
</script>

<template>
    <div
        v-if="schools.length === 0"
        class="flex items-center justify-center rounded-2xl bg-white py-20"
    >
        <p class="font-bold text-text-base">Aucune école administrée</p>
    </div>

    <div v-for="school in schools" :key="school.id" class="flex flex-col gap-8">
        <h2 v-if="schools.length > 1" class="text-2xl font-bold text-text-base">
            {{ school.name }}
        </h2>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div
                v-for="stat in statCards(school)"
                :key="stat.label"
                class="flex flex-col gap-1 rounded-2xl bg-white px-5 py-4 shadow-sm outline -outline-offset-1 outline-neutral-300/10"
            >
                <span
                    class="text-3xl font-extrabold"
                    :class="stat.highlight ? 'text-orange' : 'text-text-base'"
                    >{{ stat.value }}</span
                >
                <span
                    class="text-xs font-bold tracking-wider text-border-figma uppercase"
                    >{{ stat.label }}</span
                >
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 xl:grid-cols-2 xl:items-start">
            <div class="flex flex-col gap-8">
                <DashboardCard
                    description="Approuvez ou refusez les demandes de professeurs souhaitant rejoindre l'établissement."
                >
                    <template #title>
                        Demandes d'adhésion
                        <span
                            v-if="school.stats.pending > 0"
                            class="ml-1 rounded-full bg-orange px-2 py-0.5 text-xs font-bold text-white"
                            >{{ school.stats.pending }}</span
                        >
                    </template>
                    <template #action>
                        <LinkButton
                            :href="teachersIndex.url(school)"
                            variant="primary"
                            size="sm"
                            label="Voir tout"
                            class="mt-0.5 shrink-0"
                        />
                    </template>

                    <EmptyState
                        v-if="school.joinRequests.length === 0"
                        message="Aucune demande en attente"
                        class="bg-white"
                    />
                    <ul v-else class="divide-y divide-neutral-100 bg-white">
                        <li
                            v-for="req in school.joinRequests"
                            :key="req.id"
                            class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:gap-4"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-bold text-text-base"
                                >
                                    {{ req.user.name }}
                                </p>
                                <p class="truncate text-xs text-border-figma">
                                    {{ req.user.email }}
                                </p>
                                <p
                                    v-if="req.subjects.length"
                                    class="mt-1 truncate text-xs text-stone-400"
                                >
                                    {{
                                        req.subjects
                                            .map((s) => s.name)
                                            .join(', ')
                                    }}
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
                </DashboardCard>

                <DashboardCard
                    title="Élèves récents"
                    description="Derniers élèves inscrits dans l'établissement."
                >
                    <template #action>
                        <LinkButton
                            :href="studentsIndex.url(school)"
                            variant="primary"
                            size="sm"
                            label="Voir tout"
                            class="mt-0.5 shrink-0"
                        />
                    </template>

                    <EmptyState
                        v-if="school.recentStudents.data.length === 0"
                        message="Aucun élève"
                        class="bg-white"
                    />
                    <template v-else>
                        <ul class="divide-y divide-neutral-100 bg-white">
                            <li
                                v-for="student in school.recentStudents.data"
                                :key="student.id"
                                class="flex items-center justify-between gap-3 px-6 py-4"
                            >
                                <span
                                    class="truncate text-sm font-medium text-text-base"
                                    >{{ student.lastname }}
                                    {{ student.firstname }}</span
                                >
                                <div class="flex shrink-0 flex-wrap gap-1">
                                    <Badge
                                        v-for="g in student.groups"
                                        :key="g.id"
                                        :href="
                                            showClasslist.url({ group: g.slug })
                                        "
                                    >
                                        {{ g.grade }}{{ g.name }}
                                    </Badge>
                                </div>
                            </li>
                        </ul>
                        <div class="bg-gray-100 px-6 py-3">
                            <Pagination
                                :links="school.recentStudents.links"
                                :current-page="
                                    school.recentStudents.current_page
                                "
                                :last-page="school.recentStudents.last_page"
                            />
                        </div>
                    </template>
                </DashboardCard>
            </div>

            <div class="flex flex-col gap-8">
                <DashboardCard
                    title="Professeurs"
                    description="Professeurs actifs et leurs matières enseignées."
                >
                    <template #action>
                        <LinkButton
                            :href="teachersIndex.url(school)"
                            variant="primary"
                            size="sm"
                            label="Voir tout"
                            class="mt-0.5 shrink-0"
                        />
                    </template>

                    <EmptyState
                        v-if="school.teachers.data.length === 0"
                        message="Aucun professeur"
                        class="bg-white"
                    />
                    <template v-else>
                        <ul class="divide-y divide-neutral-100 bg-white">
                            <li
                                v-for="teacher in school.teachers.data"
                                :key="teacher.id"
                                class="flex items-center justify-between gap-3 px-6 py-4"
                            >
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-bold text-text-base"
                                    >
                                        {{ teacher.name }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-border-figma"
                                    >
                                        {{ teacher.email }}
                                    </p>
                                </div>
                                <p
                                    v-if="teacher.subjects.length"
                                    class="shrink-0 text-right text-xs text-stone-400"
                                >
                                    {{
                                        teacher.subjects
                                            .map((s) => s.name)
                                            .join(', ')
                                    }}
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
                </DashboardCard>

                <DashboardCard
                    title="Accès rapide"
                    description="Raccourcis vers les principales sections de gestion."
                >
                    <div class="grid grid-cols-2 gap-3 bg-white p-6">
                        <LinkButton
                            href="/classlist"
                            variant="secondary"
                            size="sm"
                            label="Groupes"
                            class="justify-center"
                        />
                        <LinkButton
                            :href="studentsIndex.url(school)"
                            variant="secondary"
                            size="sm"
                            label="Élèves"
                            class="justify-center"
                        />
                        <LinkButton
                            :href="teachersIndex.url(school)"
                            variant="secondary"
                            size="sm"
                            label="Professeurs"
                            class="justify-center"
                        />
                        <LinkButton
                            :href="lessonsIndex.url(school)"
                            variant="secondary"
                            size="sm"
                            label="Cours"
                            class="justify-center"
                        />
                        <LinkButton
                            :href="academicYearsIndex.url(school)"
                            variant="secondary"
                            size="sm"
                            label="Années scolaires"
                            class="col-span-2 justify-center"
                        />
                    </div>
                </DashboardCard>
            </div>
        </div>
    </div>
</template>
