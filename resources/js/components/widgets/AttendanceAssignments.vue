<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToasterStore } from '@/stores/toaster';
import {
    store,
    update as updateAssignment,
    destroy as destroyAssignment,
} from '@/routes/assignments';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import DateField from '@/components/widgets/DateField.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import SelectField from '@/components/widgets/SelectField.vue';

export interface Assignment {
    id: number;
    type: 'homework' | 'test';
    title: string;
    scheduled_date: string;
    slot_label: string | null;
    description: string | null;
}


interface ScheduleEntry {
    day_of_week: number;
    slot_label: string;
}

const props = defineProps<{
    assignments: Assignment[];
    lessonId: number | null;
    nextAssignmentDate: string | null;
    schedulePattern: ScheduleEntry[];
    groupName: string | null;
    selectedEntry: string | null;
}>();

// ── Modal ─────────────────────────────────────────────────────────────────
const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const modalMode = ref<'create' | 'edit'>('create');
const editingId = ref<number | null>(null);

const form = useForm({
    lesson_id: null as number | null,
    type: 'homework' as 'homework' | 'test',
    title: '',
    scheduled_date: '',
    description: '',
});

// ── Suppression ───────────────────────────────────────────────────────────
const confirmDeleteRef = ref<InstanceType<typeof BaseModal> | null>(null);
const pendingDelete = ref<{ id: number; title: string } | null>(null);
const hiddenIds = ref(new Set<number>());
const toaster = useToasterStore();

// ── Helpers ───────────────────────────────────────────────────────────────
function isoDow(dateStr: string): number {
    const [y, m, d] = dateStr.split('-').map(Number);

    return ((new Date(y, m - 1, d).getDay() + 6) % 7) + 1;
}

const allowedDows = computed(
    () => new Set(props.schedulePattern.map((e) => e.day_of_week)),
);

const slotsForDate = computed((): ScheduleEntry[] => {
    if (!form.scheduled_date) {
        return [];
    }

    return props.schedulePattern.filter(
        (e) => e.day_of_week === isoDow(form.scheduled_date),
    );
});

const isDateValid = computed(
    () =>
        !form.scheduled_date ||
        allowedDows.value.has(isoDow(form.scheduled_date)),
);

const slotOptions = computed(() =>
    slotsForDate.value.map((e) => ({
        value: e.slot_label,
        label: e.slot_label,
    })),
);

function firstSlotForDate(dateStr: string): string {
    return (
        props.schedulePattern.find((e) => e.day_of_week === isoDow(dateStr))
            ?.slot_label ?? ''
    );
}

function formatDate(dateStr: string): string {
    const [y, m, d] = dateStr.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('fr-BE', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}


const selectedSlot = ref('');

function onDateChange(val: string) {
    form.scheduled_date = val;
    selectedSlot.value = val ? firstSlotForDate(val) : '';
}

// ── Actions ───────────────────────────────────────────────────────────────
function openCreate() {
    modalMode.value = 'create';
    editingId.value = null;
    form.reset();
    form.lesson_id = props.lessonId;
    form.scheduled_date = props.nextAssignmentDate ?? '';
    selectedSlot.value = form.scheduled_date ? firstSlotForDate(form.scheduled_date) : '';
    modalRef.value?.open();
}

function openEdit(a: Assignment) {
    modalMode.value = 'edit';
    editingId.value = a.id;
    form.reset();
    form.type = a.type;
    form.title = a.title;
    form.scheduled_date = a.scheduled_date;
    selectedSlot.value = firstSlotForDate(a.scheduled_date);
    form.description = a.description ?? '';
    modalRef.value?.open();
}

function submit() {
    if (modalMode.value === 'create') {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                modalRef.value?.close();
                toaster.success('Devoir créé');
            },
        });
    } else {
        if (!editingId.value) {
            return;
        }

        form.patch(updateAssignment.url({ assignment: editingId.value }), {
            preserveScroll: true,
            onSuccess: () => {
                modalRef.value?.close();
                toaster.success('Devoir modifié');
            },
        });
    }
}

function requestDelete(a: Assignment) {
    pendingDelete.value = { id: a.id, title: a.title };
    confirmDeleteRef.value?.open();
}

function confirmDelete() {
    if (!pendingDelete.value) {
        return;
    }

    const { id, title } = pendingDelete.value;

    confirmDeleteRef.value?.close();
    pendingDelete.value = null;
    hiddenIds.value = new Set([...hiddenIds.value, id]);
    toaster.deletable(
        `« ${title} » supprimé`,
        () =>
            router.delete(destroyAssignment.url({ assignment: id }), {
                preserveScroll: true,
            }),
        () => {
            hiddenIds.value.delete(id);
            hiddenIds.value = new Set(hiddenIds.value);
        },
    );
}
</script>

<template>
    <div
        class="flex flex-col gap-4 rounded-2xl bg-white p-6 outline -outline-offset-1 outline-neutral-300/10"
    >
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="text-base leading-6 font-bold text-stone-900">
                    Devoirs & Interros
                </h3>
                <p class="mt-0.5 text-xs text-stone-400">
                    Planifiez les devoirs et interrogations pour cette classe.
                </p>
            </div>
            <button
                type="button"
                :disabled="!selectedEntry"
                class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-orange text-sm font-bold text-white transition-opacity disabled:cursor-not-allowed disabled:opacity-40"
                @click="openCreate"
            >
                +
            </button>
        </div>

        <p
            v-if="assignments.length === 0"
            class="text-xs text-stone-400 italic"
        >
            Aucun devoir ni interrogation
        </p>

        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="a in assignments.filter((a) => !hiddenIds.has(a.id))"
                :key="a.id"
                class="-mx-1 flex cursor-pointer items-start gap-2 rounded-xl p-1 transition-colors hover:bg-stone-50"
                @click="openEdit(a)"
            >
                <span
                    class="mt-0.5 shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-bold tracking-wide uppercase"
                    :class="
                        a.type === 'test'
                            ? 'bg-red-100 text-red-600'
                            : 'bg-blue/10 text-blue'
                    "
                    >{{ a.type === 'test' ? 'Interro' : 'Devoir' }}</span
                >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-text-base">
                        {{ a.title }}
                    </p>
                    <p class="text-xs text-stone-400">
                        <time :datetime="a.scheduled_date">{{
                            formatDate(a.scheduled_date)
                        }}</time>
                        <template v-if="a.slot_label"> · {{ a.slot_label }}</template>
                    </p>
                </div>
                <button
                    type="button"
                    class="mt-0.5 shrink-0 text-stone-300 transition-colors hover:text-red-500"
                    title="Supprimer"
                    @click.stop="requestDelete(a)"
                >
                    <Trash :size="16" />
                </button>
            </li>
        </ul>
    </div>

    <BaseModal ref="modalRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">
                    {{ modalMode === 'create' ? 'Nouveau devoir / interro' : 'Modifier' }}
                </h2>
                <p v-if="groupName" class="mt-0.5 text-xs text-stone-400">
                    {{ groupName }}
                </p>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="flex flex-col gap-1.5">
                    <span
                        class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase"
                        >Type</span
                    >
                    <div class="flex gap-2">
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center rounded-2xl border py-2.5 font-manrope text-sm font-bold transition-all duration-150"
                            :class="
                                form.type === 'homework'
                                    ? 'border-blue bg-blue/5 text-blue ring-2 ring-blue/20'
                                    : 'border-border-figma bg-white text-text-base'
                            "
                        >
                            <input
                                v-model="form.type"
                                type="radio"
                                value="homework"
                                class="sr-only"
                            />
                            Devoir
                        </label>
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center rounded-2xl border py-2.5 font-manrope text-sm font-bold transition-all duration-150"
                            :class="
                                form.type === 'test'
                                    ? 'border-blue bg-blue/5 text-blue ring-2 ring-blue/20'
                                    : 'border-border-figma bg-white text-text-base'
                            "
                        >
                            <input
                                v-model="form.type"
                                type="radio"
                                value="test"
                                class="sr-only"
                            />
                            Interrogation
                        </label>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <InputLabel
                        v-model="form.title"
                        label="Titre"
                        placeholder="Ex : Chapitre 3 – exercices"
                        required
                    />
                    <p class="text-xs text-stone-400">
                        Décrivez brièvement le travail demandé ou la matière
                        évaluée.
                    </p>
                </div>

                <div class="flex flex-col gap-1">
                    <DateField
                        label="Date"
                        :model-value="form.scheduled_date"
                        :min="modalMode === 'create' ? new Date().toISOString().slice(0, 10) : undefined"
                        @update:model-value="onDateChange"
                    />
                    <p
                        v-if="modalMode === 'create' && form.scheduled_date && !isDateValid"
                        class="text-xs text-red-500"
                    >
                        Pas de cours ce jour-là.
                    </p>
                    <p v-else class="text-xs text-stone-400">
                        La date du prochain cours est déjà présélectionner.
                        Seuls les jours où vous avez cours avec cette classe
                        sont valides.
                    </p>
                </div>

                <template v-if="form.scheduled_date && isDateValid">
                    <div
                        v-if="slotsForDate.length === 1"
                        class="flex flex-col gap-1.5"
                    >
                        <span
                            class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase"
                            >Heure de cours</span
                        >
                        <div
                            class="flex min-h-11.5 items-center rounded-2xl bg-bg-primary px-3 text-sm font-bold text-text-base"
                        >
                            {{ slotsForDate[0].slot_label }}
                        </div>
                    </div>
                    <SelectField
                        v-else-if="slotsForDate.length > 1"
                        label="Heure de cours"
                        placeholder="Choisir un créneau…"
                        :options="slotOptions"
                        :model-value="selectedSlot || null"
                        @update:model-value="
                            (val) => (selectedSlot = val ? String(val) : '')
                        "
                    />
                </template>

                <div class="flex flex-col gap-1.5">
                    <label
                        class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase"
                    >
                        Description
                        <span class="font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Précisions…"
                        class="w-full resize-none rounded-2xl border border-border-figma bg-white px-3 py-3 font-manrope text-sm text-text-base transition-all duration-150 outline-none placeholder:font-normal placeholder:text-gray-400 focus:border-blue focus:ring-2 focus:ring-blue/20"
                    />
                    <p class="text-xs text-stone-400">
                        Visible dans l'agenda de la classe.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="modalRef?.close()"
                        >Annuler</Button
                    >
                    <Button
                        type="submit"
                        variant="primary"
                        size="sm"
                        :disabled="modalMode === 'create' && !isDateValid"
                        :loading="form.processing"
                        >{{ modalMode === 'create' ? 'Enregistrer' : 'Modifier' }}</Button
                    >
                </div>
            </form>
        </div>
    </BaseModal>

    <BaseModal ref="confirmDeleteRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">
                    Supprimer ce devoir ?
                </h2>
                <p v-if="pendingDelete" class="mt-1 text-sm text-stone-500">
                    « {{ pendingDelete.title }} »
                </p>
                <p class="mt-1 text-xs text-stone-400">
                    Cette action est irréversible.
                </p>
            </div>
            <div class="flex justify-end gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="confirmDeleteRef?.close()"
                    >Annuler</Button
                >
                <Button variant="danger" size="sm" @click="confirmDelete"
                    >Supprimer</Button
                >
            </div>
        </div>
    </BaseModal>
</template>
