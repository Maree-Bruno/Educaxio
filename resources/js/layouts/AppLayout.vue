<script setup lang="ts">
import AppHeader from '@/components/widgets/nav/AppHeader.vue';
import TopBar from '@/components/widgets/nav/TopBar.vue';
import { useNavigationStore } from '@/stores/navigation';

defineOptions({ inheritAttrs: false });

const nav = useNavigationStore();
</script>

<template>
    <div class="flex min-h-screen bg-bg-primary">
        <AppHeader />

        <main
            class="flex-1 transition-all duration-300 ease-in-out"
            :class="nav.isCollapsed ? 'ml-16' : 'ml-64'"
        >
            <TopBar />
            <slot />
        </main>
    </div>
</template>