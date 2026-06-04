<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Educaxio') }}</title>

    <link rel="icon" href="/favicon.ico?v=2" sizes="any">
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ffffff">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])

    @if ($page['component'] === 'Welcome')
    @php
        $appName = config('app.name', 'Educaxio');
        $appUrl  = url('/');
        $appDesc = 'Educaxio est la plateforme tout-en-un pour les enseignants : gestion des présences, emplois du temps, suivi des élèves et journal de classe. Essai gratuit.';

        $ld = \Spatie\SchemaOrg\Schema::webApplication()
            ->name($appName)
            ->description($appDesc)
            ->url($appUrl)
            ->applicationCategory('EducationalApplication')
            ->operatingSystem('Web')
            ->inLanguage('fr')
            ->offers(
                \Spatie\SchemaOrg\Schema::offer()->price(0)->priceCurrency('EUR')->description('Compte gratuit')
            )
            ->aggregateRating(
                \Spatie\SchemaOrg\Schema::aggregateRating()->ratingValue(4.7)->reviewCount(3)->bestRating(5)->worstRating(1)
            )
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
    @endphp
    <meta name="description" content="{{ $appDesc }}">
    {!! $ld->toScript() !!}
    {!! $ldOrg->toScript() !!}
    @endif

    @inertiaHead

</head>
<body class="font-sans antialiased">
<h1 class="sr-only">{{config('app.name', 'Educaxio')}}</h1>
@inertia
</body>
</html>
