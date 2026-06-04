<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import Button from '@/components/widgets/Button.vue';
import { resolveSubjectLabel } from '@/composables/useSubjectLabel';
import { useToasterStore } from '@/stores/toaster';
import type { LessonOption, ScheduleEntry, SlotRow } from '@/types';
import { store } from '@/routes/schedule-entries';

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

const filterEcole      = ref<number | ''>('');
const filterClasse     = ref<number | ''>('');
const selectedLesson   = ref<number | ''>('');
const selectedDay      = ref<number>(1);
const selectedSlotId   = ref<number | null>(null);
const form             = useForm({ classroom: '' });
const confirmingDelete = ref(false);
const syncing          = ref(false);
const toaster          = useToasterStore();

const selectedDayLabel = computed(() => DAY_OPTIONS.find((d) => d.value === selectedDay.value)?.label ?? '');
const selectedSlot     = computed(() => props.slots.find((s) => s.id === selectedSlotId.value) ?? null);
const slotOptions      = computed(() => props.slots.filter((s) => s.type === 'slot'));
const currentEntry     = computed<ScheduleEntry | null>(() => {
    const slot = selectedSlot.value;
    if (!slot) return null;
    return props.entries?.[slot.position]?.[selectedDay.value] ?? null;
});

const ecoleOptions = computed(() => {
    const seen = new Set<number>();
    return props.lessons
        .filter((l) => {
            if (seen.has(l.group.school_id)) return false;
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
            if (seen.has(l.group_id)) return false;
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

watch(filterEcole, () => {
    if (syncing.value) return;
    filterClasse.value = '';
    selectedLesson.value = '';
});
watch(filterClasse, () => {
    if (syncing.value) return;
    selectedLesson.value = '';
});

async function syncFormFromEntry(entry: ScheduleEntry | null) {
    form.classroom = entry?.room ?? '';
    if (entry) {
        const lesson = props.lessons.find((l) => l.id === entry.lesson_id);
        filterEcole.value = lesson?.group.school_id ?? '';
        filterClasse.value = lesson?.group_id ?? '';
        await nextTick();
        selectedLesson.value = entry.lesson_id;
    } else {
        filterEcole.value = '';
        filterClasse.value = '';
        selectedLesson.value = '';
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
    if (syncing.value) return;
    syncing.value = true;
    confirmingDelete.value = false;
    await syncFormFromEntry(entry);
    syncing.value = false;
});

onMounted(() => {
    if (props.open) dialogRef.value?.showModal();
});

watch(
    () => props.open,
    async (isOpen) => {
        if (!dialogRef.value) return;
        if (isOpen) {
            await syncFromEntry();
            dialogRef.value.showModal();
        } else {
            dialogRef.value.close();
        }
    },
);

function onBackdropClick(event: MouseEvent) {
    if (event.target === dialogRef.value) emit('close');
}

function onCancel(event: Event) {
    event.preventDefault();
    emit('close');
}

function save() {
    if (!selectedSlot.value || !selectedLesson.value) return;

    const lesson = props.lessons.find((l) => l.id === selectedLesson.value);
    const schedule = props.schedules.find((s) => s.school_id === lesson?.group.school_id);

    form.transform((data) => ({
        schedule_id: schedule?.id,
        lesson_id: selectedLesson.value,
        position: selectedSlot.value!.position,
        day_of_week: selectedDay.value,
        classroom: data.classroom || null,
    })).post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            toaster.success('Créneau enregistré');
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
                    <div class="flex flex-1 flex-col gap-2">
                        <label for="slot-jour" class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">Jour</label>
                        <select
                            id="slot-jour"
                            v-model="selectedDay"
                            class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline outline-1 -outline-offset-1 outline-border-figma"
                        >
                            <option v-for="d in DAY_OPTIONS" :key="d.value" :value="d.value">{{ d.label }}</option>
                        </select>
                    </div>
                    <div class="flex flex-1 flex-col gap-2">
                        <label for="slot-heure" class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">Heure</label>
                        <select
                            id="slot-heure"
                            v-model="selectedSlotId"
                            class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline outline-1 -outline-offset-1 outline-border-figma"
                        >
                            <option v-for="s in slotOptions" :key="s.id" :value="s.id">{{ s.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="slot-ecole" class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">École</label>
                    <select
                        id="slot-ecole"
                        v-model="filterEcole"
                        class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline outline-1 -outline-offset-1 outline-border-figma"
                    >
                        <option value="">Choisir une école</option>
                        <option
                            v-for="opt in ecoleOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="slot-classe" class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">Classe</label>
                    <select
                        id="slot-classe"
                        v-model="filterClasse"
                        :disabled="!filterEcole"
                        class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline outline-1 -outline-offset-1 outline-border-figma disabled:opacity-50"
                    >
                        <option value="">Choisir une classe</option>
                        <option
                            v-for="opt in classeOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="slot-cours" class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">Cours</label>
                    <select
                        id="slot-cours"
                        v-model="selectedLesson"
                        :disabled="!filterClasse"
                        class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline outline-1 -outline-offset-1 outline-border-figma disabled:opacity-50"
                    >
                        <option value="">Choisir un cours</option>
                        <option
                            v-for="opt in coursOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

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
