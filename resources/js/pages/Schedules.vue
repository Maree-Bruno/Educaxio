<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Button from '@/components/widgets/Button.vue';
import ScheduleSlotModal from '@/components/widgets/ScheduleSlotModal.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { AcademicYear, LessonOption, ScheduleEntry, School, SlotRow } from '@/types';

setPageTitle('Horaires');

const props = defineProps<{
    slots: SlotRow[];
    entries: Record<number, Record<number, ScheduleEntry>>;
    lessons: LessonOption[];
    schools: Pick<School, 'id' | 'name' | 'slug'>[];
    schedules: { id: number; school_id: number; school: string | null }[];
    academicYears: Pick<AcademicYear, 'id' | 'year'>[];
    filters: { year?: string };
}>();

const DAY_NAMES = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven'];
const todayDow = new Date().getDay(); // 0=dim, 1=lun … 5=ven, 6=sam
const lastRowIndex = computed(() => props.slots.length - 1);

// Year filter
const filterYear = ref<string | null>(props.filters.year ?? null);
const yearOptions = props.academicYears.map((y) => ({ value: String(y.id), label: y.year }));

watch(filterYear, () => {
    router.get('/schedules', { year: filterYear.value ?? undefined }, { preserveState: true, replace: true });
});

// Mobile: selected day (1=lun … 5=ven), defaults to today or Monday
const selectedDay = ref(todayDow >= 1 && todayDow <= 5 ? todayDow : 1);

// Modal
interface ModalState {
    slot: SlotRow;
    dayOfWeek: number;
    dayLabel: string;
    entry: ScheduleEntry | null;
}

const modalState = ref<ModalState | null>(null);

function openCell(slot: SlotRow, dayIndex: number) {
    const dayOfWeek = dayIndex + 1;
    const entry = props.entries[slot.position]?.[dayOfWeek] ?? null;
    modalState.value = { slot, dayOfWeek, dayLabel: DAY_NAMES[dayIndex], entry };
}

function toggleSlotType(row: SlotRow) {
    router.patch('/schedule-slots-type', {
        id: row.id,
        type: row.type === 'lunch' ? 'slot' : 'lunch',
    }, { preserveState: true });
}
</script>

<template>
    <!-- Year filter + actions -->
    <div class="mb-6 flex flex-wrap items-end gap-4">
        <SelectField
            id="filter-year"
            v-model="filterYear"
            label="Année scolaire"
            :options="yearOptions"
            class="w-48"
        />
        <div class="ml-auto flex items-center gap-4">
            <Button variant="primary" size="sm">Importer</Button>
            <Button variant="primary" size="sm">Exporter</Button>
        </div>
    </div>

    <!-- Empty state -->
    <div v-if="slots.length === 0" class="flex items-center justify-center rounded-3xl bg-white py-20">
        <p class="font-bold text-text-base">Aucun horaire configuré</p>
    </div>

    <div v-else>

        <!-- ── Mobile / tablette portrait : vue jour par jour ──────────────── -->
        <div class="md:hidden">

            <!-- Sélecteur de jour -->
            <div class="mb-4 flex gap-1.5">
                <button
                    v-for="(day, i) in DAY_NAMES"
                    :key="day"
                    class="flex-1 rounded-xl py-2 text-sm font-bold transition-colors"
                    :class="selectedDay === i + 1
                        ? 'bg-blue text-white'
                        : 'bg-white text-text-base hover:bg-blue/10'"
                    @click="selectedDay = i + 1"
                >
                    {{ day }}
                </button>
            </div>

            <!-- Liste des créneaux -->
            <div class="overflow-hidden rounded-3xl bg-bg-primary">
                <template v-for="(row, rowIndex) in slots" :key="row.position">

                    <!-- Pause -->
                    <div
                        v-if="row.type === 'lunch'"
                        class="flex items-center justify-center border-b border-zinc-400/10 bg-white py-3"
                        :class="rowIndex === lastRowIndex && 'border-b-0'"
                    >
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-400">Pause</span>
                    </div>

                    <!-- Créneau cours -->
                    <div
                        v-else
                        class="flex cursor-pointer items-center gap-3 border-b border-zinc-400/10 bg-white px-4 py-3 transition-colors hover:bg-blue/5"
                        :class="rowIndex === lastRowIndex && 'border-b-0'"
                        @click="openCell(row, selectedDay - 1)"
                    >
                        <span class="w-20 shrink-0 text-xs font-extrabold text-border-figma">{{ row.label }}</span>
                        <template v-if="entries[row.position]?.[selectedDay]">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-extrabold text-black">
                                    {{ entries[row.position][selectedDay].grade }}
                                    · {{ entries[row.position][selectedDay].subject }}
                                </p>
                                <p class="truncate text-xs text-border-figma">
                                    {{ entries[row.position][selectedDay].room ?? '–' }}
                                    · {{ entries[row.position][selectedDay].school }}
                                </p>
                            </div>
                        </template>
                        <p v-else class="flex-1 text-sm text-zinc-300">Libre</p>
                    </div>

                </template>
            </div>
        </div>

        <!-- ── Desktop / tablette paysage : grille semaine complète ────────── -->
        <div class="hidden overflow-hidden rounded-3xl bg-bg-primary md:block">

            <!-- Header : jours -->
            <div class="grid" style="grid-template-columns: 80px repeat(5, 1fr)">
                <div class="self-stretch rounded-tl-3xl bg-blue"></div>
                <div
                    v-for="(day, i) in DAY_NAMES"
                    :key="day"
                    class="flex h-10 items-center justify-center border-b border-r border-zinc-400/10 last:rounded-tr-3xl"
                    :class="todayDow >= 1 && todayDow <= 5 && todayDow === i + 1 && 'bg-zinc-400/10'"
                >
                    <span
                        class="text-base font-extrabold leading-4"
                        :class="todayDow >= 1 && todayDow <= 5 && todayDow === i + 1 ? 'text-blue' : 'text-text-base'"
                    >
                        {{ day }}
                    </span>
                </div>
            </div>

            <!-- Corps -->
            <template v-for="(row, rowIndex) in slots" :key="row.position">

                <!-- Créneau cours -->
                <div
                    v-if="row.type === 'slot'"
                    class="grid h-20"
                    style="grid-template-columns: 80px repeat(5, 1fr)"
                >
                    <div
                        class="group flex cursor-pointer items-center justify-center border-b border-r border-zinc-400/10 bg-white transition-colors hover:bg-blue/5"
                        :class="rowIndex === lastRowIndex && 'rounded-bl-3xl'"
                        title="Marquer comme pause"
                        @click="toggleSlotType(row)"
                    >
                        <span class="text-xs font-extrabold leading-4 text-border-figma">{{ row.label }}</span>
                    </div>

                    <div
                        v-for="(day, colIndex) in DAY_NAMES"
                        :key="day"
                        class="cursor-pointer border-b border-r border-zinc-400/10 p-1 last:border-r-0"
                        @click="openCell(row, colIndex)"
                    >
                        <div
                            v-if="entries[row.position]?.[colIndex + 1]"
                            class="flex h-full flex-col justify-between rounded-lg bg-white p-2 transition-shadow hover:shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-1">
                                <span class="shrink-0 text-xs font-extrabold leading-4 text-black">
                                    {{ entries[row.position][colIndex + 1].grade }}
                                </span>
                                <span class="line-clamp-1 text-right text-xs font-extrabold leading-4 text-black">
                                    {{ entries[row.position][colIndex + 1].subject }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-1">
                                <span class="shrink-0 text-xs font-extrabold leading-4 text-black">
                                    {{ entries[row.position][colIndex + 1].room ?? '–' }}
                                </span>
                                <span class="line-clamp-1 text-right text-xs font-extrabold leading-4 text-black">
                                    {{ entries[row.position][colIndex + 1].school }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="h-full rounded-lg bg-white transition-colors hover:bg-blue/5" />
                    </div>
                </div>

                <!-- Pause -->
                <div v-else class="grid h-20" style="grid-template-columns: 80px repeat(5, 1fr)">
                    <div
                        class="cursor-pointer border-b border-r border-zinc-400/10 bg-white transition-colors hover:bg-blue/5"
                        :class="rowIndex === lastRowIndex && 'rounded-bl-3xl'"
                        title="Marquer comme cours"
                        @click="toggleSlotType(row)"
                    ></div>
                    <div class="col-span-5 p-1">
                        <div class="flex h-full items-center justify-center rounded-lg bg-white">
                            <span class="text-xs font-bold uppercase tracking-wider text-black">Pause</span>
                        </div>
                    </div>
                </div>

            </template>
        </div>

    </div>

    <ScheduleSlotModal
        :open="modalState !== null"
        :schedule-slot="modalState?.slot ?? null"
        :day-of-week="modalState?.dayOfWeek ?? 1"
        :day-label="modalState?.dayLabel ?? ''"
        :entry="modalState?.entry ?? null"
        :lessons="lessons"
        :schedules="schedules"
        :slots="slots"
        :entries="entries"
        @close="modalState = null"
    />
</template>