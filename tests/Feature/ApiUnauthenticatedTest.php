<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('api-auth');

test('unauthenticated API request without JSON Accept header returns 401 JSON', function () {
    $response = $this->get('/api/departments');

    $response->assertStatus(401);
    $response->assertHeader('Content-Type', 'application/json');
    $response->assertJsonStructure(['message']);
});

test('unauthenticated API request with JSON Accept header returns 401 JSON', function () {
    $response = $this->getJson('/api/departments');

    $response->assertUnauthorized();
    $response->assertJsonStructure(['message']);
});
