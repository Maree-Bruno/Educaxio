<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Button from '@/components/widgets/Button.vue';
import ConfirmModal from '@/components/widgets/ConfirmModal.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import { useImagePreview } from '@/composables/useImagePreview';
import { setPageTitle } from '@/composables/usePageTitle';
import { useUserHelpers } from '@/composables/useUserHelpers';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Lesson } from '@/types';

defineOptions({ layout: AppLayout });

setPageTitle('Profil');

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    userLessonIds: number[];
    availableLessons: Lesson[];
};
const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);

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

const lessonsForm = useForm({
    lesson_ids: [...props.userLessonIds],
});

function toggleLesson(id: number) {
    const idx = lessonsForm.lesson_ids.indexOf(id);

    if (idx === -1) {
        lessonsForm.lesson_ids.push(id);
    } else {
        lessonsForm.lesson_ids.splice(idx, 1);
    }
}

function submitLessons() {
    lessonsForm.put('/settings/lessons', { preserveScroll: true });
}

const lessonsBySchool = computed(() => {
    const map = new Map<string, Lesson[]>();

    for (const lesson of props.availableLessons) {
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

        <!-- Informations personnelles -->
        <section class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm outline outline-1 -outline-offset-1 outline-neutral-300/10">
            <h2 class="text-base font-bold text-text-base">Informations personnelles</h2>

            <div class="flex gap-5">
                <!-- Avatar -->
                <div class="shrink-0">
                    <div class="relative">
                        <img
                            v-if="hasImage"
                            :src="currentImageSrc"
                            :srcset="currentImageSrcset || undefined"
                            sizes="(max-width: 640px) 128px, 176px"
                            class="size-44 rounded-full object-cover"
                            alt="Photo de profil"
                        />
                        <div
                            v-else
                            class="flex size-44 items-center justify-center rounded-full bg-neutral-100 text-4xl font-bold text-neutral-400"
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

                <!-- Champs + bouton -->
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

        <!-- Mes cours -->
        <section class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm outline outline-1 -outline-offset-1 outline-neutral-300/10">
            <h2 class="text-base font-bold text-text-base">Mes cours</h2>

            <div v-if="availableLessons.length === 0" class="py-4 text-sm text-border-figma">
                Aucun cours disponible dans vos établissements.
            </div>

            <div v-else class="flex flex-col gap-6">
                <div
                    v-for="group in lessonsBySchool"
                    :key="group.school"
                    class="flex flex-col gap-3"
                >
                    <p class="text-xs font-bold tracking-wider text-stone-500 uppercase">
                        {{ group.school }}
                    </p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="lesson in group.lessons"
                            :key="lesson.id"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-neutral-200 px-4 py-3 transition-colors hover:bg-gray-50"
                            :class="{ 'border-blue bg-blue/5': lessonsForm.lesson_ids.includes(lesson.id) }"
                        >
                            <input
                                type="checkbox"
                                class="size-4 shrink-0 accent-blue"
                                :checked="lessonsForm.lesson_ids.includes(lesson.id)"
                                @change="toggleLesson(lesson.id)"
                            />
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-text-base">
                                    {{ lesson.subject?.name ?? lesson.name }}
                                </span>
                                <span class="block text-xs text-stone-500">
                                    {{ lesson.group!.grade }}{{ lesson.group!.name }}
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <Button
                    variant="primary"
                    size="sm"
                    label="Enregistrer"
                    :loading="lessonsForm.processing"
                    :disabled="availableLessons.length === 0"
                    @click="submitLessons"
                />
            </div>
        </section>

        <!-- Ligne du bas -->
        <div class="flex items-start gap-8">

            <!-- Changer de mot de passe -->
            <section class="flex flex-[3] flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm outline outline-1 -outline-offset-1 outline-neutral-300/10">
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
                    <div class="flex w-full gap-5">
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

            <!-- Supprimer le compte -->
            <section class="flex flex-[2] flex-col justify-between gap-5 rounded-2xl bg-pink/10 p-6 shadow-sm outline outline-1 -outline-offset-1 outline-neutral-300/10" style="min-height: 320px;">
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
