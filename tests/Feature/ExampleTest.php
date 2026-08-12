<?php

test('guests are redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
    $this->get('/dashboard')->assertRedirect('/login');
});
