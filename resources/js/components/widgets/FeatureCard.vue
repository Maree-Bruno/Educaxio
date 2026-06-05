<script setup lang="ts">
defineProps<{
    gradient: string;
    title: string;
    description: string;
    imageSrc?: string;
    imageSrcset?: string;
    imageSizes?: string;
}>();
</script>

<template>
    <div :class="['flex flex-col gap-3 rounded-3xl p-5', gradient]">
        <div class="flex size-10 items-center justify-center text-bg-primary">
            <slot name="icon" />
        </div>
        <div class="flex flex-col gap-2">
            <h3 class="text-lg font-semibold text-bg-primary">{{ title }}</h3>
            <p class="text-sm font-normal text-white">{{ description }}</p>
        </div>
        <div class="min-h-36 flex-1 overflow-hidden rounded-2xl bg-white/20">
            <img
                v-if="imageSrc"
                :src="imageSrc"
                :srcset="imageSrcset"
                :sizes="imageSizes"
                :alt="title"
                width="640"
                height="480"
                class="h-full w-full object-cover object-top"
                loading="lazy"
                decoding="async"
            />
        </div>
    </div>
</template>
