<?php

use App\Models\Group;
use App\Models\Lesson;
use App\Models\School;
use App\Models\AcademicYear;

beforeEach(function () {
    [$this->user, $this->school] = createUserWithSchool();
    $this->year = attachAcademicYear($this->school);
    $this->subject = attachSubject($this->school);
    $this->actingAs($this->user);
});

// --- Index ---

it('shows the classlist page to authenticated users', function () {
    $this->get(route('classlist'))->assertOk();
});

it('shows all groups to an admin regardless of lesson assignments', function () {
    $group = createGroup($this->school, $this->year);

    $this->get(route('classlist'))
        ->assertInertia(fn ($page) => $page->has('groups', 1)->where('groups.0.id', $group->id));
});

it('shows no groups to a teacher with no assigned lessons', function () {
    [$teacher, $school] = createUserWithSchool('teacher');
    $year = attachAcademicYear($school);
    createGroup($school, $year);
    $this->actingAs($teacher);

    $this->get(route('classlist'))
        ->assertInertia(fn ($page) => $page->has('groups', 0));
});

it('shows only groups with an assigned lesson to a teacher', function () {
    [$teacher, $school] = createUserWithSchool('teacher');
    $year = attachAcademicYear($school);
    $subject = attachSubject($school);

    $assignedGroup = createGroup($school, $year);
    $otherGroup = createGroup($school, $year, '4', 'B');

    $lesson = Lesson::create(['name' => $subject->name, 'group_id' => $assignedGroup->id, 'subject_id' => $subject->id]);
    $teacher->lessons()->attach($lesson->id);

    $this->actingAs($teacher);

    $this->get(route('classlist'))
        ->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            ->where('groups.0.id', $assignedGroup->id)
        );
});

it('shows only groups from the user\'s schools', function () {
    $myGroup = createGroup($this->school, $this->year);

    $otherSchool = School::create(['name' => 'Autre École', 'slug' => 'autre-ecole']);
    $otherYear = attachAcademicYear($otherSchool, '2024-2025');
    createGroup($otherSchool, $otherYear, '4', 'B');

    $this->get(route('classlist'))
        ->assertInertia(fn ($page) => $page
            ->component('ClassList')
            ->has('groups', 1)
            ->where('groups.0.id', $myGroup->id)
        );
});

it('shows only the user\'s schools in dropdowns', function () {
    $otherSchool = School::create(['name' => 'Autre École', 'slug' => 'autre-ecole']);

    $this->get(route('classlist'))
        ->assertInertia(fn ($page) => $page
            ->has('schools', 1)
            ->where('schools.0.id', $this->school->id)
        );
});

it('can filter groups by school', function () {
    $groupA = createGroup($this->school, $this->year);

    $secondSchool = School::create(['name' => 'Seconde École', 'slug' => 'seconde-ecole']);
    $secondSchool->users()->attach($this->user->id, ['role' => 'teacher']);
    $secondYear = attachAcademicYear($secondSchool, '2024-2025');
    $groupB = createGroup($secondSchool, $secondYear, '4', 'B');

    $this->get(route('classlist', ['school' => $this->school->id]))
        ->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            ->where('groups.0.id', $groupA->id)
        );
});

it('can filter groups by academic year', function () {
    createGroup($this->school, $this->year);

    $otherYear = attachAcademicYear($this->school, '2024-2025');
    $otherGroup = createGroup($this->school, $otherYear, '4', 'B');

    $this->get(route('classlist', ['year' => $otherYear->id]))
        ->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            ->where('groups.0.id', $otherGroup->id)
        );
});

// --- Create ---

it('shows the create form with user\'s schools, years and subjects', function () {
    $this->get(route('classlist.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClassListCreate')
            ->has('schools', 1)
            ->has('academicYears', 1)
            ->has('subjects', 1)
        );
});

// --- Store ---

it('can create a group with a lesson', function () {
    $this->post(route('classlist.store'), [
        'grade' => '3',
        'name' => 'A',
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'subject_id' => $this->subject->id,
    ])->assertRedirect();

    $this->assertDatabaseCount('groups', 1);
    $this->assertDatabaseCount('lessons', 1);
    $this->assertDatabaseHas('groups', ['grade' => '3', 'name' => 'A', 'school_id' => $this->school->id]);
    $this->assertDatabaseMissing('lessons', ['user_id' => $this->user->id]);
    $this->assertDatabaseMissing('lessons', ['academic_year_id' => $this->year->id]);
});

it('can create a group without a lesson when no subject is provided', function () {
    $this->post(route('classlist.store'), [
        'grade' => '3',
        'name' => 'A',
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
    ])->assertRedirect();

    $this->assertDatabaseCount('groups', 1);
    $this->assertDatabaseCount('lessons', 0);
});

it('fails to store a group when required fields are missing', function () {
    $this->post(route('classlist.store'), [])
        ->assertInvalid(['grade', 'name', 'school_id', 'academic_year_id']);

    $this->assertDatabaseCount('groups', 0);
});

// --- Show ---

it('can show a group page', function () {
    $group = createGroup($this->school, $this->year);

    $this->get(route('classlist.show', $group))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('ClassListShow'));
});

// --- Update ---

it('can update a group and its lesson', function () {
    $group = createGroup($this->school, $this->year);
    Lesson::create(['name' => $this->subject->name, 'group_id' => $group->id, 'subject_id' => $this->subject->id]);

    $newSubject = attachSubject($this->school, 'Français');

    $this->patch(route('classlist.update', $group), [
        'grade' => '4',
        'name' => 'B',
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'subject_id' => $newSubject->id,
    ])->assertRedirect();

    expect($group->fresh()->grade)->toBe('4')
        ->and($group->fresh()->name)->toBe('B');

    $this->assertDatabaseHas('lessons', ['subject_id' => $newSubject->id]);
    $this->assertDatabaseMissing('lessons', ['user_id' => $this->user->id]);
});

it('removes the lesson when subject is cleared on update', function () {
    $group = createGroup($this->school, $this->year);
    Lesson::create(['name' => $this->subject->name, 'group_id' => $group->id, 'subject_id' => $this->subject->id]);

    $this->patch(route('classlist.update', $group), [
        'grade' => $group->grade,
        'name' => $group->name,
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
    ])->assertRedirect();

    $this->assertDatabaseCount('lessons', 0);
});

// --- Destroy ---

it('can delete a group', function () {
    $group = createGroup($this->school, $this->year);

    $this->delete(route('classlist.destroy', $group))
        ->assertRedirect(route('classlist'));

    $this->assertDatabaseCount('groups', 0);
});

it('deleting a group cascades to its lessons', function () {
    $group = createGroup($this->school, $this->year);
    Lesson::create(['name' => $this->subject->name, 'group_id' => $group->id, 'subject_id' => $this->subject->id]);

    $this->delete(route('classlist.destroy', $group));

    $this->assertDatabaseCount('lessons', 0);
});