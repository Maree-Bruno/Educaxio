<?php

use App\Models\Group;
use App\Models\Lesson;
use App\Models\School;
use App\Models\Subject;
use App\Models\AcademicYear;

beforeEach(function () {
    [$this->user, $this->school] = createUserWithSchool();
    $this->year = attachAcademicYear($this->school);
    $this->subject = attachSubject($this->school);
    $this->actingAs($this->user);
});

it('can assign lessons to the authenticated user', function () {
    $group = createGroup($this->school, $this->year);
    $lesson = Lesson::create(['name' => $this->subject->name, 'group_id' => $group->id, 'subject_id' => $this->subject->id]);

    $this->put(route('lessons.update'), ['lesson_ids' => [$lesson->id]])
        ->assertRedirect();

    expect($this->user->lessons()->count())->toBe(1);
});

it('can remove all lesson assignments by sending an empty array', function () {
    $group = createGroup($this->school, $this->year);
    $lesson = Lesson::create(['name' => $this->subject->name, 'group_id' => $group->id, 'subject_id' => $this->subject->id]);
    $this->user->lessons()->attach($lesson->id);

    $this->put(route('lessons.update'), ['lesson_ids' => []])
        ->assertRedirect();

    expect($this->user->lessons()->count())->toBe(0);
});

it('can remove all lesson assignments by sending no lesson_ids key', function () {
    $group = createGroup($this->school, $this->year);
    $lesson = Lesson::create(['name' => $this->subject->name, 'group_id' => $group->id, 'subject_id' => $this->subject->id]);
    $this->user->lessons()->attach($lesson->id);

    $this->put(route('lessons.update'), [])
        ->assertRedirect();

    expect($this->user->lessons()->count())->toBe(0);
});

it('cannot assign a lesson from another school', function () {
    $otherSchool = School::create(['name' => 'Autre École', 'slug' => 'autre-ecole']);
    $otherYear = AcademicYear::create(['year' => '2024-2025']);
    $otherGroup = createGroup($otherSchool, $otherYear);
    $otherSubject = Subject::firstOrCreate(['name' => 'Histoire']);
    $otherLesson = Lesson::create(['name' => $otherSubject->name, 'group_id' => $otherGroup->id, 'subject_id' => $otherSubject->id]);

    $this->put(route('lessons.update'), ['lesson_ids' => [$otherLesson->id]])
        ->assertInvalid('lesson_ids.0');

    expect($this->user->lessons()->count())->toBe(0);
});

it('syncs lessons replacing previous assignments', function () {
    $group = createGroup($this->school, $this->year);
    $subjectA = $this->subject;
    $subjectB = attachSubject($this->school, 'Français');

    $lessonA = Lesson::create(['name' => $subjectA->name, 'group_id' => $group->id, 'subject_id' => $subjectA->id]);
    $lessonB = Lesson::create(['name' => $subjectB->name, 'group_id' => $group->id, 'subject_id' => $subjectB->id]);

    $this->user->lessons()->attach($lessonA->id);

    $this->put(route('lessons.update'), ['lesson_ids' => [$lessonB->id]])
        ->assertRedirect();

    expect($this->user->lessons()->pluck('lessons.id')->toArray())
        ->toBe([$lessonB->id]);
});

it('redirects guests to login', function () {
    auth()->logout();
    $this->put(route('lessons.update'), [])->assertRedirect(route('login'));
});
