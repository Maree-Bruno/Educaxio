<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from './Button.vue';
import SubjectGrid from './SubjectGrid.vue';
import { useToasterStore } from '@/stores/toaster';

const props = defineProps<{
    subjects:   { id: number; name: string }[];
    modelValue: number[];
}>();

const form    = useForm({ subject_ids: [...props.modelValue] as number[] });
const dirty   = ref(false);
const toaster = useToasterStore();

function onUpdate(ids: number[]) {
    form.subject_ids = ids;
    dirty.value = true;
}

function save() {
    form.patch('/pending/subjects', {
        onSuccess: () => {
            dirty.value = false;
            toaster.success('Matières enregistrées');
        },
    });
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <SubjectGrid :subjects="subjects" :model-value="form.subject_ids" @update:model-value="onUpdate" />
        <div class="flex justify-end">
            <Button variant="primary" size="sm" label="Enregistrer" :disabled="!dirty" :loading="form.processing" @click="save" />
        </div>
    </div>
</template>
