<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Component } from 'vue';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FeatureCard from '@/components/widgets/FeatureCard.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Attendance from '@/components/widgets/svg/Attendance.vue';
import ClassesList from '@/components/widgets/svg/ClassesList.vue';
import Schedule from '@/components/widgets/svg/Schedule.vue';
import TestimonialCard from '@/components/widgets/TestimonialCard.vue';
import { dashboard, login, register } from '@/routes';

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: true });

interface Feature {
    gradient: string;
    title: string;
    description: string;
    icon: Component;
    imageSrc?: string;
    imageSrcset?: string;
    imageSizes?: string;
}

interface Testimonial {
    name: string;
    role: string;
    text: string;
    rating: number;
    avatarSrc: string;
    avatarSrcset: string;
}

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);
const menuOpen = ref(false);

function closeMenu() {
    menuOpen.value = false;
    const cb = document.getElementById(
        'mobile-menu-toggle',
    ) as HTMLInputElement | null;

    if (cb) {
        cb.checked = false;
    }
}

const CARD_SIZES = '(max-width: 1024px) calc(100vw - 4rem), calc(33vw - 4rem)';

const features: Feature[] = [
    {
        gradient: 'bg-blue',
        title: 'Gestion des présences',
        description:
            'Saisie rapide et intuitive avec journal de classe automatique et historique complet pour chaque élève.',
        icon: Attendance,
        imageSrc: '/images/attendance-640w.jpg',
        imageSrcset:
            '/images/attendance-320w.jpg 320w, /images/attendance-640w.jpg 640w, /images/attendance-768w.jpg 768w, /images/attendance-1024w.jpg 1024w, /images/attendance-1280w.jpg 1280w, /images/attendance-1536w.jpg 1536w',
        imageSizes: CARD_SIZES,
    },
    {
        gradient: 'bg-pink',
        title: 'Visualisation de ses classes',
        description:
            "Vue d'ensemble claire de toutes vos classes avec accès rapide au profil détaillé de chaque élève.",
        icon: ClassesList,
        imageSrc: '/images/class-480w.jpeg',
        imageSrcset:
            '/images/class-320w.jpeg 320w, /images/class-480w.jpeg 480w, /images/class-768w.jpeg 768w, /images/class-1024w.jpeg 1024w, /images/class-1280w.jpeg 1280w, /images/class-1536w.jpeg 1536w',
        imageSizes: CARD_SIZES,
    },
    {
        gradient: 'bg-orange',
        title: 'Emploi du temps',
        description:
            'Créez et modifiez facilement vos plannings hebdomadaires. Export PDF disponible en un seul clic.',
        icon: Schedule,
        imageSrc: '/images/schedule-480w.jpeg',
        imageSrcset:
            '/images/schedule-320w.jpeg 320w, /images/schedule-480w.jpeg 480w, /images/schedule-768w.jpeg 768w, /images/schedule-1024w.jpeg 1024w, /images/schedule-1280w.jpeg 1280w, /images/schedule-1536w.jpeg 1536w',
        imageSizes: CARD_SIZES,
    },
];

const T = '/images/testimonials_user';

function tAvatar(slug: string) {
    const base = `${T}/${slug}`;

    return {
        avatarSrc: `${base}-64w.jpg`,
        avatarSrcset: `${base}-64w.jpg 64w, ${base}-128w.jpg 128w, ${base}-256w.jpg 256w`,
    };
}

const testimonials: Testimonial[] = [
    {
        name: 'Marie Lambert',
        role: 'Enseignante de mathématiques',
        text: "Educaxio a complètement transformé ma façon de gérer mes classes. La prise des présences est devenue un jeu d'enfant et je gagne un temps précieux chaque jour.",
        rating: 5,
        ...tAvatar('marie-lambert'),
    },
    {
        name: 'Thomas Renard',
        role: 'Professeur de français',
        text: 'Interface claire, intuitive et agréable à utiliser. Je recommande à tous mes collègues. Le suivi des élèves est enfin centralisé en un seul endroit.',
        rating: 4,
        ...tAvatar('thomas-renard'),
    },
    {
        name: 'Sophie Dumont',
        role: 'Directrice adjointe',
        text: "Un outil indispensable pour notre établissement. La vision d'ensemble sur toutes les classes facilite énormément le travail administratif au quotidien.",
        rating: 5,
        ...tAvatar('sophie-dumont'),
    },
];

</script>

<template>
    <Head title="Educaxio — Gestion des présences et emplois du temps pour enseignants">
        <link rel="preload" as="image"
            href="/images/blackboard-640w.jpg"
            imagesrcset="/images/blackboard-320w.jpg 320w, /images/blackboard-640w.jpg 640w, /images/blackboard-768w.jpg 768w, /images/blackboard-1024w.jpg 1024w, /images/blackboard-1280w.jpg 1280w, /images/blackboard-1536w.jpg 1536w"
            imagesizes="(max-width: 1024px) calc(100vw - 4rem), 465px"
        >
    </Head>

    <div class="min-h-screen bg-bg-primary font-manrope">
        <header class="fixed inset-x-0 top-0 z-50 bg-white shadow-sm">
            <input
                id="mobile-menu-toggle"
                type="checkbox"
                class="peer/menu sr-only"
                @change="menuOpen = ($event.target as HTMLInputElement).checked"
            />
            <nav class="flex items-center justify-between p-6">
                <h2 class="sr-only">Navigation de haut de page</h2>
                <AppLogoIcon />

                <ul class="hidden items-center gap-8 lg:flex">
                    <li>
                        <a
                            href="#accueil"
                            class="text-base font-bold text-text-base transition-colors hover:text-blue"
                        >
                            Accueil
                        </a>
                    </li>
                    <li>
                        <a
                            href="#fonctionnalites"
                            class="text-base font-bold text-text-base transition-colors hover:text-blue"
                        >
                            Fonctionnalités
                        </a>
                    </li>
                    <li>
                        <a
                            href="#avis"
                            class="text-base font-bold text-text-base transition-colors hover:text-blue"
                        >
                            Avis
                        </a>
                    </li>
                </ul>

                <div class="hidden items-center gap-4 lg:flex">
                    <template v-if="isLoggedIn">
                        <LinkButton
                            :href="dashboard.url()"
                            variant="primary"
                            size="sm"
                            label="Tableau de bord"
                        />
                    </template>
                    <template v-else>
                        <LinkButton
                            v-if="canRegister"
                            :href="register.url()"
                            variant="secondary"
                            size="sm"
                            label="Créer un compte"
                        />
                        <LinkButton
                            :href="login.url()"
                            variant="primary"
                            size="sm"
                            label="Se connecter"
                        />
                    </template>
                </div>

                <label
                    for="mobile-menu-toggle"
                    class="flex items-center justify-center rounded-xl p-2 text-text-base transition-colors hover:bg-gray-100 lg:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="mobile-menu"
                    aria-label="Menu"
                >
                    <X v-if="menuOpen" :size="24" />
                    <Menu v-else :size="24" />
                </label>
            </nav>

            <div
                id="mobile-menu"
                class="hidden border-t border-gray-100 bg-white px-6 pb-6 peer-checked/menu:block lg:hidden"
            >
                <ul class="flex flex-col gap-1 py-4">
                    <li>
                        <a
                            href="#accueil"
                            class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue"
                            @click="closeMenu"
                        >
                            Accueil
                        </a>
                    </li>
                    <li>
                        <a
                            href="#fonctionnalites"
                            class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue"
                            @click="closeMenu"
                        >
                            Fonctionnalités
                        </a>
                    </li>
                    <li>
                        <a
                            href="#avis"
                            class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue"
                            @click="closeMenu"
                        >
                            Avis
                        </a>
                    </li>
                </ul>
                <div class="flex flex-col gap-3 border-t border-gray-100 pt-4">
                    <template v-if="isLoggedIn">
                        <LinkButton
                            :href="dashboard.url()"
                            variant="primary"
                            size="sm"
                            label="Tableau de bord"
                        />
                    </template>
                    <template v-else>
                        <LinkButton
                            :href="login.url()"
                            variant="primary"
                            size="sm"
                            label="Se connecter"
                        />
                        <LinkButton
                            v-if="canRegister"
                            :href="register.url()"
                            variant="secondary"
                            size="sm"
                            label="Créer un compte"
                        />
                    </template>
                </div>
            </div>
        </header>

        <main class="flex flex-col gap-8 pt-24">
            <section id="accueil" class="scroll-mt-24 px-8 py-16 lg:pl-32">
                <div class="flex flex-col items-center gap-8 lg:flex-row">
                    <div class="flex flex-1 flex-col gap-6 lg:max-w-3/5">
                        <h2
                            class="text-4xl leading-tight font-extrabold text-text-base lg:text-5xl lg:leading-tight"
                        >
                            Simplifiez la gestion de vos classes avec
                            <span class="text-blue"> Educaxio</span>
                        </h2>
                        <p
                            class="text-base leading-7 font-normal text-text-base lg:text-lg lg:leading-8"
                        >
                            La plateforme tout-en-un pour les enseignants :
                            gestion des présences, emplois du temps, suivi des
                            élèves et bien plus encore. Gagnez du temps pour ce
                            qui compte vraiment.
                        </p>
                        <div class="flex flex-wrap gap-6">
                            <LinkButton
                                v-if="canRegister"
                                :href="register.url()"
                                variant="secondary"
                                size="md"
                                mobile-size="sm"
                                label="Commencer gratuitement"
                            />
                            <LinkButton
                                :href="login.url()"
                                variant="primary"
                                size="md"
                                mobile-size="sm"
                                label="Se connecter"
                            />
                        </div>
                    </div>
                    <div
                        class="aspect-square w-full shrink-0 overflow-hidden rounded-3xl lg:size-116.25"
                    >
                        <img
                            src="/images/blackboard-640w.jpg"
                            srcset="
                                /images/blackboard-320w.jpg   320w,
                                /images/blackboard-640w.jpg   640w,
                                /images/blackboard-768w.jpg   768w,
                                /images/blackboard-1024w.jpg 1024w,
                                /images/blackboard-1280w.jpg 1280w,
                                /images/blackboard-1536w.jpg 1536w
                            "
                            sizes="(max-width: 1024px) calc(100vw - 4rem), 465px"
                            alt="Tableau de classe"
                            width="640"
                            height="640"
                            class="h-full w-full object-cover"
                            fetchpriority="high"
                            decoding="async"
                        />
                    </div>
                </div>
            </section>

            <section
                id="fonctionnalites"
                class="scroll-mt-24 bg-white pt-8 pb-16"
            >
                <div class="px-8">
                    <h2
                        class="mb-6 text-2xl font-extrabold text-text-base lg:text-3xl"
                    >
                        Toutes les fonctionnalités dont vous avez besoin
                    </h2>
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <FeatureCard
                            v-for="feature in features"
                            :key="feature.title"
                            :gradient="feature.gradient"
                            :title="feature.title"
                            :description="feature.description"
                            :image-src="feature.imageSrc"
                            :image-srcset="feature.imageSrcset"
                            :image-sizes="feature.imageSizes"
                        >
                            <template #icon>
                                <component
                                    :is="feature.icon"
                                    :size="28"
                                    :stroke-width="2"
                                />
                            </template>
                        </FeatureCard>
                    </div>
                </div>
            </section>

            <section id="avis" class="scroll-mt-24 px-8 py-16">
                <h2 class="mb-6 text-2xl font-extrabold text-text-base lg:text-3xl">
                    Ils nous font confiance
                </h2>
                <div class="flex flex-col gap-8 lg:flex-row">
                    <TestimonialCard
                        v-for="t in testimonials"
                        :key="t.name"
                        class="flex-1"
                        :name="t.name"
                        :role="t.role"
                        :text="t.text"
                        :rating="t.rating"
                        :avatar-src="t.avatarSrc"
                        :avatar-srcset="t.avatarSrcset"
                    />
                </div>
            </section>

            <section
                class="flex min-h-96 items-center justify-center bg-white p-8"
            >
                <div class="flex flex-col items-center gap-8 text-center">
                    <div class="flex flex-col gap-3">
                        <h2
                            class="text-3xl font-extrabold text-text-base lg:text-4xl"
                        >
                            Prêt à simplifier votre quotidien ?
                        </h2>
                        <p
                            class="text-base leading-7 font-normal text-text-base lg:text-lg lg:leading-8"
                        >
                            Rejoignez des milliers d'enseignants qui ont déjà
                            adopté Educaxio
                        </p>
                    </div>
                    <LinkButton
                        :href="register.url()"
                        variant="secondary"
                        size="md"
                        mobile-size="sm"
                        label="Créer un compte dès maintenant"
                    />
                </div>
            </section>
        </main>

        <footer
            class="bg-white py-6 shadow-[0px_-2px_60px_0px_rgba(0,0,0,0.10)]"
        >
            <h2 class="sr-only">Pied de page</h2>
            <div class="flex flex-col gap-6 px-6">
                <div
                    class="relative flex flex-col items-start gap-4 sm:flex-row sm:items-center"
                >
                    <AppLogoIcon />
                    <nav
                        class="flex flex-wrap gap-8 sm:absolute sm:left-1/2 sm:-translate-x-1/2"
                    >
                        <h3 class="sr-only">Navigation de pied de page</h3>
                        <a
                            href="#"
                            class="text-sm text-text-base transition-colors hover:text-blue"
                            >Politique de confidentialité</a
                        >
                        <a
                            href="#"
                            class="text-sm text-text-base transition-colors hover:text-blue"
                            >Conditions générales d'utilisation</a
                        >
                        <a
                            href="#"
                            class="text-sm text-text-base transition-colors hover:text-blue"
                            >Accessibilité</a
                        >
                        <a
                            href="#"
                            class="text-sm text-text-base transition-colors hover:text-blue"
                            >Support</a
                        >
                    </nav>
                </div>
                <p class="text-center text-xs text-text-base">
                    © <strong>Educaxio</strong> 2026 — Tous droits réservés
                </p>
            </div>
        </footer>
    </div>
</template>
