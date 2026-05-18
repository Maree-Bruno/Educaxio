<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type Variant = 'primary' | 'secondary' | 'danger';
type Size = 'sm' | 'md' | 'lg' | 'xs';
type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

const props = withDefaults(
    defineProps<{
        href: string;
        variant?: Variant;
        size?: Size;
        mobileSize?: Size;
        label?: string;
        iconOnly?: boolean;
        method?: Method;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        mobileSize: undefined,
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

const sizePaddingMap = {
    xs: { base: 'px-2 py-1 text-xs', lg: 'lg:px-4 lg:py-2 lg:text-base' },
    sm: { base: 'px-4 py-2 text-base', lg: 'lg:px-4 lg:py-2 lg:text-base' },
    md: {
        base: 'px-5 py-2.5 text-xl font-bold',
        lg: 'lg:px-5 lg:py-2.5 lg:text-xl lg:font-bold',
    },
    lg: {
        base: 'px-7 py-3.5 text-2xl font-bold',
        lg: 'lg:px-7 lg:py-3.5 lg:text-2xl lg:font-bold',
    },
};

const sizeClasses = computed(() => {
    if (props.mobileSize && props.mobileSize !== props.size) {
        return `${sizePaddingMap[props.mobileSize].base} ${sizePaddingMap[props.size].lg}`;
    }

    return sizePaddingMap[props.size].base;
});

const iconSizeMap = {
    xs: { base: 'w-2 h-2', lg: 'lg:w-2 lg:h-2' },
    sm: { base: 'w-4 h-4', lg: 'lg:w-4 lg:h-4' },
    md: { base: 'w-5 h-5', lg: 'lg:w-5 lg:h-5' },
    lg: { base: 'w-6 h-6', lg: 'lg:w-6 lg:h-6' },
};

const iconSizeClasses = computed(() => {
    if (props.mobileSize && props.mobileSize !== props.size) {
        return `${iconSizeMap[props.mobileSize].base} ${iconSizeMap[props.size].lg}`;
    }

    return iconSizeMap[props.size].base;
});
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
