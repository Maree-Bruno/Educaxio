<?php

use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    [, $this->school] = createUserWithSchool('admin');
    $year = attachAcademicYear($this->school);
    $subject = attachSubject($this->school);
    $this->group = createGroup($this->school, $year, '3', 'A');

    $teacher = User::factory()->create();
    $this->school->users()->attach($teacher->id, ['role' => 'teacher']);
    $lesson = $this->group->lessons()->create(['subject_id' => $subject->id]);
    $lesson->users()->attach($teacher->id);

    $this->actingAs($teacher);
});

it('dashboard enseignant affiche le message de bienvenue', function () {
    visit(route('dashboard'))
        ->assertSee('Bonjour');
});

it('liste de classes affiche la classe de lenseignant', function () {
    visit(route('classlist'))
        ->assertSee('3A');
});

it('fiche de classe affiche le nom de la classe', function () {
    visit(route('classlist.show', $this->group))
        ->assertSee('3A');
});

it('fiche de classe affiche le prénom dun élève inscrit', function () {
    $student = Student::factory()->create(['school_id' => $this->school->id]);
    $this->group->students()->attach($student->id);

    visit(route('classlist.show', $this->group))
        ->assertSee($student->firstname);
});

it('page présences charge sans erreur', function () {
    visit(route('attendances'))
        ->assertSee('Présences');
});

it('agenda charge et affiche la section journal', function () {
    visit(route('agenda'))
        ->assertSee('Journal de classe');
});

it('cliquer Voir la classe depuis la liste navigue vers la fiche', function () {
    visit(route('classlist'))
        ->click('Voir la classe')
        ->assertSee('3A')
        ->assertPathContains('classes');
});
