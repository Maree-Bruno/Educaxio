<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProfileAvatarPicker from '@/components/settings/ProfileAvatarPicker.vue';
import ProfileSection from '@/components/settings/ProfileSection.vue';
import Badge from '@/components/widgets/Badge.vue';
import Button from '@/components/widgets/Button.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import SchoolJoinForm from '@/components/widgets/SchoolJoinForm.vue';
import SubjectPicker from '@/components/widgets/SubjectPicker.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { resolveSubjectLabel } from '@/composables/useSubjectLabel';
import { useSlotTimeForms } from '@/composables/useSlotTimeForms';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useToasterStore } from '@/stores/toaster';
import type { Lesson } from '@/types';

defineOptions({ layout: AppLayout });
setPageTitle('Profil');

type SlotDef = { id: number; label: string; type: string; start_time: string | null; end_time: string | null };

type Props = {
    assignedLessons: Lesson[];
    allSubjects: { id: number; name: string }[];
    userSubjectIds: number[];
    pendingRequests: { id: number; school: { id: number; name: string } }[];
    availableSchools: { id: number; name: string }[];
    scheduleSlots: SlotDef[];
    adminSchoolSlotTimes: Record<string, Record<string, { start_time: string; end_time: string }>>;
};
const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const auth = useAuthStore();
const toaster = useToasterStore();

const profileForm = useForm({
    name: user.value.name ?? '',
    email: user.value.email ?? '',
    picture: null as File | null,
});

function submitProfile() {
    profileForm.patch('/settings/profile', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => toaster.success('Profil enregistré'),
    });
}

function submitProfileWithUndo() {
    const snapshot = { name: profileForm.name, email: profileForm.email };
    toaster.deletable(
        'Profil enregistré',
        () => submitProfile(),
        () => {
            profileForm.name = snapshot.name;
            profileForm.email = snapshot.email;
        },
    );
}

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submitPassword() {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

const addingSchool = ref(false);

function cancelRequest(id: number) {
    router.delete(`/pending/join-requests/${id}`);
}

const lessonsBySchool = computed(() => {
    const map = new Map<string, Lesson[]>();

    for (const lesson of props.assignedLessons) {
        const key = lesson.group!.school.name;
        map.set(key, [...(map.get(key) ?? []), lesson]);
    }

    return [...map.entries()].map(([school, lessons]) => ({ school, lessons }));
});

const { slotTimeForms, slotTimeErrors, saveSlotTimes } = useSlotTimeForms(
    auth.adminSchools,
    props.scheduleSlots,
    props.adminSchoolSlotTimes,
);

const showDeleteModal = ref(false);
const deleteForm = useForm({});

function confirmDelete() {
    deleteForm.delete('/settings/profile');
}
</script>

<template>
    <div class="flex flex-col gap-8">

        <ProfileSection title="Informations personnelles">
            <div class="flex flex-col gap-5 sm:flex-row">
                <ProfileAvatarPicker
                    :current-picture="user.picture ?? null"
                    :name="user.name ?? ''"
                    @update:picture="profileForm.picture = $event"
                />
                <div class="flex flex-1 flex-col items-end gap-5">
                    <InputLabel
                        v-model="profileForm.name"
                        label="Nom d'utilisateur"
                        :placeholder="user.name"
                        autocomplete="name"
                        :error="profileForm.errors.name"
                        class="w-full"
                    />
                    <InputLabel
                        v-model="profileForm.email"
                        label="Adresse email"
                        type="email"
                        :placeholder="user.email"
                        autocomplete="email"
                        :error="profileForm.errors.email"
                        class="w-full"
                    />
                    <Button
                        variant="primary"
                        size="sm"
                        label="Enregistrer"
                        :loading="profileForm.processing"
                        @click="submitProfileWithUndo"
                    />
                </div>
            </div>
        </ProfileSection>

        <ProfileSection v-if="auth.isTeacher" title="Mes cours">
            <p v-if="assignedLessons.length === 0" class="py-4 text-sm text-border-figma">
                Aucun cours ne vous a encore été attribué.
            </p>
            <div v-else class="flex flex-col gap-6">
                <div v-for="group in lessonsBySchool" :key="group.school" class="flex flex-col gap-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-stone-500">{{ group.school }}</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="lesson in group.lessons"
                            :key="lesson.id"
                            class="flex items-center gap-3 rounded-xl border border-blue bg-blue/5 px-4 py-3"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-text-base">
                                    {{ resolveSubjectLabel(lesson.subject!.name, lesson.lm_level) }}
                                </span>
                                <span class="block text-xs text-stone-500">
                                    {{ lesson.group!.grade }}{{ lesson.group!.name }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </ProfileSection>

        <ProfileSection
            v-if="!auth.isPureAdmin"
            title="Matières enseignées"
            description="Les matières que vous pouvez enseigner."
        >
            <SubjectPicker :subjects="allSubjects" :model-value="userSubjectIds" />
        </ProfileSection>

        <ProfileSection v-if="!auth.isPureAdmin" title="Établissements">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-stone-500">Rattachements actifs</p>
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="school in auth.schoolRoles"
                        :key="school.id"
                        class="flex items-center gap-3 rounded-xl border border-blue bg-blue/5 px-4 py-3 text-sm font-medium text-text-base"
                    >
                        <span class="size-2 shrink-0 rounded-full bg-blue" aria-hidden="true" />
                        {{ school.name }}
                    </li>
                    <li v-if="auth.schoolRoles.length === 0" class="text-sm text-border-figma">
                        Aucun établissement actif.
                    </li>
                </ul>
            </div>

            <div v-if="pendingRequests.length > 0">
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-stone-500">Demandes en attente</p>
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="req in pendingRequests"
                        :key="req.id"
                        class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200 px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <Badge variant="neutral">En attente</Badge>
                            <span class="text-sm font-medium text-text-base">{{ req.school.name }}</span>
                        </div>
                        <button
                            type="button"
                            class="text-xs text-stone-400 transition-colors hover:text-pink"
                            @click="cancelRequest(req.id)"
                        >
                            Annuler
                        </button>
                    </li>
                </ul>
            </div>

            <div v-if="availableSchools.length > 0 || addingSchool">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-stone-500">Postuler à un établissement</p>
                    <button
                        v-if="!addingSchool"
                        type="button"
                        class="text-sm font-medium text-blue hover:underline"
                        @click="addingSchool = true"
                    >
                        + Ajouter
                    </button>
                </div>
                <div v-if="addingSchool" class="mt-3">
                    <SchoolJoinForm
                        :schools="availableSchools"
                        @cancel="addingSchool = false"
                        @success="addingSchool = false"
                    />
                </div>
            </div>
        </ProfileSection>

        <template v-if="auth.adminSchools.length > 0 && scheduleSlots.length > 0">
            <ProfileSection
                v-for="school in auth.adminSchools"
                :key="school.id"
                :title="`Horaires — ${school.name}`"
                description="Définissez l'heure de début et de fin de chaque créneau."
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-bold uppercase text-stone-400">
                                <th scope="col" class="pb-3 pr-6">Créneau</th>
                                <th scope="col" class="pb-3 pr-4">Début</th>
                                <th scope="col" class="pb-3">Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in slotTimeForms[school.id]" :key="row.slot_id">
                                <th scope="row" class="py-1.5 pr-6 text-left text-sm font-medium text-text-base">
                                    {{ scheduleSlots[i].label }}
                                </th>
                                <td class="py-1.5 pr-4">
                                    <InputLabel
                                        v-model="row.start_time"
                                        type="time"
                                        size="sm"
                                        :fluid="false"
                                        :min="i > 0 ? slotTimeForms[school.id][i - 1].end_time : undefined"
                                        :error="slotTimeErrors[school.id]?.[i] ?? ''"
                                    />
                                </td>
                                <td class="py-1.5">
                                    <InputLabel v-model="row.end_time" type="time" size="sm" :fluid="false" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end">
                    <Button variant="primary" size="sm" label="Enregistrer" @click="saveSlotTimes(school.slug, school.id)" />
                </div>
            </ProfileSection>
        </template>

        <div class="flex flex-col gap-8 lg:flex-row lg:items-start">
            <ProfileSection title="Changer de mot de passe" class="flex-3">
                <div class="flex flex-col items-end gap-5">
                    <InputLabel
                        v-model="passwordForm.current_password"
                        label="Mot de passe actuel"
                        type="password"
                        placeholder="••••••••••"
                        autocomplete="current-password"
                        :error="passwordForm.errors.current_password"
                        class="w-full"
                    />
                    <div class="flex w-full flex-col gap-5 sm:flex-row">
                        <InputLabel
                            v-model="passwordForm.password"
                            label="Nouveau mot de passe"
                            type="password"
                            placeholder="•••••••"
                            autocomplete="new-password"
                            :error="passwordForm.errors.password"
                            class="flex-1"
                        />
                        <InputLabel
                            v-model="passwordForm.password_confirmation"
                            label="Confirmer le mot de passe"
                            type="password"
                            placeholder="••••••••••"
                            autocomplete="new-password"
                            :error="passwordForm.errors.password_confirmation"
                            class="flex-1"
                        />
                    </div>
                    <Button
                        variant="primary"
                        size="sm"
                        label="Enregistrer"
                        :loading="passwordForm.processing"
                        @click="submitPassword"
                    />
                </div>
            </ProfileSection>

            <ProfileSection
                title="Supprimer le compte"
                variant="danger"
                class="flex-2 justify-between lg:min-h-80"
            >
                <p class="text-sm text-text-base">
                    Une fois le compte supprimé, il vous sera impossible de récupérer les données.
                    Soyez bien sûr que vous voulez supprimer ce compte.
                </p>
                <div class="flex justify-end">
                    <Button
                        variant="danger"
                        size="sm"
                        label="Supprimer mon compte"
                        :loading="deleteForm.processing"
                        @click="showDeleteModal = true"
                    />
                </div>
            </ProfileSection>
        </div>

    </div>

    <ConfirmModal
        :open="showDeleteModal"
        title="Supprimer mon compte"
        message="Cette action est irréversible. Toutes vos données seront définitivement supprimées."
        confirm-label="Supprimer mon compte"
        @confirm="confirmDelete"
        @cancel="showDeleteModal = false"
    />
</template>
