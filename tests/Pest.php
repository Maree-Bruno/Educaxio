<?php

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\School;
use App\Models\SchoolJoinRequest;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function createUserWithSchool(string $role = 'admin'): array
{
    $user = User::factory()->create();
    $id = uniqid();
    $school = School::create(['name' => "École Test {$id}", 'slug' => "ecole-test-{$id}"]);
    $school->users()->attach($user->id, ['role' => $role]);

    return [$user, $school];
}

function attachAcademicYear(School $school, string $year = '2025-2026'): AcademicYear
{
    $academicYear = AcademicYear::firstOrCreate(['year' => $year]);
    $school->academicYears()->syncWithoutDetaching([$academicYear->id]);

    return $academicYear;
}

function attachSubject(School $school, string $name = 'Mathématiques'): Subject
{
    $subject = Subject::firstOrCreate(['name' => $name]);
    $school->subjects()->attach($subject->id);

    return $subject;
}

function createGroup(School $school, AcademicYear $year, string $grade = '3', string $name = 'A'): Group
{
    return Group::create([
        'grade' => $grade,
        'name' => $name,
        'slug' => "{$school->slug}-{$grade}-{$name}",
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
    ]);
}

function createJoinRequest(User $user, School $school, string $status = 'pending'): SchoolJoinRequest
{
    return SchoolJoinRequest::create([
        'user_id' => $user->id,
        'school_id' => $school->id,
        'status' => $status,
    ]);
}
