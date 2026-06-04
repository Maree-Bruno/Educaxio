<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useCurrentSlot } from '@/composables/useCurrentSlot';
import { useHiddenIds } from '@/composables/useHiddenIds';
import { dashboard, agenda, attendances } from '@/routes';
import { update as updateAssignment, destroy as destroyAssignment } from '@/routes/assignments';
import { show as showClasslist } from '@/routes/classlist';
import AgendaAssignmentRow from '@/components/widgets/AgendaAssignmentRow.vue';
import type { AgendaAssignment } from '@/components/widgets/AgendaAssignmentRow.vue';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import DashboardCard from '@/components/admin/DashboardCard.vue';
import DateField from '@/components/widgets/DateField.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import SidebarLayout from '@/components/widgets/SidebarLayout.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { useToasterStore } from '@/stores/toaster';
import type { User } from '@/types';

interface Entry {
    creneau: string;
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
    start_time: string | null;
    end_time: string | null;
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

const { user, selectedDate, upcomingAssignments, slots } = defineProps<{
    slots: Slot[];
    groups: Group[];
    date: string;
    selectedDate: string;
    upcomingAssignments: AgendaAssignment[];
    upcomingAssignmentsTotal: number;
    user: User;
}>();

setPageTitle(`Bonjour ${user.name}`);

const today = new Date().toISOString().slice(0, 10);
const isEditable = selectedDate <= today;

function changeDate(value: string) {
    router.get(dashboard.url(), { date: value }, { preserveState: false });
}

const { now, activeSlotIndex } = useCurrentSlot(slots);

const isViewingToday = computed(() => {
    if (!now.value) return false;
    const localDate = new Date(now.value.getTime() - now.value.getTimezoneOffset() * 60000)
        .toISOString()
        .slice(0, 10);
    return selectedDate === localDate;
});

let midnightTimer: ReturnType<typeof setTimeout> | null = null;

onMounted(() => {
    if (selectedDate !== today) return;

    const n = new Date();
    const midnight = new Date(n);
    midnight.setHours(24, 0, 0, 0);

    midnightTimer = setTimeout(() => {
        router.get(dashboard.url(), {}, { preserveState: false });
    }, midnight.getTime() - n.getTime());
});

onUnmounted(() => {
    if (midnightTimer) clearTimeout(midnightTimer);
});

const editModalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const editingAssignment = ref<AgendaAssignment | null>(null);

const confirmDeleteRef = ref<InstanceType<typeof BaseModal> | null>(null);
const pendingDelete = ref<{ id: number; title: string } | null>(null);
const { hide: hideAssignment, show: showAssignment, isHidden: isAssignmentHidden } = useHiddenIds();
const toaster = useToasterStore();
const editForm = useForm({
    type: 'homework' as 'homework' | 'test',
    title: '',
    scheduled_date: '',
    description: '',
});

function openEdit(id: number) {
    const a = upcomingAssignments.find((x) => x.id === id);
    if (!a) return;
    editingAssignment.value = a;
    editForm.type = a.type;
    editForm.title = a.title;
    editForm.scheduled_date = a.scheduled_date;
    editForm.description = a.description ?? '';
    editModalRef.value?.open();
}

function submitEdit() {
    if (!editingAssignment.value) return;
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
    if (!a) return;
    pendingDelete.value = { id, title: a.title };
    confirmDeleteRef.value?.open();
}

function confirmDelete() {
    if (!pendingDelete.value) return;
    const { id, title } = pendingDelete.value;
    confirmDeleteRef.value?.close();
    pendingDelete.value = null;
    hideAssignment(id);
    toaster.deletable(
        `« ${title} » supprimé`,
        () => router.delete(destroyAssignment.url({ assignment: id }), { preserveScroll: true }),
        () => showAssignment(id),
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

        <SidebarLayout sidebar-width="sm">
            <DashboardCard
                title="Horaire du jour"
                description="Cliquez sur « Présences » pour enregistrer les présences d'un cours."
            >
                <EmptyState
                    v-if="slots.length === 0"
                    message="Pas de cours aujourd'hui"
                    class="bg-white"
                />
                <div v-else>
                    <template v-for="(slot, index) in slots" :key="slot.id">
                        <div
                            v-if="slot.type === 'lunch'"
                            class="flex items-center justify-center border-b border-zinc-400/10 py-3"
                            :class="[
                                index === slots.length - 1 && 'border-b-0',
                                isViewingToday && activeSlotIndex === index ? 'bg-blue/10' : 'bg-white',
                            ]"
                        >
                            <span class="text-xs font-bold tracking-wider text-zinc-400 uppercase">Pause</span>
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-3 border-b border-zinc-400/10 px-6 py-4"
                            :class="[
                                index === slots.length - 1 && 'border-b-0',
                                isViewingToday && activeSlotIndex === index ? 'bg-blue/10' : 'bg-white',
                            ]"
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
                                    :href="attendances.url({ query: { creneau: slot.entry.creneau } })"
                                    class="shrink-0 text-xs font-bold text-blue hover:underline"
                                >
                                    Présences
                                </a>
                            </template>
                            <p v-else class="flex-1 text-sm text-zinc-300">Libre</p>
                        </div>
                    </template>
                </div>
            </DashboardCard>

            <DashboardCard
                title="Devoirs & Interros"
                description="Prochains devoirs et interrogations pour vos classes."
                class="bg-white shadow-sm outline -outline-offset-1 outline-neutral-300/10"
            >
                <template v-if="upcomingAssignmentsTotal > 5" #action>
                    <a
                        :href="agenda.url({ query: { tab: 'assignments' } })"
                        class="text-xs font-bold text-blue hover:underline"
                    >
                        Voir tout →
                    </a>
                </template>
                <p v-if="upcomingAssignments.length === 0" class="px-6 py-8 text-center text-xs italic text-stone-400">
                    Aucun devoir ni interrogation à venir
                </p>
                <ul v-else class="divide-y divide-neutral-100">
                    <AgendaAssignmentRow
                        v-for="a in upcomingAssignments.filter(({ id }) => !isAssignmentHidden(id))"
                        :key="a.id"
                        :assignment="a"
                        @edit="openEdit"
                        @delete="requestDelete"
                    />
                </ul>
            </DashboardCard>

            <template #sidebar>
                <DashboardCard title="Mes classes" description="Accédez aux fiches de vos classes et à leurs présences.">
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
                </DashboardCard>
            </template>
        </SidebarLayout>
    </div>

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
                    <label for="edit-description" class="font-manrope text-xs font-bold uppercase leading-4 tracking-widest text-border-figma">
                        Description <span class="font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea
                        id="edit-description"
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
