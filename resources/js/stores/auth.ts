import { usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { computed } from 'vue';
import type { Auth } from '@/types/auth';

export const useAuthStore = defineStore('auth', () => {
    const user = computed(() => (usePage().props.auth as Auth).user);
    const schoolRoles = computed(() => (usePage().props.auth as Auth).schoolRoles ?? []);
    const adminSchools = computed(() => schoolRoles.value.filter((s) => s.role === 'admin'));
    const isTeacher = computed(() => schoolRoles.value.some((s) => s.role === 'teacher'));
    const isPureAdmin = computed(() => adminSchools.value.length > 0 && !isTeacher.value);

    const initials = computed(() =>
        user.value.name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2),
    );

    return { user, initials, schoolRoles, adminSchools, isTeacher, isPureAdmin };
});