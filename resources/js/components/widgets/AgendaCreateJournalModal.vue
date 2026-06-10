<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import DateField from '@/components/widgets/DateField.vue';
import KbdShortcut from '@/components/widgets/KbdShortcut.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { useSaveShortcut } from '@/composables/useSaveShortcut';
import { store } from '@/routes/lesson-notes';
import { useToasterStore } from '@/stores/toaster';

interface AgendaLessonOption {
    id: number;
    label: string;
}

const props = defineProps<{
    lessonOptions: AgendaLessonOption[];
}>();

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const isOpen = ref(false);
const toaster = useToasterStore();

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    lesson_id: null as number | null,
    date: today,
    notes: '',
});

const lessonSelectOptions = computed(() =>
    props.lessonOptions.map((l) => ({ value: l.id, label: l.label })),
);

function open() {
    isOpen.value = true;
    form.reset();
    form.date = new Date().toISOString().slice(0, 10);
    modalRef.value?.open();
}

function closeModal() {
    isOpen.value = false;
    modalRef.value?.close();
}

function submit() {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            toaster.success('Journal sauvegardé');
        },
    });
}

useSaveShortcut(() => {
    if (isOpen.value) {
        submit();
    }
});

defineExpose({ open });
</script>

<template>
    <BaseModal ref="modalRef">
        <div class="flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-stone-900">Nouvelle entrée de journal</h2>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="flex flex-col gap-1.5">
                    <SelectField
                        label="Cours"
                        placeholder="Choisir un cours…"
                        :options="lessonSelectOptions"
                        :model-value="form.lesson_id"
                        @update:model-value="(v) => (form.lesson_id = v as number | null)"
                    />
                    <p v-if="form.errors.lesson_id" class="font-manrope text-sm font-medium text-pink">{{ form.errors.lesson_id }}</p>
                </div>

                <div class="flex flex-col gap-1">
                    <DateField
                        label="Date"
                        :model-value="form.date"
                        @update:model-value="form.date = $event"
                    />
                    <p v-if="form.errors.date" class="font-manrope text-sm font-medium text-pink">{{ form.errors.date }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="font-manrope text-xs leading-4 font-bold tracking-widest text-border-figma uppercase">
                        Notes <span class="font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="5"
                        placeholder="Notes du cours…"
                        class="w-full resize-none rounded-2xl border bg-white px-3 py-3 font-manrope text-sm text-text-base transition-all duration-150 outline-none placeholder:font-normal placeholder:text-gray-400"
                        :class="form.errors.notes ? 'border-border-figma focus:border-pink focus:ring-2 focus:ring-pink/20' : 'border-border-figma focus:border-blue focus:ring-2 focus:ring-blue/20'"
                    />
                    <p v-if="form.errors.notes" class="font-manrope text-sm font-medium text-pink">{{ form.errors.notes }}</p>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button type="button" variant="ghost" size="sm" @click="closeModal">Annuler</Button>
                    <Button type="submit" variant="primary" size="sm" :loading="form.processing">
                        Enregistrer <KbdShortcut keys="⌘S" />
                    </Button>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
