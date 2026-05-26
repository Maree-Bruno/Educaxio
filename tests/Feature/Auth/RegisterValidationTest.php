<?php

use App\Models\User;

test('valid data redirects back', function () {
    $response = $this->post('/register/validate', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect();
    $response->assertStatus(302);
});

test('taken email returns session error on email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/register/validate', [
        'name'                  => 'Test User',
        'email'                 => 'taken@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password mismatch returns session error on password', function () {
    $response = $this->post('/register/validate', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'different123',
    ]);

    $response->assertSessionHasErrors('password');
});

test('missing name returns session error on name', function () {
    $response = $this->post('/register/validate', [
        'email'                 => 'test@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('name');
});

test('authenticated user is redirected away by guest middleware', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/register/validate', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect();
});