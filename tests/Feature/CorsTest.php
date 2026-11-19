<?php

uses()->group('cors');

test('native origins are allowed by configuration', function () {
    $origins = config('cors.allowed_origins');

    expect($origins)->toContain('http://localhost')
        ->toContain('capacitor://localhost');
});

test('cors preflight allows the android webview origin', function () {
    $response = $this->call('OPTIONS', '/api/device-tokens', [], [], [], [
        'HTTP_ORIGIN' => 'http://localhost',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ]);

    $response->assertStatus(204);
    $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost');
});
