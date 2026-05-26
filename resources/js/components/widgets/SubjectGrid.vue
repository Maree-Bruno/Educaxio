<script setup lang="ts">
const props = defineProps<{
    subjects:   { id: number; name: string }[];
    modelValue: number[];
}>();

const emit = defineEmits<{ 'update:modelValue': [value: number[]] }>();

function toggle(id: number) {
    const next = [...props.modelValue];
    const idx = next.indexOf(id);

    if (idx === -1) {
        next.push(id);
    } else {
        next.splice(idx, 1);
    }

    emit('update:modelValue', next);
}
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button
            v-for="subject in subjects"
            :key="subject.id"
            type="button"
            :class="[
                'rounded-full border px-3.5 py-2 text-sm font-medium transition-all duration-150',
                modelValue.includes(subject.id)
                    ? 'border-blue bg-blue text-white'
                    : 'border-border-figma bg-white text-text-base hover:border-blue',
            ]"
            @click="toggle(subject.id)"
        >
            {{ subject.name }}
        </button>
    </div>
</template>
