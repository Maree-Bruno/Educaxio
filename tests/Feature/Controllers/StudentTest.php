<?php

use App\Models\School;
use App\Models\Student;
use App\Models\AcademicYear;

beforeEach(function () {
    [$this->user, $this->school] = createUserWithSchool();
    $this->year = attachAcademicYear($this->school);
    $this->actingAs($this->user);
});

// --- Create ---

it('shows the student create page', function () {
    $this->get(route('students.create'))->assertOk();
});

it('shows only groups from the user\'s schools in the create form', function () {
    createGroup($this->school, $this->year);

    $otherSchool = School::create(['name' => 'Autre École', 'slug' => 'autre-ecole']);
    $otherYear = AcademicYear::create(['year' => '2024-2025']);
    createGroup($otherSchool, $otherYear, '4', 'B');

    $this->get(route('students.create'))
        ->assertInertia(fn ($page) => $page
            ->component('StudentCreate')
            ->has('groups', 1)
        );
});

// --- Store ---

it('creates a student with school_id derived from the selected group', function () {
    $group = createGroup($this->school, $this->year);

    $this->post(route('students.store'), [
        'lastname' => 'Dupont',
        'firstname' => 'Marie',
        'group_id' => $group->id,
    ])->assertRedirect(route('classlist.show', $group));

    $student = Student::first();
    expect($student->school_id)->toBe($this->school->id)
        ->and($student->groups()->count())->toBe(1);
});

it('creates a student with an explicit school_id when no group is provided', function () {
    $this->post(route('students.store'), [
        'lastname' => 'Martin',
        'firstname' => 'Jean',
        'school_id' => $this->school->id,
    ])->assertRedirect(route('classlist'));

    $student = Student::first();
    expect($student->school_id)->toBe($this->school->id)
        ->and($student->groups()->count())->toBe(0);
});

it('fails to store a student when neither group_id nor school_id is provided', function () {
    $this->post(route('students.store'), [
        'lastname' => 'Dupont',
        'firstname' => 'Marie',
    ])->assertInvalid('school_id');

    $this->assertDatabaseCount('students', 0);
});

it('fails to store a student when required name fields are missing', function () {
    $group = createGroup($this->school, $this->year);

    $this->post(route('students.store'), ['group_id' => $group->id])
        ->assertInvalid(['lastname', 'firstname']);
});

it('can create a student without an email', function () {
    $group = createGroup($this->school, $this->year);

    $this->post(route('students.store'), [
        'lastname' => 'Dupont',
        'firstname' => 'Marie',
        'group_id' => $group->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('students', ['lastname' => 'Dupont', 'email' => null]);
});

// --- Show ---

it('can show a student page', function () {
    $student = Student::factory()->create(['school_id' => $this->school->id]);

    $this->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('StudentShow'));
});

it('shows the student\'s groups on the show page', function () {
    $group = createGroup($this->school, $this->year);
    $student = Student::factory()->create(['school_id' => $this->school->id]);
    $group->students()->attach($student->id);

    $this->get(route('students.show', $student))
        ->assertInertia(fn ($page) => $page
            ->where('student.id', $student->id)
            ->has('student.groups', 1)
        );
});