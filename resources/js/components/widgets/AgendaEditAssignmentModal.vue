<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { AgendaAssignment } from '@/components/widgets/AgendaAssignmentRow.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import DateField from '@/components/widgets/DateField.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import KbdShortcut from '@/components/widgets/KbdShortcut.vue';
import { useSaveShortcut } from '@/composables/useSaveShortcut';
import { update as updateAssignment } from '@/routes/assignments';
import { useToasterStore } from '@/stores/toaster';

const today = new Date().toISOString().slice(0, 10);
const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const editing = ref<AgendaAssignment | null>(null);
const toaster = useToasterStore();

const form = useForm({
    type: 'homework' as 'homework' | 'test',
    title: '',
    scheduled_date: '',
    description: '',
});

function open(assignment: AgendaAssignment) {
    if (assignment.scheduled_date < today) {
        return;
    }

    editing.value = assignment;
    form.type = assignment.type;
    form.title = assignment.title;
    form.scheduled_date = assignment.scheduled_date;
    form.description = assignment.description ?? '';
    modalRef.value?.open();
}

function submit() {
    if (!editing.value) {
        return;
    }

    form.patch(updateAssignment.url({ assignment: editing.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            modalRef.value?.close();
            toaster.success('Devoir modifié');
        },
    });
}

useSaveShortcut(() => {
    if (editing.value) {
        submit();
    }
});

defineExpose({ open });
</script>

<template>
    <BaseModal ref="modalRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">Modifier</h2>
                <p v-if="editing" class="mt-0.5 text-xs text-stone-400">
                    {{ editing.subject }} · {{ editing.group }}
                </p>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
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
                </div>

                <InputLabel v-model="form.title" label="Titre" placeholder="Ex : Chapitre 3 – exercices" />

                <DateField
                    label="Date"
                    :model-value="form.scheduled_date"
                    @update:model-value="form.scheduled_date = $event"
                />

                <div class="flex flex-col gap-1.5">
                    <label class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase">
                        Description
                        <span class="font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Précisions…"
                        class="w-full resize-none rounded-2xl border border-border-figma bg-white px-3 py-3 font-manrope text-sm text-text-base transition-all duration-150 outline-none placeholder:font-normal placeholder:text-gray-400 focus:border-blue focus:ring-2 focus:ring-blue/20"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button type="button" variant="ghost" size="sm" @click="modalRef?.close()">Annuler</Button>
                    <Button type="submit" variant="primary" size="sm" :loading="form.processing">
                        Enregistrer <KbdShortcut keys="⌘S" />
                    </Button>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
