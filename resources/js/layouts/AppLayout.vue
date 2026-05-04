<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Button from '@/components/widgets/Button.vue';
import NavHeader from '@/components/widgets/nav/NavHeader.vue';
import TopBar from '@/components/widgets/nav/TopBar.vue';
import { logout } from '@/routes';

defineProps<{ title?: string }>();

function handleLogout() {
    router.post(logout.url());
}

const isPinned = ref(localStorage.getItem('nav-pinned') === 'true');
const isCollapsed = ref(localStorage.getItem('nav-pinned') !== 'true');

function togglePin() {
    isPinned.value = !isPinned.value;
    isCollapsed.value = !isPinned.value;
    localStorage.setItem('nav-pinned', String(isPinned.value));
}
</script>

<template>
    <div class="flex min-h-screen bg-bg-primary">
        <aside
            :class="[
                'fixed top-0 left-0 z-40 h-screen bg-white shadow-sm transition-[width] duration-300 ease-in-out',
                isCollapsed ? 'w-16' : 'w-64',
            ]"
            aria-label="Sidebar"
        >
            <nav class="mt-4 flex h-full flex-col">
                <!-- Header : logo + pin -->
                <div class="mx-2 mb-4 flex items-center justify-between pb-3">
                    <div
                        class="transition-[opacity,max-width] ease-in-out"
                        :class="
                            isCollapsed
                                ? 'max-w-0 opacity-0 duration-150'
                                : ' opacity-100 delay-150 duration-200'
                        "
                    >
                        <AppLogoIcon class="h-8 w-32 text-blue-dark" />
                    </div>

                    <button
                        @click="togglePin"
                        class="cursor-pointer rounded-lg p-2 transition-all duration-200 ease-in-out hover:scale-110 hover:bg-hover-nav"
                        :class="[
                            isCollapsed ? 'mx-auto' : '',
                            isPinned ? 'bg-hover-nav' : '',
                        ]"
                        :aria-label="
                            isPinned
                                ? 'Désépingler la navigation'
                                : 'Épingler la navigation'
                        "
                        :title="
                            isPinned
                                ? 'Désépingler la navigation'
                                : 'Épingler la navigation'
                        "
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-text-base transition-transform duration-300"
                            :class="isPinned ? 'rotate-0' : 'rotate-90'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <line x1="12" y1="17" x2="12" y2="22" />
                            <path
                                d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    <NavHeader :collapsed="isCollapsed" />
                </div>

                <div class="mt-auto mb-8">
                    <div class="px-2 pt-2 pb-4">
                        <Button
                            variant="danger"
                            size="sm"
                            :icon-only="isCollapsed"
                            :label="isCollapsed ? '' : 'Se déconnecter'"
                            :title="isCollapsed ? 'Se déconnecter' : undefined"
                            class="w-full"
                            @click="handleLogout"
                        >
                            <template #icon>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                                    />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                            </template>
                        </Button>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main content -->
        <main
            class="flex-1 transition-all duration-300 ease-in-out"
            :class="isCollapsed ? 'ml-16' : 'ml-64'"
        >
            <TopBar :title="title" />
            <slot />
        </main>
    </div>
</template>
