<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

const props = withDefaults(
    defineProps<{
        href: string;
        title?: string;
        method?: Method;
        active?: boolean;
        collapsed?: boolean;
    }>(),
    {
        method: 'get',
        collapsed: false,
    },
);
</script>

<template>
    <li>
        <Link
            :href="props.href"
            :method="props.method"
            :title="props.collapsed ? props.title : undefined"
            class="flex cursor-pointer items-center text-base gap-2.5 rounded-md px-4 py-2 font-bold transition-all hover:text-blue-dark hover:bg-hover-nav"
            :class="[
                props.active ? 'text-blue-dark bg-hover-nav' : 'text-text-base',
                props.collapsed ? 'md:justify-center md:px-2' : '',
            ]"
        >
            <div class="shrink-0">
                <slot name="icon" />
            </div>

            <span
                class="overflow-hidden whitespace-nowrap transition-[opacity,max-width] ease-in-out"
                :class="props.collapsed
                    ? 'md:sr-only duration-150'
                    : 'max-w-50 opacity-100 duration-200 delay-150'
                "
            >{{ props.title }}</span>
        </Link>
    </li>
</template>

<style scoped>
li::before {
    content: none;
    display: none;
}
</style>
