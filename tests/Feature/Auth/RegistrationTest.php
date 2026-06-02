<?php

use App\Models\School;
use App\Models\SchoolJoinRequest;
use App\Models\Subject;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registering with subject_ids attaches subjects to the user', function () {
    $subjectA = Subject::firstOrCreate(['name' => 'Mathématiques']);
    $subjectB = Subject::firstOrCreate(['name' => 'Français']);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'subject_ids' => [$subjectA->id, $subjectB->id],
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect($user->subjects()->count())->toBe(2);
});

test('registering with school_ids creates pending SchoolJoinRequests', function () {
    $id = uniqid();
    $schoolA = School::create(['name' => "École A {$id}", 'slug' => "ecole-a-{$id}"]);
    $idB = uniqid();
    $schoolB = School::create(['name' => "École B {$idB}", 'slug' => "ecole-b-{$idB}"]);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'school_ids' => [$schoolA->id, $schoolB->id],
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect(SchoolJoinRequest::where('user_id', $user->id)->count())->toBe(2);
    expect(
        SchoolJoinRequest::where('user_id', $user->id)->pluck('status')->unique()->toArray()
    )->toBe(['pending']);
});

test('registering without school_ids creates user with no join requests', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect(SchoolJoinRequest::where('user_id', $user->id)->count())->toBe(0);
});
