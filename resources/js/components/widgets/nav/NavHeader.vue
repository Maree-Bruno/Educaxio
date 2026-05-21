<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Component } from 'vue';
import Attendance from '@/components/widgets/svg/Attendance.vue';
import ClassesList from '@/components/widgets/svg/ClassesList.vue';
import ClipboardCheck from '@/components/widgets/svg/ClipboardCheck.vue';
import Home from '@/components/widgets/svg/Home.vue';
import Schedule from '@/components/widgets/svg/Schedule.vue';
import SchoolAgenda from '@/components/widgets/svg/SchoolAgenda.vue';
import { useAuthStore } from '@/stores/auth';
import { dashboard, classlist, attendances, schedules } from '@/routes';
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
];

const navItems = computed(() =>
    auth.isPureAdmin
        ? allNavItems.filter(
              (item) =>
                  item.href !== attendances.url() &&
                  item.href !== schedules.url(),
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

        <!-- Navigation commune -->
        <ul class="space-y-1">
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
                        :href="`/schools/${school.slug}/students`"
                        title="Élèves"
                        :active="isActive(`/schools/${school.slug}/students`)"
                        :collapsed="collapsed"
                    >
                        <template #icon>
                            <Attendance :size="20" :stroke-width="2" />
                        </template>
                    </NavItem>
                    <NavItem
                        :href="`/schools/${school.slug}/teachers`"
                        title="Professeurs"
                        :active="isActive(`/schools/${school.slug}/teachers`)"
                        :collapsed="collapsed"
                    >
                        <template #icon>
                            <SchoolAgenda :size="20" :stroke-width="2" />
                        </template>
                    </NavItem>
                    <NavItem
                        :href="`/schools/${school.slug}/lessons`"
                        title="Attribution"
                        :active="isActive(`/schools/${school.slug}/lessons`)"
                        :collapsed="collapsed"
                    >
                        <template #icon>
                            <ClipboardCheck :size="20" :stroke-width="2" />
                        </template>
                    </NavItem>
                </template>
            </template>
        </ul>
    </nav>
</template>

<style scoped></style>
