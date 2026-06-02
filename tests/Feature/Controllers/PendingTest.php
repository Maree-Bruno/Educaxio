<?php

use App\Models\School;
use App\Models\Subject;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('pending page renders with user requests, available schools, and subjects', function () {
    $id = uniqid();
    $school = School::create(['name' => "École {$id}", 'slug' => "ecole-{$id}"]);
    $subject = Subject::firstOrCreate(['name' => 'Mathématiques']);
    createJoinRequest($this->user, $school);

    $this->get(route('pending'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Pending'));
});

test('user can request to join a school', function () {
    $id = uniqid();
    $school = School::create(['name' => "École {$id}", 'slug' => "ecole-{$id}"]);

    $this->post(route('pending.join-requests.store'), [
        'school_id' => $school->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('school_join_requests', [
        'user_id' => $this->user->id,
        'school_id' => $school->id,
        'status' => 'pending',
    ]);
});

test('user cannot request to join the same school twice', function () {
    $id = uniqid();
    $school = School::create(['name' => "École {$id}", 'slug' => "ecole-{$id}"]);
    createJoinRequest($this->user, $school);

    $this->post(route('pending.join-requests.store'), [
        'school_id' => $school->id,
    ])->assertStatus(422);
});

test('user can cancel a pending join request', function () {
    $id = uniqid();
    $school = School::create(['name' => "École {$id}", 'slug' => "ecole-{$id}"]);
    $joinRequest = createJoinRequest($this->user, $school);

    $this->delete(route('pending.join-requests.destroy', $joinRequest))
        ->assertRedirect();

    $this->assertDatabaseMissing('school_join_requests', ['id' => $joinRequest->id]);
});

test('user cannot cancel another user\'s join request', function () {
    $otherUser = User::factory()->create();
    $id = uniqid();
    $school = School::create(['name' => "École {$id}", 'slug' => "ecole-{$id}"]);
    $joinRequest = createJoinRequest($otherUser, $school);

    $this->delete(route('pending.join-requests.destroy', $joinRequest))
        ->assertForbidden();
});

test('user cannot cancel an approved join request', function () {
    $id = uniqid();
    $school = School::create(['name' => "École {$id}", 'slug' => "ecole-{$id}"]);
    $joinRequest = createJoinRequest($this->user, $school, 'approved');

    $this->delete(route('pending.join-requests.destroy', $joinRequest))
        ->assertStatus(422);
});

test('user can sync subjects', function () {
    $subjectA = Subject::firstOrCreate(['name' => 'Mathématiques']);
    $subjectB = Subject::firstOrCreate(['name' => 'Français']);
    $this->user->subjects()->attach($subjectA->id);

    $this->patch(route('pending.subjects.sync'), [
        'subject_ids' => [$subjectB->id],
    ])->assertRedirect();

    expect($this->user->subjects()->pluck('subjects.id')->toArray())
        ->toBe([$subjectB->id]);
});

test('unauthenticated user is redirected from pending index', function () {
    $this->post('/logout');

    $this->get(route('pending'))->assertRedirect();
});

test('unauthenticated user is redirected from join request store', function () {
    $this->post('/logout');
    $id = uniqid();
    $school = School::create(['name' => "École {$id}", 'slug' => "ecole-{$id}"]);

    $this->post(route('pending.join-requests.store'), [
        'school_id' => $school->id,
    ])->assertRedirect();
});

test('unauthenticated user is redirected from join request destroy', function () {
    $this->post('/logout');
    $otherUser = User::factory()->create();
    $id = uniqid();
    $school = School::create(['name' => "École {$id}", 'slug' => "ecole-{$id}"]);
    $joinRequest = createJoinRequest($otherUser, $school);

    $this->delete(route('pending.join-requests.destroy', $joinRequest))
        ->assertRedirect();
});

test('unauthenticated user is redirected from subjects sync', function () {
    $this->post('/logout');

    $this->patch(route('pending.subjects.sync'), ['subject_ids' => []])
        ->assertRedirect();
});
