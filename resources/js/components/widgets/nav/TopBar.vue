<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Bell from '@/components/widgets/svg/Bell.vue';
import Menu from '@/components/widgets/svg/Menu.vue';
import UserAvatar from '@/components/widgets/UserAvatar.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { edit as profileEdit } from '@/routes/profile';
import { useAuthStore } from '@/stores/auth';
import { useNavigationStore } from '@/stores/navigation';

const title = usePageTitle();
const auth = useAuthStore();
const nav = useNavigationStore();
</script>

<template>
    <div
        class="sticky top-0 z-30 flex h-16 w-full items-center justify-between bg-white px-3 lg:px-6 shadow-[0px_1px_2px_0px_rgba(48,48,48,0.05)]"
    >
        <!-- Burger mobile -->
        <button
            class="rounded-lg p-2 transition-colors hover:bg-hover-nav md:hidden"
            aria-label="Ouvrir le menu"
            @click="nav.toggleMobile()"
        >
            <Menu :size="20" :stroke-width="2" class="text-text-base" aria-hidden="true" />
        </button>

        <!-- Titre -->
        <p class="text-xl lg:text-3xl font-bold tracking-[-2px] text-text-base ">
            {{ title }}
        </p>

        <!-- Actions droite -->
        <div class="flex items-center gap-4">
            <!-- Cloche notification -->
<!--            <button
                class="rounded-full p-2 transition-colors hover:bg-hover-nav"
                aria-label="Notifications"
            >
                <Bell :size="20" :stroke-width="2" class="text-text-base" aria-hidden="true" />
            </button>-->

            <!-- Séparateur -->
            <div class="hidden h-8 w-px bg-border-figma md:block" />

            <!-- Profil (desktop uniquement) -->
            <Link
                :href="profileEdit.url()"
                class="group hidden items-center gap-3 rounded-lg px-2 py-1 transition-colors hover:bg-hover-nav md:flex"
            >
                <span class="text-right text-xs leading-4 font-bold text-text-base">
                    {{ auth.user.name }}
                </span>

                <UserAvatar
                    :picture="auth.user.picture ?? null"
                    :name="auth.user.name ?? ''"
                    image-size="xs"
                    sizes="36px"
                    class="h-9 w-9 text-sm shadow-sm transition-transform duration-200 group-hover:scale-105"
                />
            </Link>
        </div>
    </div>
</template>
