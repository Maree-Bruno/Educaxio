<?php

use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    [$this->user, $this->school] = createUserWithSchool('admin');
    $this->year = attachAcademicYear($this->school);
    $this->subject = attachSubject($this->school);
    $this->actingAs($this->user);
});

it('dashboard admin affiche les statistiques de lécole', function () {
    visit(route('dashboard'))
        ->assertSee('Élèves')
        ->assertSee('Cours');
});

it('liste des élèves affiche le nom de lélève créé', function () {
    $group = createGroup($this->school, $this->year);
    $student = Student::factory()->create(['school_id' => $this->school->id]);
    $student->groups()->attach($group->id);

    visit(route('admin.students.index', $this->school))
        ->assertSee($student->lastname);
});

it('liste des professeurs affiche le nom du professeur', function () {
    $teacher = User::factory()->create(['name' => 'Marie Curie']);
    $this->school->users()->attach($teacher->id, ['role' => 'teacher']);

    visit(route('admin.teachers.index', $this->school))
        ->assertSee('Marie Curie');
});

it('cliquer sur une classe dans attribution des cours révèle la matière', function () {
    $group = createGroup($this->school, $this->year);
    $group->lessons()->create(['subject_id' => $this->subject->id]);

    visit(route('admin.lessons.index', $this->school))
        ->assertSee('3A')
        ->click('3A')
        ->assertSee('Mathématiques');
});

it('gestion des années scolaires affiche lannée courante', function () {
    visit(route('admin.academic-years.index', $this->school))
        ->assertSee('2025-2026');
});

it('liste de classes affiche la classe créée', function () {
    createGroup($this->school, $this->year, '3', 'A');

    visit(route('classlist'))
        ->assertSee('3A');
});
