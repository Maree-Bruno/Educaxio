<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import ProfileAvatarPicker from '@/components/settings/ProfileAvatarPicker.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import UserAvatar from '@/components/widgets/UserAvatar.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import Edit from '@/components/widgets/svg/Edit.vue';
import { update as updateStudent } from '@/routes/students';
import { update as adminUpdateStudent } from '@/routes/admin/students';
import { useToasterStore } from '@/stores/toaster';
import type { Student } from '@/types';

type StudentWithSchool = Student & { school?: { id: number; name: string; slug: string } };

const props = defineProps<{
    student: StudentWithSchool;
    isAdmin: boolean;
    fullName: string;
}>();

const toaster = useToasterStore();
const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);

const form = useForm({
    lastname:  props.student.lastname,
    firstname: props.student.firstname,
    email:     props.student.email ?? '',
    picture:   null as File | null,
});

function openEdit() {
    form.lastname  = props.student.lastname;
    form.firstname = props.student.firstname;
    form.email     = props.student.email ?? '';
    form.picture   = null;
    form.clearErrors();
    nextTick(() => modalRef.value?.open());
}

function save() {
    const isAdmin = props.isAdmin && !!props.student.school?.slug;
    const url = isAdmin
        ? adminUpdateStudent.url({ school: props.student.school!.slug, student: props.student.slug })
        : updateStudent.url({ student: props.student.slug });

    if (isAdmin) {
        form.transform((d) => ({ ...d, email: d.email || null, _method: 'patch' }))
            .post(url, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    modalRef.value?.close();
                    toaster.success('Élève mis à jour');
                },
            });
    } else {
        form.transform((d) => ({ ...d, email: d.email || null }))
            .patch(url, {
                preserveScroll: true,
                onSuccess: () => modalRef.value?.close(),
            });
    }
}
</script>

<template>
    <div class="min-w-0 flex-1 overflow-hidden rounded-2xl bg-white">
        <div class="flex items-center justify-between border-b border-neutral-300/10 px-6 py-5">
            <div class="flex items-center gap-4">
                <UserAvatar
                    :name="fullName"
                    :picture="student.picture ?? null"
                    type="student"
                    image-size="sm"
                    class="size-12 shrink-0 text-lg"
                />
                <h2 class="text-xl font-bold text-text-base">{{ fullName }}</h2>
            </div>
            <Button
                v-if="isAdmin"
                variant="secondary"
                size="sm"
                :icon-only="true"
                title="Modifier l'élève"
                @click="openEdit"
            >
                <template #icon>
                    <Edit :size="16" :stroke-width="2" aria-hidden="true" />
                </template>
            </Button>
        </div>

        <dl class="divide-y divide-neutral-100 px-6">
            <div class="flex items-center justify-between py-4">
                <dt class="text-xs font-bold uppercase tracking-wider text-stone-500">Nom</dt>
                <dd class="text-sm font-medium text-text-base">{{ student.lastname }}</dd>
            </div>
            <div class="flex items-center justify-between py-4">
                <dt class="text-xs font-bold uppercase tracking-wider text-stone-500">Prénom</dt>
                <dd class="text-sm font-medium text-text-base">{{ student.firstname }}</dd>
            </div>
            <div class="flex items-center justify-between py-4">
                <dt class="text-xs font-bold uppercase tracking-wider text-stone-500">Email</dt>
                <dd class="text-sm text-text-base">
                    <a
                        v-if="student.email"
                        :href="`mailto:${student.email}`"
                        class="text-blue hover:underline"
                    >
                        {{ student.email }}
                    </a>
                    <span v-else class="text-border-figma">—</span>
                </dd>
            </div>
        </dl>
    </div>

    <BaseModal ref="modalRef">
        <form class="flex flex-col gap-5" @submit.prevent="save">
            <h2 class="text-xl font-bold text-black">Modifier l'élève</h2>
            <div v-if="isAdmin" class="flex justify-center">
                <ProfileAvatarPicker
                    :current-picture="student.picture ?? null"
                    :name="fullName"
                    @update:picture="form.picture = $event"
                />
            </div>
            <InputLabel v-model="form.lastname"  label="Nom"     placeholder="Dupont" :error="form.errors.lastname" />
            <InputLabel v-model="form.firstname" label="Prénom"  placeholder="Marie"  :error="form.errors.firstname" />
            <InputLabel v-model="form.email" type="email" label="Email (optionnel)" placeholder="marie@exemple.be" :error="form.errors.email" />
            <div class="flex gap-3">
                <Button type="submit" variant="primary" size="sm" label="Enregistrer" class="flex-1" :disabled="!form.lastname || !form.firstname" :loading="form.processing" />
                <Button type="button" variant="danger"  size="sm" label="Annuler"     class="flex-1" @click="modalRef?.close()" />
            </div>
        </form>
    </BaseModal>
</template>
