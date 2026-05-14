<?php

use App\Models\Student;

it("can't access the student create page as a guest", function () {
    $this->get(route('students.create'))->assertRedirect(route('login'));
});

it("can't store a student as a guest", function () {
    $this->post(route('students.store'))->assertRedirect(route('login'));
});

it("can't access a student show page as a guest", function () {
    $this->get('/students/1')->assertRedirect(route('login'));
});

it("can't update a student as a guest", function () {
    $this->patch('/students/1')->assertRedirect(route('login'));
});

it("can't delete a student as a guest", function () {
    $this->delete('/students/1')->assertRedirect(route('login'));
});