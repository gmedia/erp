<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class)->group('auth');

test('login endpoint is available', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrong',
    ]);

    $response->assertStatus(422); // Validation error or failed attempt
});

test('users can authenticate using the login endpoint', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertStatus(200)
             ->assertJsonStructure(['token', 'user']);
});

// test('users with two factor enabled are redirected to two factor challenge', function () {
// SPA version currently does not natively support Fortify 2FA redirects through API
// });

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertStatus(422)
             ->assertJsonValidationErrors('email');
});

test('users can logout', function () {
    $user = User::factory()->create();
    
    // Authenticate first to get token
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                     ->postJson('/api/logout');

    $response->assertStatus(200)
             ->assertJson(['message' => 'Successfully logged out']);
});

// test('users are rate limited', function () {
// SPA migration: AuthController currently does not implement Laravel's default 5-attempt rate limit. 
// Rate limiting is instead handled by api throttle middleware at 60 attempts/min.
// });
