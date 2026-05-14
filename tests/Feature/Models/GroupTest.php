<?php

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;

beforeEach(function () {
    $this->school = School::create(['name' => 'École Test', 'slug' => 'ecole-test']);
    $this->year = AcademicYear::create(['year' => '2025-2026']);
    $this->group = Group::create([
        'grade' => '3', 'name' => 'A', 'slug' => 'ecole-test-3-a',
        'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
    ]);
});

it('belongs to a school', function () {
    expect($this->group->school)->toBeInstanceOf(School::class)
        ->and($this->group->school->id)->toBe($this->school->id);
});

it('belongs to an academic year', function () {
    expect($this->group->academicYear)->toBeInstanceOf(AcademicYear::class)
        ->and($this->group->academicYear->year)->toBe('2025-2026');
});

it('can have many students via pivot', function () {
    $student = Student::factory()->create(['school_id' => $this->school->id]);
    $this->group->students()->attach($student->id);

    expect($this->group->students()->count())->toBe(1);
});

it('can have many lessons', function () {
    $subject = Subject::create(['name' => 'Maths']);
    Lesson::create(['name' => 'Maths', 'group_id' => $this->group->id, 'subject_id' => $subject->id]);

    expect($this->group->lessons()->count())->toBe(1);
});

it('uses slug as route key', function () {
    expect($this->group->getRouteKeyName())->toBe('slug');
});

it('can be created without a user_id', function () {
    $this->assertDatabaseHas('groups', [
        'id' => $this->group->id,
        'school_id' => $this->school->id,
    ]);
    $this->assertDatabaseMissing('groups', ['user_id' => 1]);
});