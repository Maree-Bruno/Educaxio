<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { edit as profileEdit } from '@/routes/profile';
import type { Auth } from '@/types/auth';

defineProps<{
    title?: string;
}>();

const user = (usePage().props.auth as Auth).user;

const initials = computed(() =>
    user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2),
);
</script>

<template>
    <div
        class="sticky top-0 z-30 flex h-16 w-full items-center justify-between bg-white px-6 shadow-[0px_1px_2px_0px_rgba(48,48,48,0.05)]">
        <!-- Titre -->
        <h1 class="text-3xl font-bold text-text-base tracking-[-2px]">{{ title }}</h1>

        <!-- Actions droite -->
        <div class="flex items-center gap-4">
            <!-- Cloche notification -->
            <button class="rounded-full p-2 transition-colors hover:bg-hover-nav" aria-label="Notifications">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-text-base"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
            </button>

            <!-- Séparateur -->
            <div class="h-8 w-px bg-border-figma" />

            <!-- Profil -->
            <Link
                :href="profileEdit.url()"
                class="group flex items-center gap-3 rounded-lg px-2 py-1 transition-colors hover:bg-hover-nav"
            >
                <span class="text-right text-xs font-bold leading-4 text-text-base">
                    {{ user.name }}
                </span>

                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-dark text-sm font-semibold text-white shadow-sm transition-transform duration-200 group-hover:scale-105">
                    <img
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="`Photo de ${user.name}`"
                        class="h-full w-full rounded-full object-cover"
                    />
                    <span v-else>{{ initials }}</span>
                </span>
            </Link>
        </div>
    </div>
</template>
