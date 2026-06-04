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

const emit = defineEmits<{
    (e: 'update:modelValue', page: number): void;
}>();

const prevLink = computed(() => props.links[0]);
const nextLink = computed(() => props.links[props.links.length - 1]);
const pageLinks = computed(() => props.links.slice(1, -1));

function urlForPage(page: number): string | null {
    const sample = [...pageLinks.value, prevLink.value, nextLink.value].find(l => l.url)?.url ?? null;
    if (!sample) return null;
    return sample.replace(/([?&][^=]+=)\d+/, `$1${page}`);
}

function navigate(url: string | null, page: number) {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    } else {
        emit('update:modelValue', page);
    }
}

function jumpToPage(page: number) {
    navigate(urlForPage(page), page);
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
            :disabled="currentPage === 1"
            class="flex h-7 w-7 items-center justify-center rounded-lg outline-1 -outline-offset-1 outline-blue transition-colors hover:bg-blue/10 disabled:cursor-not-allowed disabled:opacity-40"
            aria-label="Page précédente"
            @click="navigate(prevLink.url, currentPage - 1)"
        >
            <ChevronDown :size="12" :stroke-width="1.5" class="rotate-90 text-text-base" aria-hidden="true" />
        </button>

        <div class="relative sm:hidden">
            <label for="pagination-select-mobile" class="sr-only">Aller à la page</label>
            <select
                id="pagination-select-mobile"
                class="h-7 appearance-none cursor-pointer rounded-lg bg-white pl-2 pr-6 text-xs font-medium text-text-base outline-1 -outline-offset-1 outline-blue transition-colors hover:bg-blue/10"
                :value="currentPage"
                @change="jumpToPage(Number(($event.target as HTMLSelectElement).value))"
            >
                <option v-for="n in lastPage" :key="n" :value="n">{{ n }} / {{ lastPage }}</option>
            </select>
            <ChevronDown :size="10" :stroke-width="2" class="pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 text-text-base" aria-hidden="true" />
        </div>

        <template v-for="link in pageLinks" :key="link.label">
            <button
                v-if="link.label !== '...'"
                :aria-current="link.active ? 'page' : undefined"
                :class="link.active ? 'bg-blue text-white' : 'text-text-base outline-1 -outline-offset-1 outline-blue hover:bg-blue/10'"
                class="hidden sm:flex h-7 w-7 items-center justify-center rounded-lg text-xs font-medium transition-colors"
                @click="navigate(link.url, Number(link.label))"
            >
                {{ link.label }}
            </button>
            <span v-else class="hidden sm:flex h-7 w-7 items-center justify-center text-xs text-border-figma">…</span>
        </template>

        <button
            :disabled="currentPage === lastPage"
            class="flex h-7 w-7 items-center justify-center rounded-lg outline-1 -outline-offset-1 outline-blue transition-colors hover:bg-blue/10 disabled:cursor-not-allowed disabled:opacity-40"
            aria-label="Page suivante"
            @click="navigate(nextLink.url, currentPage + 1)"
        >
            <ChevronDown :size="12" :stroke-width="1.5" class="-rotate-90 text-text-base" aria-hidden="true" />
        </button>

        <div class="relative ml-1 hidden sm:block">
            <label for="pagination-select-desktop" class="sr-only">Aller à la page</label>
            <select
                id="pagination-select-desktop"
                class="h-7 appearance-none cursor-pointer rounded-lg bg-white pl-2 pr-6 text-xs font-medium text-text-base outline-1 -outline-offset-1 outline-blue transition-colors hover:bg-blue/10"
                :value="currentPage"
                @change="jumpToPage(Number(($event.target as HTMLSelectElement).value))"
            >
                <option v-for="n in lastPage" :key="n" :value="n">{{ n }} / {{ lastPage }}</option>
            </select>
            <ChevronDown :size="10" :stroke-width="2" class="pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 text-text-base" aria-hidden="true" />
        </div>
    </div>
</template>
