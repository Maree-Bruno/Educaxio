<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { type Component, computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FeatureCard from '@/components/widgets/FeatureCard.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Attendance from '@/components/widgets/svg/Attendance.vue';
import ClassesList from '@/components/widgets/svg/ClassesList.vue';
import Schedule from '@/components/widgets/svg/Schedule.vue';
import TestimonialCard from '@/components/widgets/TestimonialCard.vue';
import { dashboard, login, register } from '@/routes';

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: true });

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);

const menuOpen = ref(false);

const features: {
    gradient: string;
    title: string;
    description: string;
    icon: Component;
}[] = [
    {
        gradient: 'bg-gradient-to-b from-blue to-blue-dark',
        title: 'Gestion des présences',
        description:
            'Saisie rapide et intuitive avec journal de classe automatique et historique complet pour chaque élève.',
        icon: Attendance,
    },
    {
        gradient: 'bg-gradient-to-b from-pink to-[#831843]',
        title: 'Visualisation de ses classes',
        description:
            "Vue d'ensemble claire de toutes vos classes avec accès rapide au profil détaillé de chaque élève.",
        icon: ClassesList,
    },
    {
        gradient: 'bg-gradient-to-b from-orange to-amber-800',
        title: 'Emploi du temps',
        description:
            'Créez et modifiez facilement vos plannings hebdomadaires. Export PDF disponible en un seul clic.',
        icon: Schedule,
    },
];

const testimonials = [
    {
        name: 'Marie Lambert',
        role: 'Enseignante de mathématiques',
        text: "Educaxio a complètement transformé ma façon de gérer mes classes. La prise des présences est devenue un jeu d'enfant et je gagne un temps précieux chaque jour.",
        rating: 5,
    },
    {
        name: 'Thomas Renard',
        role: 'Professeur de français',
        text: 'Interface claire, intuitive et agréable à utiliser. Je recommande à tous mes collègues. Le suivi des élèves est enfin centralisé en un seul endroit.',
        rating: 5,
    },
    {
        name: 'Sophie Dumont',
        role: 'Directrice adjointe',
        text: "Un outil indispensable pour notre établissement. La vision d'ensemble sur toutes les classes facilite énormément le travail administratif au quotidien.",
        rating: 5,
    },
];
</script>

<template>
    <Head title="Educaxio" />

    <div class="min-h-screen bg-bg-primary font-manrope">
        <header class="sticky top-0 z-50 bg-white shadow-sm">
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

                <button
                    class="flex items-center justify-center rounded-xl p-2 text-text-base transition-colors hover:bg-gray-100 lg:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="mobile-menu"
                    aria-label="Menu"
                    @click="menuOpen = !menuOpen"
                >
                    <X v-if="menuOpen" :size="24" />
                    <Menu v-else :size="24" />
                </button>
            </nav>

            <div
                v-if="menuOpen"
                id="mobile-menu"
                class="border-t border-gray-100 bg-white px-6 pb-6 lg:hidden"
            >
                <ul class="flex flex-col gap-1 py-4">
                    <li>
                        <a
                            href="#accueil"
                            class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue"
                            @click="menuOpen = false"
                        >
                            Accueil
                        </a>
                    </li>
                    <li>
                        <a
                            href="#fonctionnalites"
                            class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue"
                            @click="menuOpen = false"
                        >
                            Fonctionnalités
                        </a>
                    </li>
                    <li>
                        <a
                            href="#avis"
                            class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue"
                            @click="menuOpen = false"
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

        <main class="flex flex-col gap-8">
            <!-- Hero -->
            <section id="accueil" class="px-8 py-16 pl-32">
                <div class="flex flex-col items-center gap-8 lg:flex-row">
                    <div class="flex flex-1 flex-col gap-6 lg:max-w-3/5">
                        <h2
                            class="text-4xl leading-tight font-extrabold text-stone-900 lg:text-5xl lg:leading-tight"
                        >
                            Simplifiez la gestion de vos classes avec
                            <span class="text-blue"> Educaxio</span>
                        </h2>
                        <p
                            class="text-base leading-7 font-normal text-gray-700 lg:text-lg lg:leading-8"
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
                        class="aspect-square w-full shrink-0 rounded-3xl bg-zinc-300 lg:size-116.25"
                    />
                </div>
            </section>

            <!-- Fonctionnalités -->
            <section id="fonctionnalites" class="bg-white pt-8 pb-16">
                <div class="px-8">
                    <h2 class="mb-6 text-2xl font-extrabold text-black lg:text-3xl">
                        Toutes les fonctionnalités dont vous avez besoin
                    </h2>
                    <div
                        class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                    >
                        <FeatureCard
                            v-for="feature in features"
                            :key="feature.title"
                            :gradient="feature.gradient"
                            :title="feature.title"
                            :description="feature.description"
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

            <!-- Avis -->
            <section id="avis" class="px-8 py-16">
                <h2 class="mb-6 text-2xl font-extrabold text-black lg:text-3xl">
                    Ils nous font confiance
                </h2>
                <div class="flex flex-col gap-8 md:flex-row">
                    <TestimonialCard
                        v-for="t in testimonials"
                        :key="t.name"
                        class="flex-1"
                        :name="t.name"
                        :role="t.role"
                        :text="t.text"
                        :rating="t.rating"
                    />
                </div>
            </section>

            <!-- CTA -->
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
                        <p class="text-base leading-7 font-normal text-text-base lg:text-lg lg:leading-8">
                            Rejoignez des milliers d'enseignants qui ont déjà
                            adopté Educaxio
                        </p>
                    </div>
                    <LinkButton
                        :href="login.url()"
                        variant="primary"
                        size="md"
                        mobile-size="sm"
                        label="Se connecter"
                    />
                </div>
            </section>
        </main>

        <!-- Footer -->
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
                <p class="text-center text-xs text-black">
                    © <strong>Educaxio</strong> 2026 — Tous droits réservés
                </p>
            </div>
        </footer>
    </div>
</template>
