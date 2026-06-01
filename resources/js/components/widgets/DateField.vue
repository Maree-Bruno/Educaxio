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
            class="font-manrope text-xs font-bold uppercase leading-4 tracking-widest text-border-figma"
        >
            {{ label }}
        </label>
        <input
            :id="id"
            type="date"
            :value="modelValue"
            :max="max"
            :min="min"
            class="w-full rounded-2xl border border-border-figma bg-white px-3 py-3 font-manrope text-sm font-semibold text-text-base outline-none transition-all duration-150 focus:border-blue focus:ring-2 focus:ring-blue/20"
            @change="onChange"
        />
    </div>
</template>