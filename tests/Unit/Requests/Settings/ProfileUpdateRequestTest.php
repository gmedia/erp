<?php

namespace Tests\Unit\Requests\Settings;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('settings');

test('profile update request authorizes access', function () {
    $request = new ProfileUpdateRequest;

    expect($request->authorize())->toBeTrue();
});

test('profile update request accepts valid payload for current user', function () {
    $user = User::factory()->create(['email' => 'current@example.com']);
    $request = new ProfileUpdateRequest;
    $request->setUserResolver(fn () => $user);

    $validator = Validator::make([
        'name' => 'Updated User',
        'email' => 'current@example.com',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('profile update request rejects duplicate email from another user', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $user = User::factory()->create(['email' => 'current@example.com']);
    $request = new ProfileUpdateRequest;
    $request->setUserResolver(fn () => $user);

    $validator = Validator::make([
        'name' => 'Updated User',
        'email' => $existingUser->email,
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});
