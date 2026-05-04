<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type Variant = 'primary' | 'secondary' | 'danger';
type Size = 'sm' | 'md' | 'lg';
type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

const props = withDefaults(
    defineProps<{
        href: string;
        variant?: Variant;
        size?: Size;
        label?: string;
        iconOnly?: boolean;
        method?: Method;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        label: '',
        iconOnly: false,
        method: 'get',
    },
);

const variantClasses = computed(
    () =>
        ({
            primary:
                'bg-blue text-white hover:opacity-85 focus-visible:ring-blue hover:bg-blue-hover',
            secondary:
                'bg-orange text-white hover:opacity-85 focus-visible:ring-orange hover:bg-orange-hover',
            danger: 'bg-pink text-white hover:opacity-85 focus-visible:ring-pink hover:bg-pink-hover',
        })[props.variant],
);

const sizeClasses = computed(
    () =>
        ({
            sm: 'px-4 py-2 text-base',
            md: 'px-5 py-2.5 text-xl',
            lg: 'px-7 py-3.5 text-2xl',
        })[props.size],
);

const iconSizeClasses = computed(
    () =>
        ({
            sm: 'w-4 h-4',
            md: 'w-5 h-5',
            lg: 'w-6 h-6',
        })[props.size],
);
</script>

<template>
    <Link
        :href="href"
        :method="method"
        v-bind="$attrs"
        :class="[
            'inline-flex items-center justify-center gap-2.5 rounded-xl font-manrope font-medium transition-all duration-300',
            'focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
            'active:scale-[0.98]',
            variantClasses,
            sizeClasses,
        ]"
    >
        <span
            v-if="$slots.icon"
            :class="iconSizeClasses"
            class="flex shrink-0 items-center justify-center"
            aria-hidden="true"
        >
            <slot name="icon" />
        </span>
        <span :class="{ 'sr-only': iconOnly }">
            <slot>{{ label }}</slot>
        </span>
        <span
            v-if="$slots.iconRight && !iconOnly"
            :class="iconSizeClasses"
            class="flex shrink-0 items-center justify-center"
            aria-hidden="true"
        >
            <slot name="iconRight" />
        </span>
    </Link>
</template>