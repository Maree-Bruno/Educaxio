<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Badge from '@/components/widgets/Badge.vue';
import DateField from '@/components/widgets/DateField.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { User } from '@/types';

interface Entry {
    id: number;
    subject: string;
    group: string;
    groupSlug: string;
    school: string;
    room: string | null;
}

interface Slot {
    id: number;
    position: number;
    label: string;
    type: 'slot' | 'lunch';
    entry: Entry | null;
}

interface Group {
    id: number;
    grade: string;
    name: string;
    slug: string;
    school: string;
    subjects: string[];
}

const { user, selectedDate } = defineProps<{
    slots: Slot[];
    groups: Group[];
    date: string;
    selectedDate: string;
    user: User;
}>();

setPageTitle(`Bonjour ${user.name}`);

const today = new Date().toISOString().slice(0, 10);
const isEditable = selectedDate <= today;

function changeDate(value: string) {
    router.get('/dashboard', { date: value }, { preserveState: false });
}

</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-bold capitalize text-text-base">{{ date }}</h2>
            <DateField
                :model-value="selectedDate"
                @update:model-value="changeDate"
            />
        </div>
        <div class="flex flex-col gap-6 xl:flex-row xl:items-start">
            <div class="min-w-0 flex-1 overflow-hidden rounded-2xl">
                <div class="border-b border-zinc-400/10 bg-white px-6 py-5">
                    <h3 class="text-base font-bold text-stone-900">
                        Horaire du jour
                    </h3>
                </div>
                <EmptyState
                    v-if="slots.length === 0"
                    message="Pas de cours aujourd'hui"
                    class="bg-white"
                />
                <div v-else>
                    <template v-for="(slot, index) in slots" :key="slot.id">
                        <div
                            v-if="slot.type === 'lunch'"
                            class="flex items-center justify-center border-b border-zinc-400/10 bg-white py-3"
                            :class="index === slots.length - 1 && 'border-b-0'"
                        >
                            <span
                                class="text-xs font-bold tracking-wider text-zinc-400 uppercase"
                                >Pause</span
                            >
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-3 border-b border-zinc-400/10 bg-white px-6 py-4"
                            :class="index === slots.length - 1 && 'border-b-0'"
                        >
                            <span
                                class="w-20 shrink-0 text-xs font-extrabold text-border-figma"
                                >{{ slot.label }}</span
                            >
                            <template v-if="slot.entry">
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-extrabold text-text-base"
                                    >
                                        {{ slot.entry.group }} ·
                                        {{ slot.entry.subject }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-border-figma"
                                    >
                                        {{ slot.entry.room ?? '–' }} ·
                                        {{ slot.entry.school }}
                                    </p>
                                </div>
                                <a
                                    v-if="isEditable"
                                    :href="`/attendances?entry=${slot.entry.id}`"
                                    class="shrink-0 text-xs font-bold text-blue hover:underline"
                                >
                                    Présences
                                </a>
                            </template>
                            <p v-else class="flex-1 text-sm text-zinc-300">
                                Libre
                            </p>
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex w-full shrink-0 flex-col gap-6 xl:w-72">
                <div class="overflow-hidden rounded-2xl">
                    <div
                        class="flex items-center justify-between border-b border-neutral-300/10 bg-white px-6 py-5"
                    >
                        <h3 class="text-base font-bold text-stone-900">
                            Devoirs & Interros
                        </h3>
                        <button
                            type="button"
                            disabled
                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-orange text-sm font-bold text-white opacity-40"
                        >
                            +
                        </button>
                    </div>
                    <div class="bg-white px-6 py-8 text-center">
                        <p class="text-xs text-stone-400 italic">
                            Fonctionnalité à venir
                        </p>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl">
                    <div
                        class="border-b border-neutral-300/10 bg-white px-6 py-5"
                    >
                        <h3 class="text-base font-bold text-stone-900">
                            Mes classes
                        </h3>
                    </div>

                    <EmptyState
                        v-if="groups.length === 0"
                        message="Aucune classe assignée"
                        class="bg-white"
                    />

                    <ul v-else class="divide-y divide-neutral-100 bg-white">
                        <li
                            v-for="group in groups"
                            :key="group.id"
                            class="flex items-center justify-between gap-3 px-6 py-4"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs text-stone-400">
                                    {{ group.school }}
                                </p>
                                <p class="truncate text-xs text-border-figma">
                                    {{ group.subjects.join(', ') }}
                                </p>
                            </div>
                            <Badge :href="`/classlist/${group.slug}`">
                                {{ group.grade }}{{ group.name }}
                            </Badge>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
