import { usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { computed } from 'vue';
import type { Auth } from '@/types/auth';

export const useAuthStore = defineStore('auth', () => {
    const user = computed(() => (usePage().props.auth as Auth).user);

    const initials = computed(() =>
        user.value.name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2),
    );

    return { user, initials };
});