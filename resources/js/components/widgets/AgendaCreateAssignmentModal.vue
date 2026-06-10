<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import DateField from '@/components/widgets/DateField.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import KbdShortcut from '@/components/widgets/KbdShortcut.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { useSaveShortcut } from '@/composables/useSaveShortcut';
import { store } from '@/routes/assignments';
import { useToasterStore } from '@/stores/toaster';

interface ScheduleEntry {
    day_of_week: number;
    slot_label: string;
}

interface AgendaLessonOption {
    id: number;
    label: string;
    schedule_pattern: ScheduleEntry[];
    next_assignment_date: string | null;
}

const props = defineProps<{
    lessonOptions: AgendaLessonOption[];
}>();

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const isOpen = ref(false);
const toaster = useToasterStore();

const form = useForm({
    lesson_id: null as number | null,
    type: 'homework' as 'homework' | 'test',
    title: '',
    scheduled_date: '',
    description: '',
});

const lessonSelectOptions = computed(() =>
    props.lessonOptions.map((l) => ({ value: l.id, label: l.label })),
);

const selectedLesson = computed(() =>
    props.lessonOptions.find((l) => l.id === form.lesson_id) ?? null,
);

const schedulePattern = computed(() => selectedLesson.value?.schedule_pattern ?? []);

function isoDow(dateStr: string): number {
    const [y, m, d] = dateStr.split('-').map(Number);

    return ((new Date(y, m - 1, d).getDay() + 6) % 7) + 1;
}

const allowedDows = computed(() => new Set(schedulePattern.value.map((e) => e.day_of_week)));

const slotsForDate = computed((): ScheduleEntry[] => {
    if (!form.scheduled_date) {
        return [];
    }

    return schedulePattern.value.filter(
        (e) => e.day_of_week === isoDow(form.scheduled_date),
    );
});

const isDateValid = computed(
    () =>
        !form.scheduled_date ||
        !selectedLesson.value ||
        allowedDows.value.has(isoDow(form.scheduled_date)),
);

const slotOptions = computed(() =>
    slotsForDate.value.map((e) => ({ value: e.slot_label, label: e.slot_label })),
);

function firstSlotForDate(dateStr: string): string {
    return (
        schedulePattern.value.find((e) => e.day_of_week === isoDow(dateStr))
            ?.slot_label ?? ''
    );
}

const selectedSlot = ref('');

function onDateChange(val: string) {
    form.scheduled_date = val;
    selectedSlot.value = val ? firstSlotForDate(val) : '';
}

watch(
    () => form.lesson_id,
    () => {
        const next = selectedLesson.value?.next_assignment_date ?? '';
        form.scheduled_date = next;
        selectedSlot.value = next ? firstSlotForDate(next) : '';
    },
);

function open() {
    isOpen.value = true;
    form.reset();
    selectedSlot.value = '';
    modalRef.value?.open();
}

function closeModal() {
    isOpen.value = false;
    modalRef.value?.close();
}

function submit() {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            toaster.success('Devoir créé');
        },
    });
}

useSaveShortcut(() => {
    if (isOpen.value) {
        submit();
    }
});

defineExpose({ open });
</script>

<template>
    <BaseModal ref="modalRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">Nouveau devoir / interro</h2>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="flex flex-col gap-1.5">
                    <SelectField
                        label="Cours"
                        placeholder="Choisir un cours…"
                        :options="lessonSelectOptions"
                        :model-value="form.lesson_id"
                        @update:model-value="(v) => (form.lesson_id = v as number | null)"
                    />
                    <p v-if="form.errors.lesson_id" class="font-manrope text-sm font-medium text-pink">{{ form.errors.lesson_id }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase">Type</span>
                    <div class="flex gap-2">
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center rounded-2xl border py-2.5 font-manrope text-sm font-bold transition-all duration-150"
                            :class="form.type === 'homework' ? 'border-blue bg-blue/5 text-blue ring-2 ring-blue/20' : 'border-border-figma bg-white text-text-base'"
                        >
                            <input v-model="form.type" type="radio" value="homework" class="sr-only" />
                            Devoir
                        </label>
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center rounded-2xl border py-2.5 font-manrope text-sm font-bold transition-all duration-150"
                            :class="form.type === 'test' ? 'border-blue bg-blue/5 text-blue ring-2 ring-blue/20' : 'border-border-figma bg-white text-text-base'"
                        >
                            <input v-model="form.type" type="radio" value="test" class="sr-only" />
                            Interrogation
                        </label>
                    </div>
                    <p v-if="form.errors.type" class="font-manrope text-sm font-medium text-pink">{{ form.errors.type }}</p>
                </div>

                <div class="flex flex-col gap-1">
                    <InputLabel
                        v-model="form.title"
                        label="Titre"
                        placeholder="Ex : Chapitre 3 – exercices"
                        required
                        :error="form.errors.title"
                    />
                    <p v-if="!form.errors.title" class="text-xs text-stone-400">
                        Décrivez brièvement le travail demandé ou la matière évaluée.
                    </p>
                </div>

                <div class="flex flex-col gap-1">
                    <DateField
                        label="Date"
                        :model-value="form.scheduled_date"
                        :min="new Date().toISOString().slice(0, 10)"
                        @update:model-value="onDateChange"
                    />
                    <p v-if="form.errors.scheduled_date" class="font-manrope text-sm font-medium text-pink">{{ form.errors.scheduled_date }}</p>
                    <p v-else-if="form.scheduled_date && selectedLesson && !isDateValid" class="text-xs text-red-500">
                        Pas de cours ce jour-là.
                    </p>
                </div>

                <template v-if="form.scheduled_date && isDateValid">
                    <div v-if="slotsForDate.length === 1" class="flex flex-col gap-1.5">
                        <span class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase">Heure de cours</span>
                        <div class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base">
                            {{ slotsForDate[0].slot_label }}
                        </div>
                    </div>
                    <SelectField
                        v-else-if="slotsForDate.length > 1"
                        label="Heure de cours"
                        placeholder="Choisir un créneau…"
                        :options="slotOptions"
                        :model-value="selectedSlot || null"
                        @update:model-value="(val) => (selectedSlot = val ? String(val) : '')"
                    />
                </template>

                <div class="flex flex-col gap-1.5">
                    <label class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase">
                        Description <span class="font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Précisions…"
                        class="w-full resize-none rounded-2xl border bg-white px-3 py-3 font-manrope text-sm text-text-base transition-all duration-150 outline-none placeholder:font-normal placeholder:text-gray-400"
                        :class="form.errors.description ? 'border-border-figma focus:border-pink focus:ring-2 focus:ring-pink/20' : 'border-border-figma focus:border-blue focus:ring-2 focus:ring-blue/20'"
                    />
                    <p v-if="form.errors.description" class="font-manrope text-sm font-medium text-pink">{{ form.errors.description }}</p>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button type="button" variant="ghost" size="sm" @click="closeModal">Annuler</Button>
                    <Button
                        type="submit"
                        variant="primary"
                        size="sm"
                        :disabled="!isDateValid"
                        :loading="form.processing"
                    >
                        Enregistrer <KbdShortcut keys="⌘S" />
                    </Button>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
