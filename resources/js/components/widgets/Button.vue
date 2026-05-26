<script lang="ts" setup>
import { computed } from 'vue';

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost';
type Size = 'sm' | 'md' | 'lg' | 'xs';
type ButtonType = 'button' | 'submit' | 'reset';

const props = withDefaults(
    defineProps<{
        variant?: Variant;
        size?: Size;
        label?: string;
        disabled?: boolean;
        loading?: boolean;
        iconOnly?: boolean;
        type?: ButtonType;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        label: '',
        disabled: false,
        loading: false,
        iconOnly: false,
        type: 'button',
    },
);
const emit = defineEmits(['click']);

function handleClick(event: MouseEvent): void {
    if (!props.disabled && !props.loading) {
        emit('click', event);
    }
}

const variantClasses = computed(
    () =>
        ({
            primary:
                'bg-blue text-white hover:opacity-85 focus-visible:ring-blue hover:bg-blue-hover',
            secondary:
                'bg-orange text-white hover:opacity-85 focus-visible:ring-orange hover:bg-orange-hover',
            danger:  'bg-pink text-white hover:opacity-85 focus-visible:ring-pink hover:bg-pink-hover',
            ghost:   'text-blue hover:bg-blue/5 focus-visible:ring-blue',
        })[props.variant],
);
const sizeClasses = computed(
    () =>
        ({
            xs: 'px-2 py-1 text-xs',
            sm: 'px-3 py-1.5 text-sm sm:px-4 sm:py-2 sm:text-base',
            md: 'px-4 py-2 text-base sm:px-5 sm:py-2.5 sm:text-xl',
            lg: 'px-5 py-2.5 text-lg sm:px-7 sm:py-3.5 sm:text-2xl',
        })[props.size],
);

const iconSizeClasses = computed(
    () =>
        ({
            xs: 'w-3 h-3',
            sm: 'w-4 h-4',
            md: 'w-5 h-5',
            lg: 'w-6 h-6',
        })[props.size],
);
</script>
<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        v-bind="$attrs"
        :class="[
            'inline-flex items-center justify-center gap-2.5 rounded-xl font-manrope font-medium transition-all duration-300',
            'focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
            'active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:active:scale-100',
            variantClasses,
            sizeClasses,
        ]"
        @click="handleClick"
    >
        <svg
            v-if="loading"
            class="shrink-0 animate-spin"
            :class="iconSizeClasses"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            />
        </svg>
        <span
            v-if="$slots.icon && !loading"
            :class="iconSizeClasses"
            class="shrink-0"
            aria-hidden="true"
        >
            <slot name="icon" />
        </span>
        <span :class="{ 'sr-only': iconOnly }">
            <slot>{{ label }}</slot>
        </span>
        <span
            v-if="$slots.iconRight && !iconOnly && !loading"
            :class="iconSizeClasses"
            class="shrink-0"
            aria-hidden="true"
        >
            <slot name="iconRight" />
        </span>
    </button>
</template>
