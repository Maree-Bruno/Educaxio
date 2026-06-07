<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import Button from '@/components/widgets/Button.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import type { SelectOption } from '@/components/widgets/SelectField.vue';
import { resolveSubjectLabel } from '@/composables/useSubjectLabel';
import { store } from '@/routes/schedule-entries';
import { useToasterStore } from '@/stores/toaster';
import type { LessonOption, ScheduleEntry, SlotRow } from '@/types';

const props = defineProps<{
    open: boolean;
    scheduleSlot: SlotRow | null;
    dayOfWeek: number;
    dayLabel: string;
    entry: ScheduleEntry | null;
    lessons: LessonOption[];
    schedules: { id: number; school_id: number }[];
    slots: SlotRow[];
    entries: Record<number, Record<number, ScheduleEntry>>;
}>();

const emit = defineEmits<{
    close: [];
    'delete-entry': [entryId: number];
}>();

const dialogRef = ref<HTMLDialogElement | null>(null);

const DAY_OPTIONS = [
    { value: 1, label: 'Lundi' },
    { value: 2, label: 'Mardi' },
    { value: 3, label: 'Mercredi' },
    { value: 4, label: 'Jeudi' },
    { value: 5, label: 'Vendredi' },
];

const filterEcole      = ref<number | null>(null);
const filterClasse     = ref<number | null>(null);
const selectedLesson   = ref<number | null>(null);
const selectedDay      = ref<number>(1);
const selectedSlotId   = ref<number | null>(null);
const form             = useForm({ classroom: '' });
const confirmingDelete = ref(false);
const syncing          = ref(false);
const toaster          = useToasterStore();

const selectedDayLabel = computed(() => DAY_OPTIONS.find((d) => d.value === selectedDay.value)?.label ?? '');
const selectedSlot     = computed(() => props.slots.find((s) => s.id === selectedSlotId.value) ?? null);
const slotOptions      = computed(() => props.slots.filter((s) => s.type === 'slot'));
const isOriginalSlotDay = computed(
    () => selectedSlotId.value === props.scheduleSlot?.id && selectedDay.value === props.dayOfWeek,
);
const slotSelectOptions = computed<SelectOption[]>(() =>
    slotOptions.value
        .filter((s) => {
            const occupied = props.entries?.[s.position]?.[selectedDay.value];
            return !occupied || (s.id === props.scheduleSlot?.id && isOriginalSlotDay.value);
        })
        .map((s) => ({ value: s.id, label: s.label ?? String(s.id) })),
);
const currentEntry     = computed<ScheduleEntry | null>(() => {
    const slot = selectedSlot.value;

    if (!slot) {
return null;
}

    return props.entries?.[slot.position]?.[selectedDay.value] ?? null;
});

const ecoleOptions = computed(() => {
    const seen = new Set<number>();

    return props.lessons
        .filter((l) => {
            if (seen.has(l.group.school_id)) {
return false;
}

            seen.add(l.group.school_id);

            return true;
        })
        .map((l) => ({ value: l.group.school_id, label: l.group.school.name }));
});

const classeOptions = computed(() => {
    const filtered = filterEcole.value
        ? props.lessons.filter((l) => l.group.school_id === filterEcole.value)
        : props.lessons;
    const seen = new Set<number>();

    return filtered
        .filter((l) => {
            if (seen.has(l.group_id)) {
return false;
}

            seen.add(l.group_id);

            return true;
        })
        .map((l) => ({
            value: l.group_id,
            label: `${l.group.grade}${l.group.name}`,
        }));
});

const coursOptions = computed(() =>
    (filterClasse.value
        ? props.lessons.filter((l) => l.group_id === filterClasse.value)
        : []
    ).map((l) => ({ value: l.id, label: resolveSubjectLabel(l.subject.name, l.lm_level) })),
);

watch(slotSelectOptions, (options) => {
    if (syncing.value) return;
    if (selectedSlotId.value !== null && !options.some((o) => o.value === selectedSlotId.value)) {
        selectedSlotId.value = null;
    }
});

watch(filterEcole, () => {
    if (syncing.value) {
return;
}

    filterClasse.value = null;
    selectedLesson.value = null;
});
watch(filterClasse, () => {
    if (syncing.value) {
return;
}

    selectedLesson.value = null;
});

async function syncFormFromEntry(entry: ScheduleEntry | null) {
    form.classroom = entry?.room ?? '';

    if (entry) {
        const lesson = props.lessons.find((l) => l.id === entry.lesson_id);
        filterEcole.value = lesson?.group.school_id ?? null;
        filterClasse.value = lesson?.group_id ?? null;
        await nextTick();
        selectedLesson.value = entry.lesson_id;
    } else {
        filterEcole.value = null;
        filterClasse.value = null;
        selectedLesson.value = null;
    }
}

async function syncFromEntry() {
    syncing.value = true;
    confirmingDelete.value = false;
    selectedDay.value    = props.dayOfWeek;
    selectedSlotId.value = props.scheduleSlot?.id ?? null;
    await nextTick();
    await syncFormFromEntry(currentEntry.value);
    syncing.value = false;
}

watch(currentEntry, async (entry) => {
    if (syncing.value) {
return;
}

    confirmingDelete.value = false;

    if (entry) {
        syncing.value = true;
        await syncFormFromEntry(entry);
        syncing.value = false;
    }
});

onMounted(() => {
    if (props.open) {
dialogRef.value?.showModal();
}
});

watch(
    () => props.open,
    async (isOpen) => {
        if (!dialogRef.value) {
return;
}

        if (isOpen) {
            await syncFromEntry();
            dialogRef.value.showModal();
        } else {
            dialogRef.value.close();
        }
    },
);

function onBackdropClick(event: MouseEvent) {
    if (event.target === dialogRef.value) {
emit('close');
}
}

function onCancel(event: Event) {
    event.preventDefault();
    emit('close');
}

function save() {
    if (!selectedSlot.value || !selectedLesson.value) {
return;
}

    const lesson = props.lessons.find((l) => l.id === selectedLesson.value);
    const schedule = props.schedules.find((s) => s.school_id === lesson?.group.school_id);

    const originalEntry = props.entry;
    const isMoving = !!originalEntry && (
        selectedSlotId.value !== props.scheduleSlot?.id ||
        selectedDay.value !== props.dayOfWeek
    );

    form.transform((data) => ({
        schedule_id:     schedule?.id,
        lesson_id:       selectedLesson.value,
        position:        selectedSlot.value!.position,
        day_of_week:     selectedDay.value,
        classroom:       data.classroom || null,
        delete_entry_id: isMoving ? originalEntry!.id : null,
    })).post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            toaster.success(isMoving ? 'Créneau déplacé' : 'Créneau enregistré');
        },
    });
}

function deleteEntry() {
    const entry = currentEntry.value;

    if (!entry) {
        return;
    }

    emit('close');
    emit('delete-entry', entry.id);
}
</script>

<template>
    <dialog
        ref="dialogRef"
        class="w-[90vw] max-w-137.5 overflow-hidden rounded-2xl bg-bg-primary p-4 sm:p-6"
        @click="onBackdropClick"
        @cancel="onCancel"
    >
        <div class="flex flex-col gap-4 rounded-xl bg-bg-primary p-3">
            <div>
                <h2 class="text-2xl font-bold text-black">
                    {{
                        currentEntry
                            ? 'Modifier le créneau'
                            : 'Ajouter un créneau horaire'
                    }}
                </h2>
                <p class="mt-1 text-sm font-bold text-border-figma">
                    {{ selectedDayLabel }} · {{ selectedSlot?.label ?? scheduleSlot?.label }}
                </p>
            </div>

            <div class="flex flex-col gap-5">
                <div class="flex gap-3">
                    <SelectField
                        id="slot-jour"
                        class="flex-1"
                        label="Jour"
                        :options="DAY_OPTIONS"
                        :model-value="selectedDay"
                        @update:model-value="selectedDay = ($event as number) ?? 1"
                    />
                    <SelectField
                        id="slot-heure"
                        class="flex-1"
                        label="Heure"
                        :options="slotSelectOptions"
                        :model-value="selectedSlotId"
                        @update:model-value="selectedSlotId = $event as number | null"
                    />
                </div>

                <SelectField
                    id="slot-ecole"
                    label="École"
                    placeholder="Choisir une école"
                    :options="ecoleOptions"
                    :model-value="filterEcole"
                    @update:model-value="filterEcole = $event as number | null"
                />

                <SelectField
                    id="slot-classe"
                    label="Classe"
                    placeholder="Choisir une classe"
                    :options="classeOptions"
                    :model-value="filterClasse"
                    :disabled="!filterEcole"
                    @update:model-value="filterClasse = $event as number | null"
                />

                <SelectField
                    id="slot-cours"
                    label="Cours"
                    placeholder="Choisir un cours"
                    :options="coursOptions"
                    :model-value="selectedLesson"
                    :disabled="!filterClasse"
                    @update:model-value="selectedLesson = $event as number | null"
                />

                <div class="flex flex-col gap-2">
                    <label for="slot-local" class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">Local</label>
                    <input
                        id="slot-local"
                        v-model="form.classroom"
                        type="text"
                        placeholder="Ex : 302"
                        class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline outline-1 -outline-offset-1 outline-border-figma placeholder:font-normal placeholder:text-border-figma"
                    />
                </div>

                <div class="flex items-center gap-5">
                    <Button
                        variant="primary"
                        size="md"
                        class="flex-1"
                        :disabled="!selectedLesson || !selectedSlotId"
                        :loading="form.processing"
                        @click="save"
                    >
                        Enregistrer
                    </Button>
                    <Button variant="danger" size="md" class="flex-1" @click="emit('close')">
                        Annuler
                    </Button>
                </div>

                <Button
                    v-if="currentEntry && !confirmingDelete"
                    variant="danger"
                    size="sm"
                    class="w-full"
                    @click="confirmingDelete = true"
                >
                    Supprimer le créneau
                </Button>

                <div v-if="currentEntry && confirmingDelete" class="flex gap-3">
                    <Button variant="danger" size="sm" class="flex-1" @click="deleteEntry">
                        Confirmer la suppression
                    </Button>
                    <Button variant="secondary" size="sm" class="flex-1" @click="confirmingDelete = false">
                        Annuler
                    </Button>
                </div>
            </div>
        </div>
    </dialog>
</template>

<style scoped>
dialog {
    margin: auto;
}

dialog::backdrop {
    background-color: rgb(0 0 0 / 0.4);
    backdrop-filter: blur(4px);
}
</style>
