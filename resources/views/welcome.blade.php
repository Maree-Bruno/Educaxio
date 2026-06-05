<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Educaxio — Gestion des présences et emplois du temps pour enseignants</title>

    <link rel="icon" href="/favicon.ico?v=2" sizes="any">
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ffffff">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    @php
        $appName = config('app.name', 'Educaxio');
        $appUrl  = url('/');
        $appDesc = 'Educaxio est la plateforme tout-en-un pour les enseignants : gestion des présences, emplois du temps, suivi des élèves et journal de classe. Essai gratuit.';

        $ldScripts = \Illuminate\Support\Facades\Cache::rememberForever('welcome.jsonld', function () use ($appName, $appUrl, $appDesc) {
            $ld = \Spatie\SchemaOrg\Schema::webApplication()
                ->name($appName)
                ->description($appDesc)
                ->url($appUrl)
                ->applicationCategory('EducationalApplication')
                ->operatingSystem('Web')
                ->inLanguage('fr')
                ->offers(\Spatie\SchemaOrg\Schema::offer()->price(0)->priceCurrency('EUR')->description('Compte gratuit'))
                ->aggregateRating(\Spatie\SchemaOrg\Schema::aggregateRating()->ratingValue(4.7)->reviewCount(3)->bestRating(5)->worstRating(1))
                ->review([
                    \Spatie\SchemaOrg\Schema::review()
                        ->author(\Spatie\SchemaOrg\Schema::person()->name('Marie Lambert'))
                        ->reviewRating(\Spatie\SchemaOrg\Schema::rating()->ratingValue(5)->bestRating(5)->worstRating(1))
                        ->reviewBody("Educaxio a complètement transformé ma façon de gérer mes classes. La prise des présences est devenue un jeu d'enfant et je gagne un temps précieux chaque jour."),
                    \Spatie\SchemaOrg\Schema::review()
                        ->author(\Spatie\SchemaOrg\Schema::person()->name('Thomas Renard'))
                        ->reviewRating(\Spatie\SchemaOrg\Schema::rating()->ratingValue(4)->bestRating(5)->worstRating(1))
                        ->reviewBody("Interface claire, intuitive et agréable à utiliser. Je recommande à tous mes collègues. Le suivi des élèves est enfin centralisé en un seul endroit."),
                    \Spatie\SchemaOrg\Schema::review()
                        ->author(\Spatie\SchemaOrg\Schema::person()->name('Sophie Dumont'))
                        ->reviewRating(\Spatie\SchemaOrg\Schema::rating()->ratingValue(5)->bestRating(5)->worstRating(1))
                        ->reviewBody("Un outil indispensable pour notre établissement. La vision d'ensemble sur toutes les classes facilite énormément le travail administratif au quotidien."),
                ])
                ->featureList(['Gestion des présences', 'Visualisation de ses classes', 'Emploi du temps']);

            $ldOrg = \Spatie\SchemaOrg\Schema::organization()
                ->name($appName)
                ->description('Plateforme de gestion scolaire pour enseignants.')
                ->url($appUrl)
                ->logo(url('/favicon.svg'));

            return $ld->toScript() . $ldOrg->toScript();
        });
    @endphp

    <meta name="description" content="{{ $appDesc }}">
    <meta name="author" content="Educaxio">
    <meta name="keywords" content="gestion présences, emploi du temps, suivi élèves, journal de classe, enseignants, école, plateforme scolaire">
    {!! $ldScripts !!}

    <style>
        #mobile-menu-toggle:checked + nav .burger-close { display: block; }
        #mobile-menu-toggle:checked + nav .burger-open  { display: none; }
    </style>
</head>
<body class="font-manrope antialiased">
<h1 class="sr-only">Educaxio</h1>

<div class="min-h-screen bg-bg-primary font-manrope">

    {{-- ── Header ────────────────────────────────────────────────────────────── --}}
    <header class="fixed inset-x-0 top-0 z-50 bg-white shadow-sm">
        <input id="mobile-menu-toggle" type="checkbox" class="peer/menu sr-only" aria-hidden="true">

        <nav class="flex items-center justify-between p-6">
            <h2 class="sr-only">Navigation de haut de page</h2>

            <a href="{{ url('/') }}" title="Accueil Educaxio" class="flex items-center gap-1">
                <div class="inline-flex h-9 w-9 items-center justify-center gap-2.5 rounded-2xl bg-blue p-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M22 10V16M22 10L12 5L2 10L12 15L22 10Z" stroke="#F5F0EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 12V17C9 20 15 20 18 17V12" stroke="#F5F0EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="text-xl font-black tracking-[-2px] text-text-base lg:text-4xl">Educaxio</span>
            </a>

            <ul class="hidden items-center gap-8 lg:flex">
                <li><a href="#accueil" hreflang="fr" class="text-base font-bold text-text-base transition-colors hover:text-blue">Accueil</a></li>
                <li><a href="#fonctionnalites" hreflang="fr" class="text-base font-bold text-text-base transition-colors hover:text-blue">Fonctionnalités</a></li>
                <li><a href="#avis" hreflang="fr" class="text-base font-bold text-text-base transition-colors hover:text-blue">Avis</a></li>
            </ul>

            <div class="hidden items-center gap-4 lg:flex">
                @if($isLoggedIn)
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-blue px-4 py-2 text-base font-medium text-white transition-all duration-300 hover:opacity-85">Tableau de bord</a>
                @else
                    @if($canRegister)
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-orange px-4 py-2 text-base font-medium text-white transition-all duration-300 hover:opacity-85">Créer un compte</a>
                    @endif
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-blue px-4 py-2 text-base font-medium text-white transition-all duration-300 hover:opacity-85">Se connecter</a>
                @endif
            </div>

            <label for="mobile-menu-toggle" class="flex cursor-pointer items-center justify-center rounded-xl p-2 text-text-base transition-colors hover:bg-gray-100 lg:hidden" aria-label="Menu">
                <svg class="burger-open" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="18" x2="20" y2="18"/>
                </svg>
                <svg class="burger-close hidden" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </label>
        </nav>

        <div class="hidden border-t border-gray-100 bg-white px-6 pb-6 peer-checked/menu:block lg:hidden">
            <ul class="flex flex-col gap-1 py-4">
                <li><a href="#accueil" class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue">Accueil</a></li>
                <li><a href="#fonctionnalites" class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue">Fonctionnalités</a></li>
                <li><a href="#avis" class="block rounded-xl px-3 py-2.5 text-lg font-bold text-text-base transition-colors hover:bg-gray-50 hover:text-blue">Avis</a></li>
            </ul>
            <div class="flex flex-col gap-3 border-t border-gray-100 pt-4">
                @if($isLoggedIn)
                    <a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-blue px-4 py-2 text-base font-medium text-white transition-all duration-300 hover:opacity-85">Tableau de bord</a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-blue px-4 py-2 text-base font-medium text-white transition-all duration-300 hover:opacity-85">Se connecter</a>
                    @if($canRegister)
                        <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-orange px-4 py-2 text-base font-medium text-white transition-all duration-300 hover:opacity-85">Créer un compte</a>
                    @endif
                @endif
            </div>
        </div>
    </header>
    <main class="flex flex-col gap-8 pt-24">

        <section id="accueil" class="scroll-mt-24 px-8 py-16 lg:pl-32">
            <div class="flex flex-col items-center gap-8 lg:flex-row">
                <div class="flex flex-1 flex-col gap-6 lg:max-w-3/5">
                    <h2 class="text-4xl font-extrabold leading-tight text-text-base lg:text-5xl lg:leading-tight">
                        Simplifiez la gestion de vos classes avec
                        <span class="text-blue"> Educaxio</span>
                    </h2>
                    <p class="text-base font-normal leading-7 text-text-base lg:text-lg lg:leading-8">
                        La plateforme tout-en-un pour les enseignants : gestion des présences, emplois du temps, suivi des élèves et bien plus encore. Gagnez du temps pour ce qui compte vraiment.
                    </p>
                    <div class="flex flex-wrap gap-6">
                        @if($canRegister)
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-orange px-5 py-2.5 text-xl font-bold text-white transition-all duration-300 hover:opacity-85 active:scale-[0.98]">Commencer gratuitement</a>
                        @endif
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-blue px-5 py-2.5 text-xl font-bold text-white transition-all duration-300 hover:opacity-85 active:scale-[0.98]">Se connecter</a>
                    </div>
                </div>
                <div class="aspect-square w-full shrink-0 overflow-hidden rounded-3xl lg:size-116.25">
                    <img
                        src="/images/blackboard-640w.jpg"
                        srcset="/images/blackboard-320w.jpg 320w, /images/blackboard-640w.jpg 640w, /images/blackboard-768w.jpg 768w, /images/blackboard-1024w.jpg 1024w, /images/blackboard-1280w.jpg 1280w, /images/blackboard-1536w.jpg 1536w"
                        sizes="(max-width: 1024px) calc(100vw - 4rem), 465px"
                        alt="Tableau de classe"
                        width="640" height="640"
                        class="h-full w-full object-cover"
                        fetchpriority="high"
                        decoding="async"
                    />
                </div>
            </div>
        </section>

        <section id="fonctionnalites" class="scroll-mt-24 bg-white pb-16 pt-8">
            <div class="px-8">
                <h2 class="mb-6 text-2xl font-extrabold text-text-base lg:text-3xl">Toutes les fonctionnalités dont vous avez besoin</h2>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    <div class="flex flex-col gap-3 rounded-3xl bg-blue p-5">
                        <div class="flex size-10 items-center justify-center text-bg-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 6C12.5523 6 13 5.55228 13 5C13 4.44772 12.5523 4 12 4C11.4477 4 11 4.44772 11 5C11 5.55228 11.4477 6 12 6Z"/><path d="M9 20L12 14L15 20"/><path d="M6 8L12 10L18 8"/><path d="M12 10V14"/>
                            </svg>
                        </div>
                        <div class="flex flex-col gap-2">
                            <h3 class="text-lg font-semibold text-bg-primary">Gestion des présences</h3>
                            <p class="text-sm font-normal text-bg-primary">Saisie rapide et intuitive avec journal de classe
                                automatique et historique complet pour chaque élève.</p>
                        </div>
                        <div class="min-h-36 flex-1 overflow-hidden rounded-2xl bg-white/20">
                            <img src="/images/attendance-640w.jpg" srcset="/images/attendance-320w.jpg 320w, /images/attendance-640w.jpg 640w, /images/attendance-768w.jpg 768w, /images/attendance-1024w.jpg 1024w, /images/attendance-1280w.jpg 1280w, /images/attendance-1536w.jpg 1536w" sizes="(max-width: 1024px) calc(100vw - 4rem), calc(33vw - 4rem)" alt="Gestion des présences" width="640" height="480" class="h-full w-full object-cover object-top" loading="lazy" decoding="async"/>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 rounded-3xl bg-pink p-5">
                        <div class="flex size-10 items-center justify-center text-bg-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H6C4.93913 15 3.92172 15.4214 3.17157 16.1716C2.42143 16.9217 2 17.9391 2 19V21"/><path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z"/><path d="M22 21V19C21.9993 18.1137 21.7044 17.2528 21.1614 16.5523C20.6184 15.8519 19.8581 15.3516 19 15.13"/><path d="M16 3.13C16.8604 3.3503 17.623 3.8507 18.1676 4.55231C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89317 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88"/>
                            </svg>
                        </div>
                        <div class="flex flex-col gap-2">
                            <h3 class="text-lg font-semibold text-bg-primary">Visualisation de ses classes</h3>
                            <p class="text-sm font-normal text-bg-primary">Vue d'ensemble claire de toutes vos classes avec
                                accès rapide au profil détaillé de chaque élève.</p>
                        </div>
                        <div class="min-h-36 flex-1 overflow-hidden rounded-2xl bg-white/20">
                            <img src="/images/class-480w.jpeg" srcset="/images/class-320w.jpeg 320w, /images/class-480w.jpeg 480w, /images/class-768w.jpeg 768w, /images/class-1024w.jpeg 1024w, /images/class-1280w.jpeg 1280w, /images/class-1536w.jpeg 1536w" sizes="(max-width: 1024px) calc(100vw - 4rem), calc(33vw - 4rem)" alt="Visualisation de ses classes" width="640" height="480" class="h-full w-full object-cover object-top" loading="lazy" decoding="async"/>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 rounded-3xl bg-orange p-5">
                        <div class="flex size-10 items-center justify-center text-bg-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 7.5V6C21 5.46957 20.7893 4.96086 20.4142 4.58579C20.0391 4.21071 19.5304 4 19 4H5C4.46957 4 3.96086 4.21071 3.58579 4.58579C3.21071 4.96086 3 5.46957 3 6V20C3 20.5304 3.21071 21.0391 3.58579 21.4142C3.96086 21.7893 4.46957 22 5 22H8.5"/><path d="M16 2V6"/><path d="M8 2V6"/><path d="M3 10H8"/><path d="M17.5 17.5L16 16.25V14"/><path d="M22 16C22 17.5913 21.3679 19.1174 20.2426 20.2426C19.1174 21.3679 17.5913 22 16 22C14.4087 22 12.8826 21.3679 11.7574 20.2426C10.6321 19.1174 10 17.5913 10 16C10 14.4087 10.6321 12.8826 11.7574 11.7574C12.8826 10.6321 14.4087 10 16 10C17.5913 10 19.1174 10.6321 20.2426 11.7574C21.3679 12.8826 22 14.4087 22 16Z"/>
                            </svg>
                        </div>
                        <div class="flex flex-col gap-2">
                            <h3 class="text-lg font-semibold text-bg-primary">Emploi du temps</h3>
                            <p class="text-sm font-normal text-bg-primary">Créez et modifiez facilement vos plannings
                                hebdomadaires. Export PDF disponible en un seul clic.</p>
                        </div>
                        <div class="min-h-36 flex-1 overflow-hidden rounded-2xl bg-white/20">
                            <img src="/images/schedule-480w.jpeg" srcset="/images/schedule-320w.jpeg 320w, /images/schedule-480w.jpeg 480w, /images/schedule-768w.jpeg 768w, /images/schedule-1024w.jpeg 1024w, /images/schedule-1280w.jpeg 1280w, /images/schedule-1536w.jpeg 1536w" sizes="(max-width: 1024px) calc(100vw - 4rem), calc(33vw - 4rem)" alt="Emploi du temps" width="640" height="480" class="h-full w-full object-cover object-top" loading="lazy" decoding="async"/>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <section id="avis" class="scroll-mt-24 px-8 py-16">
            <h2 class="mb-6 text-2xl font-extrabold text-text-base lg:text-3xl">Ils nous font confiance</h2>
            <div class="flex flex-col gap-8 lg:flex-row">

                <article class="flex flex-1 flex-col gap-6 rounded-3xl bg-white p-6">
                    <h3 class="sr-only">Avis de Marie Lambert</h3>
                    <div class="flex items-center gap-3.5">
                        <div class="size-20 shrink-0 overflow-hidden rounded-full bg-zinc-300">
                            <img src="/images/testimonials_user/marie-lambert-64w.jpg" srcset="/images/testimonials_user/marie-lambert-64w.jpg 64w, /images/testimonials_user/marie-lambert-128w.jpg 128w, /images/testimonials_user/marie-lambert-256w.jpg 256w" sizes="80px" alt="Marie Lambert" width="80" height="80" class="h-full w-full object-cover" loading="lazy" decoding="async"/>
                        </div>
                        <div class="flex flex-col gap-1">
                            <cite class="text-base font-bold not-italic text-text-base">Marie Lambert</cite>
                            <p class="text-sm text-text-base">Enseignante de mathématiques</p>
                        </div>
                    </div>
                    <q class="flex-1 text-sm not-italic text-text-base">Educaxio a complètement transformé ma façon de gérer mes classes. La prise des présences est devenue un jeu d'enfant et je gagne un temps précieux chaque jour.</q>
                    <div class="flex gap-1" role="img" aria-label="5 étoiles sur 5">
                        <span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span>
                    </div>
                </article>

                <article class="flex flex-1 flex-col gap-6 rounded-3xl bg-white p-6">
                    <h3 class="sr-only">Avis de Thomas Renard</h3>
                    <div class="flex items-center gap-3.5">
                        <div class="size-20 shrink-0 overflow-hidden rounded-full bg-zinc-300">
                            <img src="/images/testimonials_user/thomas-renard-64w.jpg" srcset="/images/testimonials_user/thomas-renard-64w.jpg 64w, /images/testimonials_user/thomas-renard-128w.jpg 128w, /images/testimonials_user/thomas-renard-256w.jpg 256w" sizes="80px" alt="Thomas Renard" width="80" height="80" class="h-full w-full object-cover" loading="lazy" decoding="async"/>
                        </div>
                        <div class="flex flex-col gap-1">
                            <cite class="text-base font-bold not-italic text-text-base">Thomas Renard</cite>
                            <p class="text-sm text-text-base">Professeur de français</p>
                        </div>
                    </div>
                    <q class="flex-1 text-sm not-italic text-text-base">Interface claire, intuitive et agréable à utiliser. Je recommande à tous mes collègues. Le suivi des élèves est enfin centralisé en un seul endroit.</q>
                    <div class="flex gap-1" role="img" aria-label="4 étoiles sur 5">
                        <span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-zinc-300" aria-hidden="true">★</span>
                    </div>
                </article>

                <article class="flex flex-1 flex-col gap-6 rounded-3xl bg-white p-6">
                    <h3 class="sr-only">Avis de Sophie Dumont</h3>
                    <div class="flex items-center gap-3.5">
                        <div class="size-20 shrink-0 overflow-hidden rounded-full bg-zinc-300">
                        <img src="/images/testimonials_user/sophie-dumont-64w.jpg" srcset="/images/testimonials_user/sophie-dumont-64w.jpg 64w, /images/testimonials_user/sophie-dumont-128w.jpg 128w, /images/testimonials_user/sophie-dumont-256w.jpg 256w" sizes="80px" alt="Sophie Dumont" width="80" height="80" class="h-full w-full object-cover" loading="lazy" decoding="async"/>
                        </div>
                        <div class="flex flex-col gap-1">
                            <cite class="text-base font-bold not-italic text-text-base">Sophie Dumont</cite>
                            <p class="text-sm text-text-base">Directrice adjointe</p>
                        </div>
                    </div>
                    <q class="flex-1 text-sm not-italic text-text-base">Un outil indispensable pour notre établissement. La vision d'ensemble sur toutes les classes facilite énormément le travail administratif au quotidien.</q>
                    <div class="flex gap-1" role="img" aria-label="5 étoiles sur 5">
                        <span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span><span class="text-base leading-none text-orange-500" aria-hidden="true">★</span>
                    </div>
                </article>

            </div>
        </section>
        <section class="flex min-h-96 items-center justify-center bg-white p-8">
            <div class="flex flex-col items-center gap-8 text-center">
                <div class="flex flex-col gap-3">
                    <h2 class="text-3xl font-extrabold text-text-base lg:text-4xl">Prêt à simplifier votre quotidien ?</h2>
                    <p class="text-base font-normal leading-7 text-text-base lg:text-lg lg:leading-8">Rejoignez des milliers d'enseignants qui ont déjà adopté Educaxio</p>
                </div>
                @if($canRegister)
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-orange px-5 py-2.5 text-xl font-bold text-white transition-all duration-300 hover:opacity-85 active:scale-[0.98]">Créer un compte dès maintenant</a>
                @endif
            </div>
        </section>

    </main>
    <footer class="bg-white py-6 shadow-[0px_-2px_60px_0px_rgba(0,0,0,0.10)]">
        <h2 class="sr-only">Pied de page</h2>
        <div class="flex flex-col gap-6 px-6">
            <div class="relative flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                <a href="{{ url('/') }}" class="flex items-center gap-1">
                    <div class="inline-flex h-9 w-9 items-center justify-center gap-2.5 rounded-2xl bg-blue p-2.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M22 10V16M22 10L12 5L2 10L12 15L22 10Z" stroke="#F5F0EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 12V17C9 20 15 20 18 17V12" stroke="#F5F0EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="text-xl font-black tracking-[-2px] text-text-base">Educaxio</span>
                </a>
                <nav class="flex flex-wrap gap-8 sm:absolute sm:left-1/2 sm:-translate-x-1/2">
                    <h3 class="sr-only">Navigation de pied de page</h3>
                    <a href="#" class="text-sm text-text-base transition-colors hover:text-blue">Politique de confidentialité</a>
                    <a href="#" class="text-sm text-text-base transition-colors hover:text-blue">Conditions générales d'utilisation</a>
                    <a href="#" class="text-sm text-text-base transition-colors hover:text-blue">Accessibilité</a>
                    <a href="#" class="text-sm text-text-base transition-colors hover:text-blue">Support</a>
                </nav>
            </div>
            <p class="text-center text-xs text-text-base">© <strong>Educaxio</strong> <time datetime="2026">2026</time> — Tous droits réservés</p>
        </div>
    </footer>

</div>
</body>
</html>
