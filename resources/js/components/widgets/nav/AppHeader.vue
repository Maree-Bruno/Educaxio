<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Button from '@/components/widgets/Button.vue';
import Logout from '@/components/widgets/svg/Logout.vue';
import Pin from '@/components/widgets/svg/Pin.vue';
import { useUserHelpers } from '@/composables/useUserHelpers';
import { logout } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { useAuthStore } from '@/stores/auth';
import { useNavigationStore } from '@/stores/navigation';
import NavHeader from './NavHeader.vue';

const { getUserImageUrl, getUserImageSrcset } = useUserHelpers();

function handleLogout() {
    router.post(logout.url());
}

const nav = useNavigationStore();
const auth = useAuthStore();
const page = usePage();

// Ferme le drawer mobile à chaque navigation
watch(
    () => page.url,
    () => nav.closeMobile(),
);

// Détection desktop pour le bouton logout
const isDesktop = ref(true);
function checkBreakpoint() {
    isDesktop.value = window.innerWidth >= 768;
}
onMounted(() => {
    checkBreakpoint();
    window.addEventListener('resize', checkBreakpoint);
});
onUnmounted(() => {
    window.removeEventListener('resize', checkBreakpoint);
});
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="nav.isMobileOpen"
            class="fixed inset-0 z-30 bg-black/40 md:hidden"
            aria-hidden="true"
            @click="nav.closeMobile()"
        />
    </Transition>

    <aside
        :class="[
            'fixed top-0 left-0 z-40 h-screen w-64 bg-white shadow-sm',
            'transition-transform duration-300 ease-out md:transition-[width] md:duration-300 md:ease-in-out',
            nav.isMobileOpen ? 'translate-x-0' : '-translate-x-full',
            nav.isCollapsed
                ? 'md:w-16 md:translate-x-0'
                : 'md:w-64 md:translate-x-0',
        ]"
        aria-label="Navigation principale"
    >
        <h2 class="sr-only">Navigation principale</h2>
        <div class="mt-4 flex h-full flex-col">
            <div class="mx-2 mb-4 flex items-center justify-between pb-3">
                <div
                    class="overflow-hidden transition-[opacity,max-width] ease-in-out"
                    :class="
                        nav.isCollapsed
                            ? 'duration-150 md:max-w-0 md:opacity-0'
                            : 'opacity-100 delay-150 duration-200'
                    "
                >
                    <AppLogoIcon class="h-8 w-32 text-blue-dark" />
                </div>

                <button
                    class="hidden cursor-pointer rounded-lg p-2 transition-all duration-200 ease-in-out hover:scale-110 hover:bg-hover-nav md:flex"
                    :class="[
                        nav.isCollapsed ? 'mx-auto' : '',
                        nav.isPinned ? 'bg-hover-nav' : '',
                    ]"
                    :aria-label="
                        nav.isPinned
                            ? 'Désépingler la navigation'
                            : 'Épingler la navigation'
                    "
                    :title="
                        nav.isPinned
                            ? 'Désépingler la navigation'
                            : 'Épingler la navigation'
                    "
                    @click="nav.togglePin()"
                >
                    <Pin
                        :size="20"
                        :stroke-width="2"
                        class="text-text-base transition-transform duration-300"
                        :class="nav.isPinned ? 'rotate-0' : 'rotate-90'"
                        aria-hidden="true"
                    />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-4">
                <NavHeader :collapsed="nav.isCollapsed" />
            </div>

            <div
                class="border-t border-border-figma/30 px-2 pt-3 pb-1 md:hidden"
            >
                <Link
                    :href="profileEdit.url()"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-hover-nav"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-dark text-sm font-semibold text-white shadow-sm"
                    >
                        <img
                            v-if="auth.user.picture"
                            :src="getUserImageUrl(auth.user.picture, 'xs')"
                            :srcset="getUserImageSrcset(auth.user.picture)"
                            sizes="36px"
                            :alt="`Photo de ${auth.user.name}`"
                            class="h-full w-full rounded-full object-cover"
                        />
                        <span v-else>{{ auth.initials }}</span>
                    </span>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-text-base">{{
                            auth.user.name
                        }}</span>
                        <span class="text-xs font-medium text-border-figma"
                            >Mon profil</span
                        >
                    </div>
                </Link>
            </div>

            <div class="mt-auto mb-8 px-2 pt-2 pb-4">
                <Button
                    variant="danger"
                    size="sm"
                    :icon-only="nav.isCollapsed && isDesktop"
                    :label="
                        nav.isCollapsed && isDesktop ? '' : 'Se déconnecter'
                    "
                    :title="
                        nav.isCollapsed && isDesktop
                            ? 'Se déconnecter'
                            : undefined
                    "
                    class="w-full"
                    @click="handleLogout"
                >
                    <template #icon>
                        <Logout
                            :size="20"
                            :stroke-width="2"
                            aria-hidden="true"
                        />
                    </template>
                </Button>
            </div>
        </div>
    </aside>
</template>
