<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { usePageTitle } from '@/composables/usePageTitle';
import { useNavigationStore } from '@/stores/navigation';
import AppHeader from '@/components/widgets/nav/AppHeader.vue';
import TopBar from '@/components/widgets/nav/TopBar.vue';

const title = usePageTitle();

defineOptions({ inheritAttrs: false });

const nav = useNavigationStore();
</script>

<template>
    <div class="flex min-h-screen bg-bg-primary">
        <AppHeader />

        <main
            class="min-w-0 flex-1 transition-all duration-300 ease-in-out"
            :class="nav.isCollapsed ? 'md:ml-16' : 'md:ml-64'"
        >
            <TopBar />
            <section class="flex flex-col gap-4 lg:p-section p-2">
                <Head :title="title" />
                <h2 class="sr-only">{{title}}</h2>
                <slot />
            </section>
        </main>
    </div>
</template>
