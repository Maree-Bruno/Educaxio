<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { dashboard, agenda, attendances } from '@/routes';
import { update as updateAssignment, destroy as destroyAssignment } from '@/routes/assignments';
import { show as showClasslist } from '@/routes/classlist';
import Badge from '@/components/widgets/Badge.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import DateField from '@/components/widgets/DateField.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { useToasterStore } from '@/stores/toaster';
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

interface UpcomingAssignment {
    id: number;
    type: 'homework' | 'test';
    title: string;
    scheduled_date: string;
    description: string | null;
    group: string;
    subject: string;
    school: string;
}

const { user, selectedDate, upcomingAssignments } = defineProps<{
    slots: Slot[];
    groups: Group[];
    date: string;
    selectedDate: string;
    upcomingAssignments: UpcomingAssignment[];
    upcomingAssignmentsTotal: number;
    user: User;
}>();

setPageTitle(`Bonjour ${user.name}`);

const today = new Date().toISOString().slice(0, 10);
const isEditable = selectedDate <= today;

function changeDate(value: string) {
    router.get(dashboard.url(), { date: value }, { preserveState: false });
}

// Auto-avance à minuit si l'utilisateur est sur "aujourd'hui"
let midnightTimer: ReturnType<typeof setTimeout> | null = null;

onMounted(() => {
    if (selectedDate !== today) {
        return;
    }

    const now = new Date();
    const midnight = new Date(now);
    midnight.setHours(24, 0, 0, 0);

    midnightTimer = setTimeout(() => {
        router.get(dashboard.url(), {}, { preserveState: false });
    }, midnight.getTime() - now.getTime());
});

onUnmounted(() => {
    if (midnightTimer) {
        clearTimeout(midnightTimer);
    }
});

function formatDate(dateStr: string): string {
    const [y, m, d] = dateStr.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('fr-BE', { weekday: 'short', day: 'numeric', month: 'short' });
}

const editModalRef      = ref<InstanceType<typeof BaseModal> | null>(null);
const editingAssignment = ref<UpcomingAssignment | null>(null);

const confirmDeleteRef = ref<InstanceType<typeof BaseModal> | null>(null);
const pendingDelete    = ref<{ id: number; title: string } | null>(null);
const hiddenIds        = ref(new Set<number>());
const toaster          = useToasterStore();
const editForm = useForm({
    type:           'homework' as 'homework' | 'test',
    title:          '',
    scheduled_date: '',
    description:    '',
});

function openEdit(a: UpcomingAssignment) {
    editingAssignment.value = a;
    editForm.type           = a.type;
    editForm.title          = a.title;
    editForm.scheduled_date = a.scheduled_date;
    editForm.description    = a.description ?? '';
    editModalRef.value?.open();
}

function submitEdit() {
    if (!editingAssignment.value) {
        return;
    }

    editForm.patch(updateAssignment.url({ assignment: editingAssignment.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            editModalRef.value?.close();
            toaster.success('Devoir modifié');
        },
    });
}

function requestDelete(id: number) {
    const a = upcomingAssignments.find((x) => x.id === id);

    if (!a) {
        return;
    }

    pendingDelete.value = { id, title: a.title };
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
        () => router.delete(destroyAssignment.url({ assignment: id }), { preserveScroll: true }),
        () => { hiddenIds.value.delete(id); hiddenIds.value = new Set(hiddenIds.value); },
    );
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
                    <h3 class="text-base font-bold text-stone-900">Horaire du jour</h3>
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
                            <span class="text-xs font-bold tracking-wider text-zinc-400 uppercase">Pause</span>
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-3 border-b border-zinc-400/10 bg-white px-6 py-4"
                            :class="index === slots.length - 1 && 'border-b-0'"
                        >
                            <span class="w-20 shrink-0 text-xs font-extrabold text-border-figma">{{ slot.label }}</span>
                            <template v-if="slot.entry">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-extrabold text-text-base">
                                        {{ slot.entry.group }} · {{ slot.entry.subject }}
                                    </p>
                                    <p class="truncate text-xs text-border-figma">
                                        {{ slot.entry.room ?? '–' }} · {{ slot.entry.school }}
                                    </p>
                                </div>
                                <a
                                    v-if="isEditable"
                                    :href="attendances.url({ query: { entry: slot.entry.id } })"
                                    class="shrink-0 text-xs font-bold text-blue hover:underline"
                                >
                                    Présences
                                </a>
                            </template>
                            <p v-else class="flex-1 text-sm text-zinc-300">Libre</p>
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex w-full shrink-0 flex-col gap-6 xl:w-72">
                <div class="overflow-hidden rounded-2xl">
                    <div class="flex items-center justify-between border-b border-neutral-300/10 bg-white px-6 py-5">
                        <h3 class="text-base font-bold text-stone-900">Devoirs & Interros</h3>
                        <span v-if="upcomingAssignmentsTotal > 0" class="text-xs text-stone-400">
                            {{ upcomingAssignmentsTotal }} à venir
                        </span>
                    </div>

                    <div v-if="upcomingAssignments.length === 0" class="bg-white px-6 py-8 text-center">
                        <p class="text-xs italic text-stone-400">Aucun devoir ni interrogation à venir</p>
                    </div>

                    <template v-else>
                        <ul class="divide-y divide-neutral-100 bg-white">
                            <li
                                v-for="a in upcomingAssignments.filter((a) => !hiddenIds.has(a.id))"
                                :key="a.id"
                                class="group flex cursor-pointer items-start gap-3 px-6 py-4 transition-colors hover:bg-gray-50"
                                @click="openEdit(a)"
                            >
                                <span
                                    class="mt-0.5 shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                    :class="a.type === 'test' ? 'bg-red-100 text-red-600' : 'bg-blue/10 text-blue'"
                                >{{ a.type === 'test' ? 'Interro' : 'Devoir' }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-text-base">{{ a.title }}</p>
                                    <p class="truncate text-xs text-stone-400">{{ a.group }} · {{ a.subject }} · {{ a.school }}</p>
                                    <p class="text-xs font-medium text-border-figma">{{ formatDate(a.scheduled_date) }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 text-stone-300 opacity-0 transition-all group-hover:opacity-100 hover:text-red-500"
                                    title="Supprimer"
                                    @click.stop="requestDelete(a.id)"
                                ><Trash :size="16" /></button>
                            </li>
                        </ul>
                        <div v-if="upcomingAssignmentsTotal > 5" class="border-t border-neutral-100 bg-white px-6 py-3">
                            <a :href="agenda.url()" class="text-xs font-bold text-blue hover:underline">
                                Voir les {{ upcomingAssignmentsTotal }} devoirs & interros →
                            </a>
                        </div>
                    </template>
                </div>
                <div class="overflow-hidden rounded-2xl">
                    <div class="border-b border-neutral-300/10 bg-white px-6 py-5">
                        <h3 class="text-base font-bold text-stone-900">Mes classes</h3>
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
                                <p class="truncate text-xs text-stone-400">{{ group.school }}</p>
                                <p class="truncate text-xs text-border-figma">{{ group.subjects.join(', ') }}</p>
                            </div>
                            <Badge :href="showClasslist.url({ group: group.slug })">
                                {{ group.grade }}{{ group.name }}
                            </Badge>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale confirmation suppression -->
    <BaseModal ref="confirmDeleteRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">Supprimer ce devoir ?</h2>
                <p v-if="pendingDelete" class="mt-1 text-sm text-stone-500">
                    « {{ pendingDelete.title }} »
                </p>
                <p class="mt-1 text-xs text-stone-400">Cette action est irréversible.</p>
            </div>
            <div class="flex justify-end gap-2">
                <Button variant="ghost" size="sm" @click="confirmDeleteRef?.close()">Annuler</Button>
                <Button variant="danger" size="sm" @click="confirmDelete">Supprimer</Button>
            </div>
        </div>
    </BaseModal>

    <!-- Modale édition devoir -->
    <BaseModal ref="editModalRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">Modifier</h2>
                <p v-if="editingAssignment" class="mt-0.5 text-xs text-stone-400">
                    {{ editingAssignment.subject }} · {{ editingAssignment.group }}
                </p>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submitEdit">
                <div class="flex flex-col gap-1.5">
                    <span class="font-manrope text-xs font-bold uppercase leading-4 tracking-widest text-border-figma">Type</span>
                    <div class="flex gap-2">
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center rounded-2xl border py-2.5 font-manrope text-sm font-bold transition-all duration-150"
                            :class="editForm.type === 'homework' ? 'border-blue bg-blue/5 text-blue ring-2 ring-blue/20' : 'border-border-figma bg-white text-text-base'"
                        >
                            <input v-model="editForm.type" type="radio" value="homework" class="sr-only" />
                            Devoir
                        </label>
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center rounded-2xl border py-2.5 font-manrope text-sm font-bold transition-all duration-150"
                            :class="editForm.type === 'test' ? 'border-blue bg-blue/5 text-blue ring-2 ring-blue/20' : 'border-border-figma bg-white text-text-base'"
                        >
                            <input v-model="editForm.type" type="radio" value="test" class="sr-only" />
                            Interrogation
                        </label>
                    </div>
                </div>

                <InputLabel v-model="editForm.title" label="Titre" placeholder="Ex : Chapitre 3 – exercices" />

                <DateField
                    label="Date"
                    :model-value="editForm.scheduled_date"
                    @update:model-value="editForm.scheduled_date = $event"
                />

                <div class="flex flex-col gap-1.5">
                    <label class="font-manrope text-xs font-bold uppercase leading-4 tracking-widest text-border-figma">
                        Description <span class="font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea
                        v-model="editForm.description"
                        rows="3"
                        placeholder="Précisions…"
                        class="w-full resize-none rounded-2xl border border-border-figma bg-white px-3 py-3 font-manrope text-sm text-text-base placeholder:font-normal placeholder:text-gray-400 outline-none transition-all duration-150 focus:border-blue focus:ring-2 focus:ring-blue/20"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button type="button" variant="ghost" size="sm" @click="editModalRef?.close()">Annuler</Button>
                    <Button type="submit" variant="primary" size="sm" :loading="editForm.processing">Enregistrer</Button>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
