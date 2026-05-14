<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    { title: '', description: '' },
);
</script>

<template>
    <div class="min-h-screen bg-bg-primary flex items-center justify-center px-6 py-12">
        <Link
            :href="home.url()"
            class="absolute top-5 left-6 flex items-center gap-1.5 text-sm font-medium text-text-base opacity-60 transition-opacity hover:opacity-100"
        >
            ← Retour à l'accueil
        </Link>
        <div class="flex items-center gap-12">

            <div class="hidden lg:flex w-147 flex-col gap-10">
                <AppLogoIcon />

                <p class="text-3xl font-bold text-text-base leading-tight">
                    Prenez <span class="text-blue">plaisir</span> à donner cours
                </p>

                <p class="text-xl font-medium text-gray-700">
                    Une interface conçue comme un journal pour les enseignants.
                    Simplifiez votre gestion de classe avec une clarté éditoriale sans précédent.
                </p>

                <div class="w-full h-64 rounded-[20px] bg-zinc-300" />
            </div>

            <div class="w-full max-w-125 rounded-[20px] bg-white px-10 py-15 flex flex-col gap-7 shadow-sm">
                <div v-if="title || description" class="flex flex-col gap-2">
                    <h1 v-if="title" class="text-3xl font-bold text-text-base">{{ title }}</h1>
                    <p v-if="description" class="text-base text-text-base">{{ description }}</p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
