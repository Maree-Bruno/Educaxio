<script setup lang="ts">
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';

export interface SelectOption {
    value: string | number;
    label: string;
}

const props = withDefaults(
    defineProps<{
        label?: string;
        placeholder?: string;
        options: SelectOption[];
        modelValue?: string | number | null;
        id?: string;
    }>(),
    {
        label: '',
        placeholder: 'Sélectionner…',
        modelValue: null,
        id: undefined,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

function onChange(event: Event) {
    const val = (event.target as HTMLSelectElement).value;
    emit('update:modelValue', val === '' ? null : val);
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <label
            v-if="label"
            :for="id"
            class="font-manrope text-xs leading-4 font-bold tracking-wide text-border-figma uppercase"
        >
            {{ label }}
        </label>

        <div class="relative">
            <select
                :id="id"
                :value="modelValue ?? ''"
                :class="modelValue != null && modelValue !== '' ? 'text-text-base' : 'text-border-figma'"
                class="w-full cursor-pointer appearance-none rounded-2xl bg-white px-3 py-3 font-manrope text-sm leading-5 font-bold outline-1 -outline-offset-1 outline-border-figma transition-colors focus:ring-0 focus:outline-2 focus:outline-border-figma focus-visible:outline-none"
                @change="onChange"
            >
                <option value="">{{ placeholder }}</option>
                <option
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>

            <span
                class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-border-figma truncate"
                aria-hidden="true"
            >
                <ChevronDown :size="20" :stroke-width="2" />
            </span>
        </div>
    </div>
</template>
