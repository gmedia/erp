<?php

use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('users');

/**
 * Helper function to create a form request with validation rules applied.
 */
function makeUpdateUserRequest(array $data, ?Employee $employee = null): array
{
    $employee = $employee ?? Employee::factory()->create(['user_id' => null]);

    $request = new UpdateUserRequest();
    $request->setRouteResolver(function () use ($employee) {
        return new class($employee) {
            private Employee $employee;

            public function __construct(Employee $employee)
            {
                $this->employee = $employee;
            }

            public function parameter(string $name): ?Employee
            {
                return $name === 'employee' ? $this->employee : null;
            }
        };
    });

    $validator = Validator::make($data, $request->rules());

    return [
        'passes' => $validator->passes(),
        'errors' => $validator->errors()->toArray(),
    ];
}

describe('UpdateUserRequest Validation', function () {
    test('validation passes with valid data for new user', function () {
        $result = makeUpdateUserRequest([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        expect($result['passes'])->toBeTrue();
    });

    test('name is required', function () {
        $result = makeUpdateUserRequest([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        expect($result['passes'])->toBeFalse()
            ->and($result['errors'])->toHaveKey('name');
    });

    test('email is required', function () {
        $result = makeUpdateUserRequest([
            'name' => 'John Doe',
            'password' => 'password123',
        ]);

        expect($result['passes'])->toBeFalse()
            ->and($result['errors'])->toHaveKey('email');
    });

    test('email must be valid email format', function () {
        $result = makeUpdateUserRequest([
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        expect($result['passes'])->toBeFalse()
            ->and($result['errors'])->toHaveKey('email');
    });

    test('password is required for new user', function () {
        $employee = Employee::factory()->create(['user_id' => null]);

        $result = makeUpdateUserRequest([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            // password is missing
        ], $employee);

        expect($result['passes'])->toBeFalse()
            ->and($result['errors'])->toHaveKey('password');
    });

    test('password is optional for existing user', function () {
        $existingUser = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $existingUser->id]);

        $result = makeUpdateUserRequest([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            // password is optional
        ], $employee);

        expect($result['passes'])->toBeTrue();
    });

    test('password must be at least 8 characters', function () {
        $result = makeUpdateUserRequest([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
        ]);

        expect($result['passes'])->toBeFalse()
            ->and($result['errors'])->toHaveKey('password');
    });

    test('email must be unique', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $result = makeUpdateUserRequest([
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        expect($result['passes'])->toBeFalse()
            ->and($result['errors'])->toHaveKey('email');
    });

    test('email unique constraint ignores current user when updating', function () {
        $existingUser = User::factory()->create(['email' => 'same@example.com']);
        $employee = Employee::factory()->create(['user_id' => $existingUser->id]);

        $result = makeUpdateUserRequest([
            'name' => 'Updated Name',
            'email' => 'same@example.com', // Same email should pass
        ], $employee);

        expect($result['passes'])->toBeTrue();
    });

    test('name must be max 255 characters', function () {
        $result = makeUpdateUserRequest([
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        expect($result['passes'])->toBeFalse()
            ->and($result['errors'])->toHaveKey('name');
    });

    test('email must be max 255 characters', function () {
        $result = makeUpdateUserRequest([
            'name' => 'John Doe',
            'email' => str_repeat('a', 250) . '@example.com',
            'password' => 'password123',
        ]);

        expect($result['passes'])->toBeFalse()
            ->and($result['errors'])->toHaveKey('email');
    });
});
