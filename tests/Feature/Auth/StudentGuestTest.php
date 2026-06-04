<?php

it("can't store a student as a guest", function () {
    $this->post(route('students.store'))->assertRedirect(route('login'));
});

it("can't access a student show page as a guest", function () {
    $this->get(route('students.show', 'nonexistent'))->assertRedirect(route('login'));
});

it("can't update a student as a guest", function () {
    $this->patch(route('students.update', 'nonexistent'))->assertRedirect(route('login'));
});

it("can't delete a student as a guest", function () {
    $this->delete(route('students.destroy', 'nonexistent'))->assertRedirect(route('login'));
});
