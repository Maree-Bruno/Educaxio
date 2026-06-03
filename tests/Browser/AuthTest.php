<?php

use App\Models\User;

it('un utilisateur peut se connecter via le formulaire', function () {
    [$user] = createUserWithSchool('admin');

    visit('/login')
        ->fill('input[type="email"]', $user->email)
        ->fill('input[type="password"]', 'password')
        ->click('Se connecter')
        ->assertPathContains('tableau-de-bord');
});

it('affiche le formulaire de connexion avec les champs requis', function () {
    visit('/login')
        ->assertSee('Adresse email')
        ->assertSee('Mot de passe')
        ->assertSee('Se connecter');
});

it('redirige un guest depuis le dashboard vers la page de connexion', function () {
    visit(route('dashboard'))->assertSee('Se connecter');
});

it('redirige un guest depuis les présences vers la page de connexion', function () {
    visit(route('attendances'))->assertSee('Se connecter');
});

it('redirige un utilisateur sans école vers la page en attente', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    visit(route('dashboard'))->assertPathContains('en-attente');
});
