<script lang="ts" setup>
import { computed, useId } from 'vue';

type InputType = 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'time';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        label?: string;
        placeholder?: string;
        error?: string;
        disabled?: boolean;
        required?: boolean;
        type?: InputType;
        id?: string;
        size?: 'sm' | 'md';
        fluid?: boolean;
        autocomplete?: string;
        min?: string;
    }>(),
    {
        modelValue: '',
        label: '',
        placeholder: '',
        error: '',
        disabled: false,
        required: false,
        type: 'text',
        size: 'md',
        fluid: true,
        autocomplete: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const inputId = props.id ?? useId();

const isFilled = computed(() => props.modelValue.length > 0);
const hasError = computed(() => props.error.length > 0);

const inputClasses = computed(() => [
    props.fluid ? 'w-full' : 'w-auto',
    'rounded-2xl border bg-white font-manrope outline-none transition-all duration-150',
    'placeholder:text-gray-400 placeholder:font-normal',
    'disabled:cursor-not-allowed disabled:opacity-50',
    hasError.value
        ? 'border-border-figma focus:border-pink focus:ring-2 focus:ring-pink/20'
        : 'border-border-figma focus:border-blue focus:ring-2 focus:ring-blue/20',
    isFilled.value ? 'font-semibold text-text-base' : 'font-normal',
    props.size === 'sm' ? 'px-3 py-2.5 text-sm' : 'px-3 py-3 text-base',
]);
</script>

<template>
    <div :class="['flex flex-col gap-1.5', fluid ? 'w-full' : 'w-fit']">
        <label
            v-if="label"
            :for="inputId"
            class="font-manrope text-xs font-bold uppercase tracking-widest text-border-figma leading-4"
        >
            {{ label }}
            <span v-if="required" class="ml-0.5 text-pink" aria-hidden="true">*</span>
        </label>

        <input
            :id="inputId"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required || undefined"
            :autocomplete="autocomplete"
            :min="min"
            :class="inputClasses"
            :aria-invalid="hasError"
            :aria-required="required ? 'true' : undefined"
            :aria-describedby="hasError ? `${inputId}-error` : undefined"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />

        <p
            v-if="hasError"
            :id="`${inputId}-error`"
            class="font-manrope text-sm font-medium text-pink sm:text-base"
        >
            {{ error }}
        </p>
    </div>
</template>
