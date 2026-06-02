<?php

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $school = School::create(['name' => 'École Test', 'slug' => 'ecole-test']);
    $year = AcademicYear::create(['year' => '2025-2026']);
    $this->group = Group::create([
        'grade' => '3', 'name' => 'A', 'slug' => 'ecole-test-3-a',
        'school_id' => $school->id, 'academic_year_id' => $year->id,
    ]);
    $this->subject = Subject::create(['name' => 'Mathématiques']);
    $this->lesson = Lesson::create([
        'name' => 'Mathématiques',
        'group_id' => $this->group->id,
        'subject_id' => $this->subject->id,
    ]);
});

it('belongs to a group', function () {
    expect($this->lesson->group)->toBeInstanceOf(Group::class)
        ->and($this->lesson->group->id)->toBe($this->group->id);
});

it('belongs to a subject', function () {
    expect($this->lesson->subject)->toBeInstanceOf(Subject::class)
        ->and($this->lesson->subject->name)->toBe('Mathématiques');
});

it('can be created without a user_id', function () {
    $this->assertDatabaseHas('lessons', ['id' => $this->lesson->id]);
    $this->assertDatabaseMissing('lessons', ['user_id' => 1]);
});

it('can be created without an academic_year_id', function () {
    $this->assertDatabaseHas('lessons', ['id' => $this->lesson->id]);
    $this->assertDatabaseMissing('lessons', ['academic_year_id' => 1]);
});

it('enforces unique subject per group', function () {
    expect(fn () => Lesson::create([
        'name' => 'Doublon',
        'group_id' => $this->group->id,
        'subject_id' => $this->subject->id,
    ]))->toThrow(QueryException::class);
});
