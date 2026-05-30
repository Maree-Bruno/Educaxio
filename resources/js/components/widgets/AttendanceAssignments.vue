<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import DateField from '@/components/widgets/DateField.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import SelectField from '@/components/widgets/SelectField.vue';

export interface Assignment {
    id: number;
    type: 'homework' | 'test';
    title: string;
    scheduled_date: string;
    description: string | null;
}

interface ScheduleEntry { day_of_week: number; slot_label: string }

const props = defineProps<{
    assignments:        Assignment[];
    lessonId:           number | null;
    nextAssignmentDate: string | null;
    schedulePattern:    ScheduleEntry[];
    groupName:          string | null;
    selectedEntry:      number | null;
}>();

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const form = useForm({
    lesson_id:      null as number | null,
    type:           'homework' as 'homework' | 'test',
    title:          '',
    scheduled_date: '',
    description:    '',
});

// ISO 8601 day of week: 1=Mon … 7=Sun
function isoDow(dateStr: string): number {
    const [y, m, d] = dateStr.split('-').map(Number);

    return ((new Date(y, m - 1, d).getDay() + 6) % 7) + 1;
}

const allowedDows = computed(() => new Set(props.schedulePattern.map((e) => e.day_of_week)));

const slotsForDate = computed((): ScheduleEntry[] => {
    if (!form.scheduled_date) {
        return [];
    }

    const dow = isoDow(form.scheduled_date);

    return props.schedulePattern.filter((e) => e.day_of_week === dow);
});

const isDateValid = computed(
    () => !form.scheduled_date || allowedDows.value.has(isoDow(form.scheduled_date)),
);

const slotOptions = computed(() =>
    slotsForDate.value.map((e) => ({ value: e.slot_label, label: e.slot_label })),
);

// Only used when multiple slots exist on the chosen day
const selectedSlot = ref('');

function firstSlotForDate(dateStr: string): string {
    const dow = isoDow(dateStr);

    return props.schedulePattern.find((e) => e.day_of_week === dow)?.slot_label ?? '';
}

function open() {
    form.reset();
    form.lesson_id = props.lessonId;
    form.scheduled_date = props.nextAssignmentDate ?? '';
    selectedSlot.value = form.scheduled_date ? firstSlotForDate(form.scheduled_date) : '';
    modalRef.value?.open();
}

function submit() {
    form.post('/assignments', {
        preserveScroll: true,
        onSuccess: () => modalRef.value?.close(),
    });
}

function remove(id: number) {
    router.delete(`/assignments/${id}`, { preserveScroll: true });
}

function onDateChange(val: string) {
    form.scheduled_date = val;
    selectedSlot.value = val ? firstSlotForDate(val) : '';
}

function formatDate(dateStr: string): string {
    const [y, m, d] = dateStr.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('fr-BE', {
        weekday: 'short', day: 'numeric', month: 'short',
    });
}
</script>

<template>
    <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 outline -outline-offset-1 outline-neutral-300/10">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold leading-6 text-stone-900">Devoirs & Interros</h3>
            <button
                type="button"
                :disabled="!selectedEntry"
                class="flex h-6 w-6 items-center justify-center rounded-lg bg-orange text-sm font-bold text-white transition-opacity disabled:cursor-not-allowed disabled:opacity-40"
                @click="open"
            >+</button>
        </div>

        <p v-if="assignments.length === 0" class="text-xs italic text-stone-400">Aucun devoir ni interrogation</p>

        <ul v-else class="flex flex-col gap-3">
            <li v-for="a in assignments" :key="a.id" class="flex items-start gap-2">
                <span
                    class="mt-0.5 shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                    :class="a.type === 'test' ? 'bg-red-100 text-red-600' : 'bg-blue/10 text-blue'"
                >{{ a.type === 'test' ? 'Interro' : 'Devoir' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-text-base">{{ a.title }}</p>
                    <p class="text-xs text-stone-400">{{ formatDate(a.scheduled_date) }}</p>
                </div>
                <button
                    type="button"
                    class="mt-0.5 shrink-0 text-stone-300 transition-colors hover:text-red-500"
                    title="Supprimer"
                    @click="remove(a.id)"
                >✕</button>
            </li>
        </ul>
    </div>

    <BaseModal ref="modalRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">Nouveau devoir / interro</h2>
                <p v-if="groupName" class="mt-0.5 text-xs text-stone-400">{{ groupName }}</p>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submit">

                <div class="flex flex-col gap-1.5">
                    <span class="font-manrope text-xs font-bold uppercase leading-4 tracking-widest text-border-figma">Type</span>
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
                </div>

                <InputLabel v-model="form.title" label="Titre" placeholder="Ex : Chapitre 3 – exercices" />

                <!-- Date -->
                <div class="flex flex-col gap-1">
                    <DateField
                        label="Date"
                        :model-value="form.scheduled_date"
                        :min="new Date().toISOString().slice(0, 10)"
                        @update:model-value="onDateChange"
                    />
                    <p v-if="form.scheduled_date && !isDateValid" class="text-xs text-red-500">
                        Pas de cours ce jour-là.
                    </p>
                </div>

                <!-- Heure de cours -->
                <template v-if="form.scheduled_date && isDateValid">
                    <!-- Single slot: read-only badge -->
                    <div v-if="slotsForDate.length === 1" class="flex flex-col gap-1.5">
                        <span class="font-manrope text-xs font-bold uppercase leading-4 tracking-widest text-border-figma">Heure de cours</span>
                        <div class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base">
                            {{ slotsForDate[0].slot_label }}
                        </div>
                    </div>
                    <!-- Multiple slots: let user pick -->
                    <SelectField
                        v-else-if="slotsForDate.length > 1"
                        label="Heure de cours"
                        placeholder="Choisir un créneau…"
                        :options="slotOptions"
                        :model-value="selectedSlot || null"
                        @update:model-value="(val) => selectedSlot = val ? String(val) : ''"
                    />
                </template>

                <div class="flex flex-col gap-1.5">
                    <label class="font-manrope text-xs font-bold uppercase leading-4 tracking-widest text-border-figma">
                        Description <span class="font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Précisions…"
                        class="w-full resize-none rounded-2xl border border-border-figma bg-white px-3 py-3 font-manrope text-sm text-text-base placeholder:font-normal placeholder:text-gray-400 outline-none transition-all duration-150 focus:border-blue focus:ring-2 focus:ring-blue/20"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button type="button" variant="ghost" size="sm" @click="modalRef?.close()">Annuler</Button>
                    <Button
                        type="submit"
                        variant="primary"
                        size="sm"
                        :disabled="!isDateValid"
                        :loading="form.processing"
                    >Enregistrer</Button>
                </div>
            </form>
        </div>
    </BaseModal>
</template>