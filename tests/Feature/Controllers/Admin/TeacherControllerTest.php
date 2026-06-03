<?php

use App\Models\User;

beforeEach(function () {
    [$this->admin, $this->school] = createUserWithSchool('admin');
    $this->actingAs($this->admin);
});

// --- Index ---

it('admin peut voir la liste des enseignants de son école', function () {
    $teacher = User::factory()->create(['name' => 'Sophie Bernard']);
    $this->school->users()->attach($teacher->id, ['role' => 'teacher']);

    $this->get(route('admin.teachers.index', $this->school))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Teachers')
            ->has('teachers.data', 1)
            ->where('teachers.data.0.name', 'Sophie Bernard')
        );
});

it('un enseignant ne peut pas accéder à la liste admin des professeurs', function () {
    $teacher = User::factory()->create();
    $this->school->users()->attach($teacher->id, ['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get(route('admin.teachers.index', $this->school))
        ->assertForbidden();
});

it('un guest est redirigé depuis la liste des professeurs', function () {
    $this->post('/logout');

    $this->get(route('admin.teachers.index', $this->school))
        ->assertRedirect(route('login'));
});

// --- Destroy ---

it('admin peut retirer un enseignant de son école', function () {
    $teacher = User::factory()->create();
    $this->school->users()->attach($teacher->id, ['role' => 'teacher']);

    $this->delete(route('admin.teachers.destroy', [$this->school, $teacher]))
        ->assertRedirect();

    expect($this->school->users()->where('users.id', $teacher->id)->exists())->toBeFalse();
});

it('admin ne peut pas retirer un enseignant dune autre école', function () {
    [$otherAdmin, $otherSchool] = createUserWithSchool('admin');
    $teacher = User::factory()->create();
    $otherSchool->users()->attach($teacher->id, ['role' => 'teacher']);

    $this->delete(route('admin.teachers.destroy', [$this->school, $teacher]))
        ->assertForbidden();
});
