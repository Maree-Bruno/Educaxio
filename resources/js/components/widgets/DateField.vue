<script setup lang="ts">
withDefaults(defineProps<{
    label?: string;
    modelValue: string;
    max?: string;
    min?: string;
    id?: string;
}>(), {
    label: '',
    max: undefined,
    min: undefined,
    id: undefined,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

function onChange(event: Event) {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <label
            v-if="label"
            :for="id"
            class="text-xs font-bold uppercase leading-4 tracking-wide text-border-figma"
        >
            {{ label }}
        </label>
        <input
            :id="id"
            type="date"
            :value="modelValue"
            :max="max"
            :min="min"
            class="rounded-2xl bg-white px-3 py-3 text-sm font-bold text-text-base outline -outline-offset-1 outline-border-figma focus:outline-blue"
            @change="onChange"
        />
    </div>
</template>