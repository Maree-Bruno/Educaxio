<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

const props = withDefaults(
    defineProps<{
        href: string;
        title?: string;
        method?: Method;
        active?: boolean;
    }>(),
    {
        method: 'get',
    },
);
</script>

<template>
    <li class="group relative">
        <Link
            :href="props.href"
            :method="props.method"
            class="flex cursor-pointer items-center text-base gap-2.5 rounded-md px-4 py-2 text-text-base font-bold transition-all hover:text-blue-dark hover:bg-hover-nav"
            :class="[props.active ? 'font-bold text-blue-dark' : '']"
        >
            <div class="shrink-0">
                <slot name="icon" />
            </div>

            <span>{{ props.title }}</span>
        </Link>
    </li>
</template>
<style scoped>
li::before {
    content: none;
    display: none;
}
</style>
