<?php

use App\Models\Student;

beforeEach(function () {
    [$this->user, $this->school] = createUserWithSchool();
    $this->year = attachAcademicYear($this->school);
    $this->actingAs($this->user);
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
