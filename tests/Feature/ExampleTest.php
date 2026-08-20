<?php

test('redirects to the panel login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard/login');
});
