<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';
import Attendance from '@/components/widgets/svg/Attendance.vue';
import ClassesList from '@/components/widgets/svg/ClassesList.vue';
import Home from '@/components/widgets/svg/Home.vue';
import Schedule from '@/components/widgets/svg/Schedule.vue';
import { dashboard, classlist, attendances,schedules } from '@/routes';
import NavItem from './NavItem.vue';



const { collapsed = false } = defineProps<{
    collapsed?: boolean;
}>();

const page = usePage();

const navItems: { title: string; href: string; icon: Component }[] = [
    { title: 'Dashboard', href: dashboard.url(), icon: Home },
    { title: 'Liste des classes', href: classlist.url(), icon: ClassesList },
    { title: 'Présences', href: attendances.url(), icon: Attendance },
    { title: 'Horaires', href: schedules.url(), icon: Schedule},
];

function isActive(href: string): boolean {
    return page.url.startsWith(href);
}
</script>

<template>
    <nav>
        <h3 class="sr-only">Liens de navigation</h3>
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
        </ul>
    </nav>
</template>

<style scoped></style>
