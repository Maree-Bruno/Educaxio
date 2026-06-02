<?php

it("can't access the classlist index as a guest", function () {
    $this->get(route('classlist'))->assertRedirect(route('login'));
});

it("can't access the classlist create page as a guest", function () {
    $this->get(route('classlist.create'))->assertRedirect(route('login'));
});

it("can't store a group as a guest", function () {
    $this->post(route('classlist.store'))->assertRedirect(route('login'));
});

it("can't access a group show page as a guest", function () {
    $this->get('/classlist/some-slug')->assertRedirect(route('login'));
});

it("can't update a group as a guest", function () {
    $this->patch('/classlist/some-slug')->assertRedirect(route('login'));
});

it("can't delete a group as a guest", function () {
    $this->delete('/classlist/some-slug')->assertRedirect(route('login'));
});
