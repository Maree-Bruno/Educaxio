<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';
import type { PaginationLink } from '@/types';

const props = defineProps<{
    links: PaginationLink[];
    currentPage: number;
    lastPage: number;
}>();

const prevLink = computed(() => props.links[0]);
const nextLink = computed(() => props.links[props.links.length - 1]);
const pageLinks = computed(() => props.links.slice(1, -1));

function navigate(url: string | null) {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <div
        v-if="lastPage > 1"
        class="flex items-center justify-end gap-1"
        role="navigation"
        aria-label="Pagination"
    >
        <button
            :disabled="!prevLink.url"
            class="flex h-7 w-7 items-center justify-center rounded-2xl outline-1 -outline-offset-1 outline-blue transition-colors hover:bg-blue/10 disabled:cursor-not-allowed disabled:opacity-40"
            :aria-label="`Page précédente`"
            @click="navigate(prevLink.url)"
        >
            <ChevronDown
                :size="12"
                :stroke-width="1.5"
                class="rotate-90 text-text-base"
                aria-hidden="true"
            />
        </button>

        <!-- Mobile: compact page indicator -->
        <span class="sm:hidden px-2 text-xs font-medium text-text-base">
            {{ currentPage }} / {{ lastPage }}
        </span>

        <!-- Desktop: full page links -->
        <template v-for="link in pageLinks" :key="link.label">
            <button
                v-if="link.label !== '...'"
                :aria-current="link.active ? 'page' : undefined"
                :class="link.active
                    ? 'bg-blue text-white'
                    : 'text-text-base outline-1 -outline-offset-1 outline-blue hover:bg-blue/10'"
                class="hidden sm:flex h-7 w-7 items-center justify-center rounded-lg text-xs font-medium transition-colors"
                @click="navigate(link.url)"
            >
                {{ link.label }}
            </button>
            <span v-else class="hidden sm:flex h-7 w-7 items-center justify-center text-xs text-border-figma">
                …
            </span>
        </template>

        <button
            :disabled="!nextLink.url"
            class="flex h-7 w-7 items-center justify-center rounded-2xl outline-1 -outline-offset-1 outline-blue transition-colors hover:bg-blue/10 disabled:cursor-not-allowed disabled:opacity-40"
            :aria-label="`Page suivante`"
            @click="navigate(nextLink.url)"
        >
            <ChevronDown
                :size="12"
                :stroke-width="1.5"
                class="-rotate-90 text-text-base"
                aria-hidden="true"
            />
        </button>
    </div>
</template>
