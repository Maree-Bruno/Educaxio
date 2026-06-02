<?php

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;

beforeEach(function () {
    $this->school = School::create(['name' => 'École Test', 'slug' => 'ecole-test']);
});

it('can attach a user with a role', function () {
    $user = User::factory()->create();
    $this->school->users()->attach($user->id, ['role' => 'admin']);

    expect($this->school->users()->count())->toBe(1)
        ->and($this->school->users->first()->pivot->role)->toBe('admin');
});

it('can attach a user as teacher', function () {
    $user = User::factory()->create();
    $this->school->users()->attach($user->id, ['role' => 'teacher']);

    expect($this->school->users->first()->pivot->role)->toBe('teacher');
});

it('can attach subjects via pivot', function () {
    $subject = Subject::create(['name' => 'Mathématiques']);
    $this->school->subjects()->attach($subject->id);

    expect($this->school->subjects()->count())->toBe(1)
        ->and($this->school->subjects->first()->name)->toBe('Mathématiques');
});

it('can attach academic years via pivot', function () {
    $year = AcademicYear::create(['year' => '2025-2026']);
    $this->school->academicYears()->attach($year->id);

    expect($this->school->academicYears()->count())->toBe(1)
        ->and($this->school->academicYears->first()->year)->toBe('2025-2026');
});

it('has many groups', function () {
    $year = AcademicYear::create(['year' => '2025-2026']);
    Group::create([
        'grade' => '3', 'name' => 'A', 'slug' => 'ecole-test-3-a',
        'school_id' => $this->school->id, 'academic_year_id' => $year->id,
    ]);

    expect($this->school->groups()->count())->toBe(1);
});

it('has many students', function () {
    Student::factory()->create(['school_id' => $this->school->id]);

    expect($this->school->students()->count())->toBe(1);
});

it('deleting a school cascades to its groups and students', function () {
    $year = AcademicYear::create(['year' => '2025-2026']);
    Group::create([
        'grade' => '3', 'name' => 'A', 'slug' => 'ecole-test-3-a',
        'school_id' => $this->school->id, 'academic_year_id' => $year->id,
    ]);
    Student::factory()->create(['school_id' => $this->school->id]);

    $this->school->delete();

    $this->assertDatabaseCount('groups', 0);
    $this->assertDatabaseCount('students', 0);
});
