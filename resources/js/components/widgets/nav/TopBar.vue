<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Bell from '@/components/widgets/svg/Bell.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { edit as profileEdit } from '@/routes/profile';
import { useAuthStore } from '@/stores/auth';

const title = usePageTitle();
const auth = useAuthStore();
</script>

<template>
    <div
        class="sticky top-0 z-30 flex h-16 w-full items-center justify-between bg-white px-6 shadow-[0px_1px_2px_0px_rgba(48,48,48,0.05)]"
    >
        <!-- Titre -->
        <h2 class="text-3xl font-bold tracking-[-2px] text-text-base">
            {{ title }}
        </h2>

        <!-- Actions droite -->
        <div class="flex items-center gap-4">
            <!-- Cloche notification -->
            <button
                class="rounded-full p-2 transition-colors hover:bg-hover-nav"
                aria-label="Notifications"
            >
                <Bell :size="20" :stroke-width="2" class="text-text-base" aria-hidden="true" />
            </button>

            <!-- Séparateur -->
            <div class="h-8 w-px bg-border-figma" />

            <!-- Profil -->
            <Link
                :href="profileEdit.url()"
                class="group flex items-center gap-3 rounded-lg px-2 py-1 transition-colors hover:bg-hover-nav"
            >
                <span class="text-right text-xs leading-4 font-bold text-text-base">
                    {{ auth.user.name }}
                </span>

                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-dark text-sm font-semibold text-white shadow-sm transition-transform duration-200 group-hover:scale-105"
                >
                    <img
                        v-if="auth.user.avatar"
                        :src="auth.user.avatar"
                        :alt="`Photo de ${auth.user.name}`"
                        class="h-full w-full rounded-full object-cover"
                    />
                    <span v-else>{{ auth.initials }}</span>
                </span>
            </Link>
        </div>
    </div>
</template>