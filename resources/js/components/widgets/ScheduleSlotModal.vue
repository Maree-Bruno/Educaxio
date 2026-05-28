<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import Button from '@/components/widgets/Button.vue';

interface SlotRow {
    id: number;
    position: number;
    label: string;
    type: 'slot' | 'lunch';
}

interface EntryData {
    id: number;
    lesson_id: number;
    grade: string;
    subject: string;
    room: string | null;
    school: string;
}

interface LessonOption {
    id: number;
    group_id: number;
    subject_id: number;
    group: {
        id: number;
        grade: string;
        name: string;
        school_id: number;
        school: { id: number; name: string };
    };
    subject: { id: number; name: string };
}

const props = defineProps<{
    open: boolean;
    scheduleSlot: SlotRow | null;
    dayOfWeek: number;
    dayLabel: string;
    entry: EntryData | null;
    lessons: LessonOption[];
    schedules: { id: number; school_id: number }[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const dialogRef = ref<HTMLDialogElement | null>(null);

const filterEcole      = ref<number | ''>('');
const filterClasse     = ref<number | ''>('');
const selectedLesson   = ref<number | ''>('');
const form             = useForm({ classroom: '' });
const confirmingDelete = ref(false);
const syncing          = ref(false);

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
    ).map((l) => ({ value: l.id, label: l.subject.name })),
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

async function syncFromEntry() {
    syncing.value = true;
    confirmingDelete.value = false;
    form.classroom = props.entry?.room ?? '';

    if (props.entry) {
        const lesson = props.lessons.find(
            (l) => l.id === props.entry!.lesson_id,
        );
        filterEcole.value = lesson?.group.school_id ?? '';
        filterClasse.value = lesson?.group_id ?? '';
        await nextTick();
        selectedLesson.value = props.entry.lesson_id;
    } else {
        filterEcole.value = '';
        filterClasse.value = '';
        selectedLesson.value = '';
    }
    syncing.value = false;
}

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
    if (!props.scheduleSlot || !selectedLesson.value) return;

    const lesson = props.lessons.find((l) => l.id === selectedLesson.value);
    const schedule = props.schedules.find((s) => s.school_id === lesson?.group.school_id);

    form.transform((data) => ({
        schedule_id: schedule?.id,
        lesson_id: selectedLesson.value,
        position: props.scheduleSlot!.position,
        day_of_week: props.dayOfWeek,
        classroom: data.classroom || null,
    })).post('/schedule-entries', {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}

function deleteEntry() {
    if (!props.entry) return;
    router.delete(`/schedule-entries/${props.entry.id}`, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
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
                        entry
                            ? 'Modifier le créneau'
                            : 'Ajouter un créneau horaire'
                    }}
                </h2>
                <p class="mt-1 text-sm font-bold text-border-figma">
                    {{ dayLabel }} · {{ scheduleSlot?.label }}
                </p>
            </div>

            <div class="flex flex-col gap-5">
                <!-- École -->
                <div class="flex flex-col gap-2">
                    <span
                        class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase"
                        >École</span
                    >
                    <select
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

                <!-- Classe -->
                <div class="flex flex-col gap-2">
                    <span
                        class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase"
                        >Classe</span
                    >
                    <select
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

                <!-- Cours -->
                <div class="flex flex-col gap-2">
                    <span
                        class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase"
                        >Cours</span
                    >
                    <select
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

                <!-- Local -->
                <div class="flex flex-col gap-2">
                    <span
                        class="text-xs leading-4 font-bold tracking-wide text-border-figma uppercase"
                        >Local</span
                    >
                    <input
                        v-model="form.classroom"
                        type="text"
                        placeholder="Ex : 302"
                        class="w-full rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline outline-1 -outline-offset-1 outline-border-figma placeholder:font-normal placeholder:text-border-figma"
                    />
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-5">
                    <Button
                        variant="primary"
                        size="md"
                        class="flex-1"
                        :disabled="!selectedLesson"
                        :loading="form.processing"
                        @click="save"
                    >
                        Enregistrer
                    </Button>
                    <Button variant="danger" size="md" class="flex-1" @click="emit('close')">
                        Annuler
                    </Button>
                </div>

                <!-- Suppression : étape 1 -->
                <Button
                    v-if="entry && !confirmingDelete"
                    variant="danger"
                    size="sm"
                    class="w-full"
                    @click="confirmingDelete = true"
                >
                    Supprimer le créneau
                </Button>

                <!-- Suppression : étape 2 (confirmation) -->
                <div v-if="entry && confirmingDelete" class="flex gap-3">
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
