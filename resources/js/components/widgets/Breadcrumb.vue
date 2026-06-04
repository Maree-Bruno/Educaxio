<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';

const props = defineProps<{
    items: { label: string; href?: string }[];
}>();

const parentItem = computed(() =>
    [...props.items].reverse().find((item) => item.href) ?? null,
);
</script>

<template>
    <nav class="mb-6" aria-label="Fil d'ariane">
        <h3 class="sr-only">Fil d'ariane</h3>

        <div class="flex items-center gap-1 sm:hidden">
            <template v-if="parentItem">
                <ChevronDown
                    :size="14"
                    :stroke-width="2.5"
                    class="shrink-0 rotate-90 text-text-base"
                    aria-hidden="true"
                />
                <Link
                    :href="parentItem.href!"
                    class="text-xs font-bold text-text-base transition-colors hover:text-blue"
                >
                    {{ parentItem.label }}
                </Link>
            </template>
        </div>

        <div class="hidden sm:flex flex-wrap items-center gap-3">
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
                    class="text-base font-bold text-text-base transition-colors hover:text-blue"
                >
                    {{ item.label }}
                </Link>
                <span v-else class="text-base font-bold text-blue">
                    {{ item.label }}
                </span>
            </template>
        </div>
    </nav>
</template>
