<?php

test('la raíz manda al login cuando no hay sesión', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('la raíz manda al dashboard con sesión iniciada', function () {
    $this->actingAs(App\Models\User::factory()->create());

    $this->get('/')->assertRedirect(route('dashboard'));
});
