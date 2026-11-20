<?php

uses()->group('cors');

test('native origins are allowed by configuration', function () {
    $origins = config('cors.allowed_origins');

    expect($origins)->toContain('http://localhost')
        ->toContain('https://localhost')
        ->toContain('capacitor://localhost');
});

test('cors preflight allows the android http origin', function () {
    $response = $this->call('OPTIONS', '/api/device-tokens', [], [], [], [
        'HTTP_ORIGIN' => 'http://localhost',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,x-requested-with,accept',
    ]);

    $response->assertStatus(204);
    $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost');
});

test('cors preflight allows the capacitor secured origin', function () {
    $response = $this->call('OPTIONS', '/api/login', [], [], [], [
        'HTTP_ORIGIN' => 'https://localhost',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,x-requested-with,accept',
    ]);

    $response->assertStatus(204);
    $response->assertHeader('Access-Control-Allow-Origin', 'https://localhost');
});
