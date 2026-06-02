<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import Edit from '@/components/widgets/svg/Edit.vue';
import { update as updateStudent } from '@/routes/students';
import type { Student } from '@/types';

type StudentWithSchool = Student & { school?: { id: number; name: string; slug: string } };

const props = defineProps<{
    student: StudentWithSchool;
    isAdmin: boolean;
    fullName: string;
}>();

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const form = useForm({
    lastname:  props.student.lastname,
    firstname: props.student.firstname,
    email:     props.student.email ?? '',
});

function openEdit() {
    form.lastname  = props.student.lastname;
    form.firstname = props.student.firstname;
    form.email     = props.student.email ?? '';
    form.clearErrors();
    nextTick(() => modalRef.value?.open());
}

function save() {
    form.transform((data) => ({ ...data, email: data.email || null }))
        .patch(updateStudent.url({ student: props.student.slug }), {
            preserveScroll: true,
            onSuccess: () => modalRef.value?.close(),
        });
}
</script>

<template>
    <div class="min-w-0 flex-1 overflow-hidden rounded-2xl bg-white">
        <div class="flex items-center justify-between border-b border-neutral-300/10 px-6 py-5">
            <h2 class="text-xl font-bold text-text-base">{{ fullName }}</h2>
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
            <InputLabel v-model="form.lastname"  label="Nom"              placeholder="Dupont"          :error="form.errors.lastname" />
            <InputLabel v-model="form.firstname" label="Prénom"           placeholder="Marie"           :error="form.errors.firstname" />
            <InputLabel v-model="form.email" type="email" label="Email (optionnel)" placeholder="marie@exemple.be" :error="form.errors.email" />
            <div class="flex gap-3">
                <Button type="submit"  variant="primary" size="sm" label="Enregistrer" class="flex-1" :disabled="!form.lastname || !form.firstname" :loading="form.processing" />
                <Button type="button"  variant="danger"  size="sm" label="Annuler"     class="flex-1" @click="modalRef?.close()" />
            </div>
        </form>
    </BaseModal>
</template>