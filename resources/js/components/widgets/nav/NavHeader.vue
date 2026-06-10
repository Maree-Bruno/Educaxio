<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Component } from 'vue';
import Attendance from '@/components/widgets/svg/Attendance.vue';
import Bell from '@/components/widgets/svg/Bell.vue';
import ClassesList from '@/components/widgets/svg/ClassesList.vue';
import ClipboardCheck from '@/components/widgets/svg/ClipboardCheck.vue';
import Home from '@/components/widgets/svg/Home.vue';
import Pin from '@/components/widgets/svg/Pin.vue';
import Schedule from '@/components/widgets/svg/Schedule.vue';
import SchoolAgenda from '@/components/widgets/svg/SchoolAgenda.vue';
import { useAuthStore } from '@/stores/auth';
import { agenda, dashboard, classlist, attendances, schedules, pending } from '@/routes';
import { index as adminAcademicYearsIndex } from '@/routes/admin/academic-years';
import { index as adminStudentsIndex } from '@/routes/admin/students';
import { index as adminTeachersIndex } from '@/routes/admin/teachers';
import { index as adminLessonsIndex } from '@/routes/admin/lessons';
import NavItem from './NavItem.vue';

const { collapsed = false } = defineProps<{
    collapsed?: boolean;
}>();

const page = usePage();
const auth = useAuthStore();

const allNavItems: { title: string; href: string; icon: Component }[] = [
    { title: 'Dashboard', href: dashboard.url(), icon: Home },
    { title: 'Classes', href: classlist.url(), icon: ClassesList },
    { title: 'Présences', href: attendances.url(), icon: Attendance },
    { title: 'Horaires', href: schedules.url(), icon: Schedule },
    { title: 'Agenda', href: agenda.url(), icon: SchoolAgenda },
];

const navItems = computed(() =>
    auth.isPureAdmin
        ? allNavItems.filter(
              (item) =>
                  item.href !== attendances.url() &&
                  item.href !== schedules.url() &&
                  item.href !== agenda.url(),
          )
        : allNavItems,
);

function isActive(href: string): boolean {
    return page.url.startsWith(href);
}
</script>

<template>
    <nav>
        <h3 class="sr-only">Liens de navigation</h3>

        <ul v-if="auth.isPending" class="space-y-1">
            <NavItem
                :href="pending.url()"
                title="En attente"
                :active="isActive(pending.url())"
                :collapsed="collapsed"
            >
                <template #icon>
                    <Bell :size="20" :stroke-width="2" />
                </template>
            </NavItem>
        </ul>

        <ul v-else class="space-y-1">
            <NavItem
                v-for="item in navItems"
                :key="item.href"
                :href="item.href"
                :title="item.title"
                :active="isActive(item.href)"
                :collapsed="collapsed"
            >
                <template #icon>
                    <component :is="item.icon" :size="20" :stroke-width="2" />
                </template>
            </NavItem>
            <template v-if="auth.adminSchools.length > 0">
                <template v-for="school in auth.adminSchools" :key="school.slug">
                    <NavItem
                        :href="adminStudentsIndex.url({ school: school.slug })"
                        title="Élèves"
                        :active="isActive(adminStudentsIndex.url({ school: school.slug }))"
                        :collapsed="collapsed"
                    >
                        <template #icon>
                            <Attendance :size="20" :stroke-width="2" />
                        </template>
                    </NavItem>
                    <NavItem
                        :href="adminTeachersIndex.url({ school: school.slug })"
                        title="Professeurs"
                        :active="isActive(adminTeachersIndex.url({ school: school.slug }))"
                        :collapsed="collapsed"
                    >
                        <template #icon>
                            <SchoolAgenda :size="20" :stroke-width="2" />
                        </template>
                    </NavItem>
                    <NavItem
                        :href="adminLessonsIndex.url({ school: school.slug })"
                        title="Attribution"
                        :active="isActive(adminLessonsIndex.url({ school: school.slug }))"
                        :collapsed="collapsed"
                    >
                        <template #icon>
                            <ClipboardCheck :size="20" :stroke-width="2" />
                        </template>
                    </NavItem>
                    <NavItem
                        :href="adminAcademicYearsIndex.url({ school: school.slug })"
                        title="Années scolaires"
                        :active="isActive(adminAcademicYearsIndex.url({ school: school.slug }))"
                        :collapsed="collapsed"
                    >
                        <template #icon>
                            <Schedule :size="20" :stroke-width="2" />
                        </template>
                    </NavItem>
                </template>
            </template>
        </ul>
    </nav>
</template>

