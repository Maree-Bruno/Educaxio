<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';

defineProps<{
    items: { label: string; href?: string }[];
}>();
</script>

<template>
    <nav
        class="mb-6 flex flex-wrap items-center gap-3"
        aria-label="Fil d'ariane"
    >
        <h3 class="sr-only">Fil d'ariane</h3>
        <template v-for="(item, index) in items" :key="index">
            <ChevronDown
                v-if="index > 0"
                :size="16"
                :stroke-width="2"
                class="shrink-0 -rotate-90 text-text-base"
                aria-hidden="true"
            />
            <Link
                v-if="item.href"
                :href="item.href"
                class="text-xs font-bold text-text-base transition-colors hover:text-blue sm:text-base"
            >
                {{ item.label }}
            </Link>
            <span v-else class="text-xs font-bold text-blue sm:text-base">
                {{ item.label }}
            </span>
        </template>
    </nav>
</template>
