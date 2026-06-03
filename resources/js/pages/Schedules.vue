<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ScheduleCellCard from '@/components/widgets/ScheduleCellCard.vue';
import ScheduleMobileRow from '@/components/widgets/ScheduleMobileRow.vue';
import ScheduleSlotModal from '@/components/widgets/ScheduleSlotModal.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import ScheduleIcon from '@/components/widgets/svg/Schedule.vue';
import { toMins, useCurrentSlot } from '@/composables/useCurrentSlot';
import { useHiddenIds } from '@/composables/useHiddenIds';
import { setPageTitle } from '@/composables/usePageTitle';
import { schedules as schedulesRoute } from '@/routes';
import { destroy as destroyScheduleEntry } from '@/routes/schedule-entries';
import { updateType } from '@/routes/schedule-slots';
import { useToasterStore } from '@/stores/toaster';
import type {
    AcademicYear,
    LessonOption,
    ScheduleEntry,
    School,
    SlotRow,
} from '@/types';

setPageTitle('Horaire hebdomadaire annuel');

const props = defineProps<{
    slots: SlotRow[];
    entries: Record<number, Record<number, ScheduleEntry>>;
    lessons: LessonOption[];
    schools: Pick<School, 'id' | 'name' | 'slug'>[];
    schedules: { id: number; school_id: number; school: string | null }[];
    academicYears: Pick<AcademicYear, 'id' | 'year' | 'is_current' | 'is_archived'>[];
    filters: { year?: string };
}>();

const toaster = useToasterStore();
const { hide: hideEntry, show: showEntry, isHidden: isEntryHidden } = useHiddenIds();

const DAY_NAMES = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven'];
const todayDow = new Date().getDay();
const lastRowIndex = computed(() => props.slots.length - 1);

const filterYear = ref<string | null>(props.filters.year ?? null);
const yearOptions = props.academicYears.map((y) => ({
    value: String(y.id),
    label: y.is_archived ? `${y.year} — archivée` : y.is_current ? `${y.year} — en cours` : y.year,
}));

watch(filterYear, () => {
    router.get(
        schedulesRoute.url(),
        { year: filterYear.value ?? undefined },
        { preserveState: true, replace: true },
    );
});

const selectedDay = ref(todayDow >= 1 && todayDow <= 5 ? todayDow : 1);

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
    modalState.value = {
        slot,
        dayOfWeek,
        dayLabel: DAY_NAMES[dayIndex],
        entry,
    };
}

function getEntry(position: number, day: number): ScheduleEntry | null {
    const entry = props.entries[position]?.[day] ?? null;

    return !entry || isEntryHidden(entry.id) ? null : entry;
}

function onDeleteEntry(entryId: number) {
    hideEntry(entryId);
    toaster.deletable(
        'Créneau supprimé',
        () => router.delete(destroyScheduleEntry.url({ scheduleEntry: entryId }), { preserveScroll: true }),
        () => showEntry(entryId),
    );
}

function toggleSlotType(row: SlotRow) {
    router.patch(
        updateType.url(),
        {
            id: row.id,
            type: row.type === 'lunch' ? 'slot' : 'lunch',
        },
        { preserveState: true },
    );
}

const { now, activeSlotIndex } = useCurrentSlot(props.slots);

const isViewingToday = computed(
    () => now.value !== null && selectedDay.value === now.value.getDay(),
);

const indicatorTop = computed((): number | null => {
    const i = activeSlotIndex.value;

    if (i === null || !now.value) {
        return null;
    }

    const slot = props.slots[i];
    const start = toMins(slot.start_time!);
    const end = toMins(slot.end_time!);
    const mins = now.value.getHours() * 60 + now.value.getMinutes();

    return 40 + i * 80 + ((mins - start) / (end - start)) * 80;
});
</script>

<template>
    <div class="mb-6 rounded-2xl bg-white shadow-sm outline -outline-offset-1 outline-neutral-300/10">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-neutral-300/10 px-6 py-4">
            <div>
                <p class="text-sm font-bold text-text-base">Sélection de l'année académique</p>
                <p class="mt-0.5 text-xs text-stone-400">Cliquer sur une cellule pour assigner un cours · Cliquer sur
                    une étiquette (1ère heure) pour la basculer en pause</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <SelectField
                id="filter-year"
                v-model="filterYear"
                label="Année académique"
                :options="yearOptions"
                class="w-48"
            />
        </div>
    </div>

    <div
        v-if="slots.length === 0"
        class="flex flex-col items-center justify-center gap-3 rounded-3xl bg-white py-20"
    >
        <ScheduleIcon :size="32" :stroke-width="1.5" class="text-stone-300" />
        <div class="text-center">
            <p class="font-bold text-text-base">Aucun créneau horaire configuré</p>
            <p class="mt-1 text-xs text-stone-400">Les créneaux sont définis dans les paramètres de l'établissement.</p>
        </div>
    </div>

    <div v-else>
        <div class="md:hidden">
            <div class="mb-4 flex gap-1.5">
                <button
                    v-for="(day, i) in DAY_NAMES"
                    :key="day"
                    type="button"
                    :aria-pressed="selectedDay === i + 1"
                    class="flex-1 rounded-xl py-2 text-sm font-bold transition-colors"
                    :class="selectedDay === i + 1 ? 'bg-blue text-white' : 'bg-white text-text-base hover:bg-blue/10'"
                    @click="selectedDay = i + 1"
                >
                    {{ day }}
                </button>
            </div>

            <div class="overflow-hidden rounded-3xl bg-bg-primary">
                <template v-for="(row, rowIndex) in slots" :key="row.position">
                    <ScheduleMobileRow
                        :row="row"
                        :entry="getEntry(row.position, selectedDay)"
                        :is-last="rowIndex === lastRowIndex"
                        :is-active="isViewingToday && activeSlotIndex === rowIndex"
                        @click="openCell(row, selectedDay - 1)"
                    />
                </template>
            </div>
        </div>

        <div
            role="grid"
            aria-label="Horaire hebdomadaire"
            class="relative hidden overflow-hidden rounded-3xl bg-bg-primary md:block"
        >
            <div role="row" class="grid" style="grid-template-columns: 80px repeat(5, 1fr)">
                <div aria-hidden="true" class="self-stretch rounded-tl-3xl bg-blue"></div>
                <div
                    v-for="(day, i) in DAY_NAMES"
                    :key="day"
                    role="columnheader"
                    class="flex h-10 items-center justify-center border-r border-b border-zinc-400/10 last:rounded-tr-3xl"
                    :class="todayDow === i + 1 && 'bg-zinc-400/10'"
                >
                    <span
                        class="text-base leading-4 font-extrabold"
                        :class="todayDow === i + 1 ? 'text-blue' : 'text-text-base'"
                    >
                        {{ day }}
                    </span>
                </div>
            </div>

            <template v-for="(row, rowIndex) in slots" :key="row.position">
                <div
                    v-if="row.type === 'slot'"
                    role="row"
                    class="grid h-20"
                    style="grid-template-columns: 80px repeat(5, 1fr)"
                >
                    <button
                        type="button"
                        role="rowheader"
                        class="group flex cursor-pointer flex-col items-center justify-center gap-0.5 border-r border-b border-zinc-400/10 bg-white transition-colors hover:bg-blue/5"
                        :class="rowIndex === lastRowIndex && 'rounded-bl-3xl'"
                        :aria-label="`${row.label} — basculer en pause`"
                        title="Cliquer pour basculer en pause"
                        @click="toggleSlotType(row)"
                    >
                        <span class="text-xs leading-4 font-extrabold text-border-figma" aria-hidden="true">{{ row.label }}</span>
                        <span class="text-[8px] text-stone-300 opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true">pause</span>
                    </button>

                    <button
                        v-for="(day, colIndex) in DAY_NAMES"
                        :key="day"
                        type="button"
                        role="gridcell"
                        class="block cursor-pointer border-r border-b border-zinc-400/10 p-1 last:border-r-0"
                        :aria-label="`${day}, ${row.label}`"
                        @click="openCell(row, colIndex)"
                    >
                        <ScheduleCellCard
                            :entry="getEntry(row.position, colIndex + 1)"
                            :active="activeSlotIndex === rowIndex && now?.getDay() === colIndex + 1"
                        />
                    </button>
                </div>

                <div
                    v-else
                    role="row"
                    class="grid h-20"
                    style="grid-template-columns: 80px repeat(5, 1fr)"
                >
                    <button
                        type="button"
                        role="rowheader"
                        class="cursor-pointer border-r border-b border-zinc-400/10 bg-white transition-colors hover:bg-blue/5"
                        :class="rowIndex === lastRowIndex && 'rounded-bl-3xl'"
                        :aria-label="`${row.label} — basculer en cours`"
                        @click="toggleSlotType(row)"
                    ></button>
                    <div role="gridcell" aria-colspan="5" class="col-span-5 p-1">
                        <div class="flex h-full items-center justify-center rounded-lg bg-white">
                            <span class="text-xs font-bold tracking-wider text-black uppercase">Pause</span>
                        </div>
                    </div>
                </div>
            </template>

            <div
                v-if="indicatorTop !== null"
                class="pointer-events-none absolute inset-x-0 z-10 flex items-center"
                :style="{ top: indicatorTop + 'px' }"
            >
                <div
                    class="h-2.5 w-2.5 shrink-0 rounded-full bg-pink"
                    style="margin-left: 74px"
                />
                <div class="h-px flex-1 bg-pink opacity-80" />
            </div>
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
        @delete-entry="onDeleteEntry"
    />
</template>
