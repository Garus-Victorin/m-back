<?php

test('the welcome page returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk();
});
