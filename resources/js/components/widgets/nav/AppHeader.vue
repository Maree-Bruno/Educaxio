<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Button from '@/components/widgets/Button.vue';
import Pin from '@/components/widgets/svg/Pin.vue';
import Logout from '@/components/widgets/svg/Logout.vue';
import { logout } from '@/routes';
import { useNavigationStore } from '@/stores/navigation';
import NavHeader from './NavHeader.vue';

function handleLogout() {
    router.post(logout.url());
}

const nav = useNavigationStore();
</script>

<template>
    <aside
        :class="[
            'fixed top-0 left-0 z-40 h-screen bg-white shadow-sm transition-[width] duration-300 ease-in-out',
            nav.isCollapsed ? 'w-16' : 'w-64',
        ]"
        aria-label="Navigation principale"
    >
        <nav class="mt-4 flex h-full flex-col">
            <!-- Logo + pin -->
            <div class="mx-2 mb-4 flex items-center justify-between pb-3">
                <div
                    class="overflow-hidden transition-[opacity,max-width] ease-in-out"
                    :class="
                        nav.isCollapsed
                            ? 'max-w-0 opacity-0 duration-150'
                            : 'opacity-100 delay-150 duration-200'
                    "
                >
                    <AppLogoIcon class="h-8 w-32 text-blue-dark" />
                </div>

                <button
                    @click="nav.togglePin()"
                    class="cursor-pointer rounded-lg p-2 transition-all duration-200 ease-in-out hover:scale-110 hover:bg-hover-nav"
                    :class="[
                        nav.isCollapsed ? 'mx-auto' : '',
                        nav.isPinned ? 'bg-hover-nav' : '',
                    ]"
                    :aria-label="nav.isPinned ? 'Désépingler la navigation' : 'Épingler la navigation'"
                    :title="nav.isPinned ? 'Désépingler la navigation' : 'Épingler la navigation'"
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

            <!-- Navigation -->
            <div class="flex-1 overflow-y-auto px-4 py-4">
                <NavHeader :collapsed="nav.isCollapsed" />
            </div>

            <!-- Logout -->
            <div class="mt-auto mb-8 px-2 pt-2 pb-4">
                <Button
                    variant="danger"
                    size="sm"
                    :icon-only="nav.isCollapsed"
                    :label="nav.isCollapsed ? '' : 'Se déconnecter'"
                    :title="nav.isCollapsed ? 'Se déconnecter' : undefined"
                    class="w-full"
                    @click="handleLogout"
                >
                    <template #icon>
                        <Logout :size="20" :stroke-width="2" aria-hidden="true" />
                    </template>
                </Button>
            </div>
        </nav>
    </aside>
</template>
