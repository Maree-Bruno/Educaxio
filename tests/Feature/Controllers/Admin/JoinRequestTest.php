<?php

use App\Models\School;
use App\Models\SchoolJoinRequest;
use App\Models\User;

beforeEach(function () {
    [$this->admin, $this->school] = createUserWithSchool('admin');
    $this->teacher = User::factory()->create();
    $this->joinRequest = createJoinRequest($this->teacher, $this->school);
});

test('admin can approve a join request', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.join-requests.approve', [$this->school, $this->joinRequest]))
        ->assertRedirect();

    expect($this->joinRequest->refresh()->status)->toBe('approved');

    $this->assertTrue(
        $this->school->users()
            ->where('users.id', $this->teacher->id)
            ->wherePivot('role', 'teacher')
            ->exists()
    );
});

test('admin can reject a join request', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.join-requests.reject', [$this->school, $this->joinRequest]))
        ->assertRedirect();

    expect($this->joinRequest->refresh()->status)->toBe('rejected');
});

test('admin cannot approve a join request belonging to a different school', function () {
    $id = uniqid();
    $otherSchool = School::create(['name' => "Autre École {$id}", 'slug' => "autre-ecole-{$id}"]);
    $otherUser = User::factory()->create();
    $otherRequest = createJoinRequest($otherUser, $otherSchool);

    $this->actingAs($this->admin)
        ->patch(route('admin.join-requests.approve', [$this->school, $otherRequest]))
        ->assertForbidden();
});

test('non-admin teacher cannot approve join requests', function () {
    $teacher = User::factory()->create();
    $this->school->users()->attach($teacher->id, ['role' => 'teacher']);

    $this->actingAs($teacher)
        ->patch(route('admin.join-requests.approve', [$this->school, $this->joinRequest]))
        ->assertForbidden();
});

test('unauthenticated user cannot approve join requests', function () {
    $this->patch(route('admin.join-requests.approve', [$this->school, $this->joinRequest]))
        ->assertRedirect();
});