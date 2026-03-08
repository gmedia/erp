<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class)->group('auth');

test('reset password link endpoint is available', function () {
    $response = $this->postJson('/api/forgot-password', ['email' => 'invalid@example.com']);

    $response->assertStatus(422);
});

test('reset password link can be requested via api', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->postJson('/api/forgot-password', ['email' => $user->email]);
    $response->assertStatus(200);

    Notification::assertSentTo($user, ResetPassword::class);
});

// Note: Password reset screen rendering is handled by the SPA frontend in the new architecture. 
// We only need to test the API endpoints for requesting and submitting the reset.

test('password can be reset with valid token via api', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson('/api/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->postJson('/api/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200);

        return true;
    });
});

test('password cannot be reset with invalid token via api', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors('email');
});
