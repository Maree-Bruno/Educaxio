<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        placeholder?: string;
        modelValue?: string;
        id?: string;
    }>(),
    {
        label: '',
        placeholder: 'Rechercher…',
        modelValue: '',
        id: undefined,
    },
);

defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();
</script>

<template>
    <div class="flex flex-col gap-2">
        <label
            v-if="label"
            :for="id"
            class="text-xs font-bold uppercase leading-4 tracking-wide text-border-figma font-manrope"
        >
            {{ label }}
        </label>

        <input
            :id="id"
            type="text"
            :value="modelValue"
            :placeholder="placeholder"
            class="rounded-2xl bg-white px-3 py-3 text-sm font-bold leading-5 text-text-base outline-1 -outline-offset-1 outline-zinc-800/50 transition-colors placeholder:text-border-figma focus:outline-2 focus:outline-border-figma focus:ring-0 font-manrope"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
    </div>
</template>
