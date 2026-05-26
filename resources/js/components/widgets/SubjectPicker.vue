<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from './Button.vue';
import SubjectGrid from './SubjectGrid.vue';

const props = defineProps<{
    subjects:   { id: number; name: string }[];
    modelValue: number[];
}>();

const selected = ref<number[]>([...props.modelValue]);
const dirty = ref(false);

function onUpdate(ids: number[]) {
    selected.value = ids;
    dirty.value = true;
}

function save() {
    router.patch('/pending/subjects', { subject_ids: selected.value }, {
        onSuccess: () => {
            dirty.value = false;
        },
    });
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <SubjectGrid :subjects="subjects" :model-value="selected" @update:model-value="onUpdate" />
        <div class="flex justify-end">
            <Button variant="primary" size="sm" label="Enregistrer" :disabled="!dirty" @click="save" />
        </div>
    </div>
</template>
