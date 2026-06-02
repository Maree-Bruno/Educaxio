<?php

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->school = School::create(['name' => 'École Test', 'slug' => 'ecole-test']);
    $this->student = Student::factory()->create(['school_id' => $this->school->id]);
});

it('belongs to a school', function () {
    expect($this->student->school)->toBeInstanceOf(School::class)
        ->and($this->student->school->id)->toBe($this->school->id);
});

it('can belong to many groups', function () {
    $year = AcademicYear::create(['year' => '2025-2026']);
    $groupA = Group::create([
        'grade' => '3', 'name' => 'A', 'slug' => 'ecole-test-3-a',
        'school_id' => $this->school->id, 'academic_year_id' => $year->id,
    ]);
    $groupB = Group::create([
        'grade' => '3', 'name' => 'B', 'slug' => 'ecole-test-3-b',
        'school_id' => $this->school->id, 'academic_year_id' => $year->id,
    ]);

    $this->student->groups()->attach([$groupA->id, $groupB->id]);

    expect($this->student->groups()->count())->toBe(2);
});

it('has a unique email per school', function () {
    Student::factory()->create([
        'school_id' => $this->school->id,
        'email' => 'duplicate@test.com',
    ]);

    expect(fn () => Student::factory()->create([
        'school_id' => $this->school->id,
        'email' => 'duplicate@test.com',
    ]))->toThrow(QueryException::class);
});

it('the same email can exist in two different schools', function () {
    $otherSchool = School::create(['name' => 'Autre École', 'slug' => 'autre-ecole']);

    Student::factory()->create(['school_id' => $this->school->id, 'email' => 'same@test.com']);
    Student::factory()->create(['school_id' => $otherSchool->id, 'email' => 'same@test.com']);

    $this->assertDatabaseCount('students', 3); // beforeEach + 2 new
});

it('can be created without a user_id', function () {
    $this->assertDatabaseHas('students', ['id' => $this->student->id, 'school_id' => $this->school->id]);
    $this->assertDatabaseMissing('students', ['user_id' => 1]);
});
