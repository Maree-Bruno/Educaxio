<script setup lang="ts">
import { computed } from 'vue';

interface Stats { students: number; teachers: number; pending: number; lessons: number }

const props = defineProps<{ stats: Stats }>();

const cards = computed(() => [
    { label: 'Élèves',               value: props.stats.students, highlight: false },
    { label: 'Professeurs',          value: props.stats.teachers, highlight: false },
    { label: "En attente d'adhésion", value: props.stats.pending,  highlight: props.stats.pending > 0 },
    { label: 'Cours',                value: props.stats.lessons,  highlight: false },
]);
</script>

<template>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div
            v-for="card in cards"
            :key="card.label"
            class="flex flex-col gap-1 rounded-2xl bg-white px-5 py-4 shadow-sm outline -outline-offset-1 outline-neutral-300/10"
        >
            <span class="text-3xl font-extrabold" :class="card.highlight ? 'text-orange' : 'text-text-base'">{{ card.value }}</span>
            <span class="text-xs font-bold tracking-wider text-border-figma uppercase">{{ card.label }}</span>
        </div>
    </div>
</template>
