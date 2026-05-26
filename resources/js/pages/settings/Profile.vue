<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Badge from '@/components/widgets/Badge.vue';
import Button from '@/components/widgets/Button.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import SchoolJoinForm from '@/components/widgets/SchoolJoinForm.vue';
import SubjectPicker from '@/components/widgets/SubjectPicker.vue';
import { useImagePreview } from '@/composables/useImagePreview';
import { setPageTitle } from '@/composables/usePageTitle';
import { useUserHelpers } from '@/composables/useUserHelpers';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import type { Lesson } from '@/types';

defineOptions({ layout: AppLayout });

setPageTitle('Profil');

type Props = {
    assignedLessons: Lesson[];
    allSubjects: { id: number; name: string }[];
    userSubjectIds: number[];
    pendingRequests: { id: number; school: { id: number; name: string } }[];
    availableSchools: { id: number; name: string }[];
};
const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const auth = useAuthStore();

const { previewUrl, handleSingleImage, removeSinglePreview } = useImagePreview();
const { getUserImageUrl, getUserImageSrcset } = useUserHelpers();

const fileInputRef = ref<HTMLInputElement | null>(null);

const profileForm = useForm({
    name: user.value.name ?? '',
    email: user.value.email ?? '',
    picture: null as File | null,
});

const handlePicture = (event: Event) => {
    const file = handleSingleImage(event);

    if (file) {
        profileForm.picture = file;
    }
};

const removePicture = () => {
    removeSinglePreview();
    profileForm.picture = null;

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const currentImageSrc = computed(() => {
    if (previewUrl.value) {
        return previewUrl.value;
    }

    return getUserImageUrl(user.value.picture, 'md');
});

const currentImageSrcset = computed(() => {
    if (previewUrl.value || !user.value.picture) {
        return '';
    }

    return getUserImageSrcset(user.value.picture);
});

const hasImage = computed(() => !!(previewUrl.value || user.value.picture));

function submitProfile() {
    profileForm.patch('/settings/profile', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => removeSinglePreview(),
    });
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
        const schoolName = lesson.group!.school.name;
        const entry = map.get(schoolName) ?? [];
        entry.push(lesson);
        map.set(schoolName, entry);
    }

    return [...map.entries()].map(([school, lessons]) => ({ school, lessons }));
});

const showDeleteModal = ref(false);
const deleteForm = useForm({});

function confirmDelete() {
    deleteForm.delete('/settings/profile');
}
</script>

<template>
    <div class="flex flex-col gap-8">

        <section class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm outline-1 -outline-offset-1 outline-neutral-300/10">
            <h2 class="text-base font-bold text-text-base">Informations personnelles</h2>

            <div class="flex flex-col gap-5 sm:flex-row">
                <div class="shrink-0 self-start">
                    <div class="relative">
                        <img
                            v-if="hasImage"
                            :src="currentImageSrc"
                            :srcset="currentImageSrcset || undefined"
                            sizes="(max-width: 640px) 112px, 176px"
                            class="size-28 rounded-full object-cover sm:size-44"
                            alt="Photo de profil"
                        />
                        <div
                            v-else
                            class="flex size-28 items-center justify-center rounded-full bg-neutral-100 text-3xl font-bold text-neutral-400 sm:size-44 sm:text-4xl"
                        >
                            {{ user.name?.charAt(0).toUpperCase() }}
                        </div>

                        <button
                            v-if="previewUrl"
                            type="button"
                            class="absolute right-1 top-1 flex size-6 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600"
                            @click="removePicture"
                        >
                            ×
                        </button>
                    </div>

                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/png,image/jpeg,image/jpg,image/webp"
                        class="hidden"
                        @change="handlePicture"
                    />
                    <button
                        type="button"
                        class="mt-3 w-full rounded-xl border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-text-base transition-colors hover:bg-gray-50"
                        @click="fileInputRef?.click()"
                    >
                        Changer la photo
                    </button>
                </div>

                <div class="flex flex-1 flex-col items-end gap-5">
                    <InputLabel
                        v-model="profileForm.name"
                        label="Nom d'utilisateur"
                        :placeholder="user.name"
                        :error="profileForm.errors.name"
                        class="w-full"
                    />
                    <InputLabel
                        v-model="profileForm.email"
                        label="Adresse email"
                        type="email"
                        :placeholder="user.email"
                        :error="profileForm.errors.email"
                        class="w-full"
                    />
                    <Button
                        variant="primary"
                        size="sm"
                        label="Enregistrer"
                        :loading="profileForm.processing"
                        @click="submitProfile"
                    />
                </div>
            </div>
        </section>

        <section
            v-if="auth.isTeacher"
            class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm outline-1 -outline-offset-1 outline-neutral-300/10"
        >
            <h2 class="text-base font-bold text-text-base">Mes cours</h2>

            <div v-if="assignedLessons.length === 0" class="py-4 text-sm text-border-figma">
                Aucun cours ne vous a encore été attribué.
            </div>

            <div v-else class="flex flex-col gap-6">
                <div
                    v-for="group in lessonsBySchool"
                    :key="group.school"
                    class="flex flex-col gap-3"
                >
                    <p class="text-xs font-bold uppercase tracking-wider text-stone-500">
                        {{ group.school }}
                    </p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="lesson in group.lessons"
                            :key="lesson.id"
                            class="flex items-center gap-3 rounded-xl border border-blue bg-blue/5 px-4 py-3"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-text-base">
                                    {{ lesson.subject?.name ?? lesson.name }}
                                </span>
                                <span class="block text-xs text-stone-500">
                                    {{ lesson.group!.grade }}{{ lesson.group!.name }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="!auth.isPureAdmin" class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm outline-1 -outline-offset-1 outline-neutral-300/10">
            <div>
                <h2 class="text-base font-bold text-text-base">Matières enseignées</h2>
                <p class="mt-0.5 text-sm text-stone-400">Les matières que vous pouvez enseigner.</p>
            </div>
            <SubjectPicker :subjects="allSubjects" :model-value="userSubjectIds" />
        </section>

        <section v-if="!auth.isPureAdmin" class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm outline-1 -outline-offset-1 outline-neutral-300/10">
            <h2 class="text-base font-bold text-text-base">Établissements</h2>

            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-stone-500">Rattachements actifs</p>
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="school in auth.schoolRoles"
                        :key="school.id"
                        class="flex items-center gap-3 rounded-xl border border-blue bg-blue/5 px-4 py-3 text-sm font-medium text-text-base"
                    >
                        <span class="size-2 shrink-0 rounded-full bg-blue" />
                        {{ school.name }}
                    </li>
                    <li v-if="auth.schoolRoles.length === 0" class="text-sm text-border-figma">Aucun établissement actif.</li>
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
        </section>

        <div class="flex flex-col gap-8 lg:flex-row lg:items-start">

            <section class="flex flex-3 flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm outline-1 -outline-offset-1 outline-neutral-300/10">
                <h2 class="text-base font-bold text-text-base">Changer de mot de passe</h2>

                <div class="flex flex-col items-end gap-5">
                    <InputLabel
                        v-model="passwordForm.current_password"
                        label="Mot de passe actuel"
                        type="password"
                        placeholder="••••••••••"
                        :error="passwordForm.errors.current_password"
                        class="w-full"
                    />
                    <div class="flex w-full flex-col gap-5 sm:flex-row">
                        <InputLabel
                            v-model="passwordForm.password"
                            label="Nouveau mot de passe"
                            type="password"
                            placeholder="•••••••"
                            :error="passwordForm.errors.password"
                            class="flex-1"
                        />
                        <InputLabel
                            v-model="passwordForm.password_confirmation"
                            label="Confirmer le mot de passe"
                            type="password"
                            placeholder="••••••••••"
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
            </section>

            <section class="flex flex-col justify-between gap-5 rounded-2xl bg-pink/10 p-6 shadow-sm outline-1 -outline-offset-1 outline-neutral-300/10 lg:flex-2 lg:min-h-80">
                <div class="flex flex-col gap-5">
                    <h2 class="text-base font-bold text-text-base">Supprimer le compte</h2>
                    <p class="text-sm text-text-base">
                        Une fois le compte supprimé, il vous sera impossible de récupérer les données.
                        Soyez bien sûr que vous voulez supprimer ce compte.
                    </p>
                </div>
                <div class="flex justify-end">
                    <Button
                        variant="danger"
                        size="sm"
                        label="Supprimer mon compte"
                        :loading="deleteForm.processing"
                        @click="showDeleteModal = true"
                    />
                </div>
            </section>
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
