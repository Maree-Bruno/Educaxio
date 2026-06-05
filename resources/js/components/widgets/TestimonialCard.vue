<script setup lang="ts">
withDefaults(
    defineProps<{
        name: string;
        role: string;
        text: string;
        rating: number;
        avatarSrc?: string;
        avatarSrcset?: string;
    }>(),
    { rating: 5 },
);
</script>

<template>
    <article class="flex flex-col gap-6 rounded-3xl bg-white p-6">
        <h3 class="sr-only">Avis de {{name}}</h3>
        <div class="flex items-center gap-3.5">
            <div class="size-20 shrink-0 overflow-hidden rounded-full bg-zinc-300">
                <img
                    v-if="avatarSrc"
                    :src="avatarSrc"
                    :srcset="avatarSrcset"
                    sizes="80px"
                    :alt="name"
                    class="h-full w-full object-cover"
                    loading="lazy"
                    decoding="async"
                />
            </div>
            <div class="flex flex-col gap-1">
                <p class="text-base font-bold text-text-base">{{ name }}</p>
                <p class="text-sm text-text-base">{{ role }}</p>
            </div>
        </div>
        <p class="flex-1 text-sm text-text-base">{{ text }}</p>
        <div class="flex gap-1" role="img" :aria-label="`${rating} étoiles sur 5`">
            <span
                v-for="i in 5"
                :key="i"
                class="text-base leading-none"
                :class="i> rating ?'text-zinc-300':'text-orange-500'"
                aria-hidden="true"
            >★</span>
        </div>
    </article>
</template>
