<?php

use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    [$this->admin, $this->school] = createUserWithSchool('admin');
    $this->year = attachAcademicYear($this->school);
    $this->actingAs($this->admin);
});

// --- Index ---

it('admin peut voir la liste des élèves de son école', function () {
    $student = Student::factory()->create(['school_id' => $this->school->id]);
    $group = createGroup($this->school, $this->year);
    $student->groups()->attach($group->id);

    $this->get(route('admin.students.index', $this->school))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students')
            ->has('students.data', 1)
            ->where('students.data.0.lastname', $student->lastname)
        );
});

it('enseignant ne peut pas accéder à la liste des élèves admin', function () {
    [$teacher] = createUserWithSchool('teacher');

    $this->actingAs($teacher)
        ->get(route('admin.students.index', $this->school))
        ->assertForbidden();
});

// --- Store ---

it('admin peut créer un élève', function () {
    $this->post(route('admin.students.store', $this->school), [
        'lastname'  => 'Martin',
        'firstname' => 'Léa',
        'email'     => 'lea.martin@example.com',
    ])->assertRedirect(route('admin.students.index', $this->school));

    $this->assertDatabaseHas('students', [
        'lastname'  => 'Martin',
        'firstname' => 'Léa',
        'school_id' => $this->school->id,
    ]);
});

it('admin peut créer un élève et linscrire dans un groupe', function () {
    $group = createGroup($this->school, $this->year);

    $this->post(route('admin.students.store', $this->school), [
        'lastname'   => 'Dupont',
        'firstname'  => 'Hugo',
        'group_ids'  => [$group->id],
    ])->assertRedirect();

    $student = Student::where('lastname', 'Dupont')->firstOrFail();
    expect($student->groups()->where('groups.id', $group->id)->exists())->toBeTrue();
});

it('la création déchoue si le nom est manquant', function () {
    $this->post(route('admin.students.store', $this->school), [
        'firstname' => 'Léa',
    ])->assertInvalid(['lastname']);

    $this->assertDatabaseCount('students', 0);
});

// --- Update ---

it('admin peut modifier un élève de son école', function () {
    $student = Student::factory()->create(['school_id' => $this->school->id]);

    $this->patch(route('admin.students.update', [$this->school, $student]), [
        'lastname'  => 'Nouveau',
        'firstname' => 'Prénom',
    ])->assertRedirect();

    expect($student->fresh()->lastname)->toBe('Nouveau');
});

it('admin ne peut pas modifier un élève dune autre école', function () {
    [, $otherSchool] = createUserWithSchool('admin');
    $student = Student::factory()->create(['school_id' => $otherSchool->id]);

    $this->patch(route('admin.students.update', [$this->school, $student]), [
        'lastname'  => 'Modifié',
        'firstname' => 'Prénom',
    ])->assertForbidden();
});

// --- Destroy ---

it('admin peut supprimer un élève de son école', function () {
    $student = Student::factory()->create(['school_id' => $this->school->id]);

    $this->delete(route('admin.students.destroy', [$this->school, $student]))
        ->assertRedirect();

    $this->assertDatabaseMissing('students', ['id' => $student->id]);
});

it('admin ne peut pas supprimer un élève dune autre école', function () {
    [, $otherSchool] = createUserWithSchool('admin');
    $student = Student::factory()->create(['school_id' => $otherSchool->id]);

    $this->delete(route('admin.students.destroy', [$this->school, $student]))
        ->assertForbidden();
});
