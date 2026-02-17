<?php

use App\DTOs\Employees\UpdateEmployeeData;

uses()->group('employees');

test('fromArray creates DTO with all fields', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '123-456-7890',
        'department' => 'Engineering',
        'position' => 'Developer',
        'salary' => '75000',
        'hire_date' => '2023-01-15',
    ];

    $dto = UpdateEmployeeData::fromArray($data);

    expect($dto->name)->toBe('John Doe');
    expect($dto->email)->toBe('john@example.com');
    expect($dto->phone)->toBe('123-456-7890');
    expect($dto->department)->toBe('Engineering');
    expect($dto->position)->toBe('Developer');
    expect($dto->salary)->toBe('75000');
    expect($dto->hire_date)->toBe('2023-01-15');
});

test('fromArray creates DTO with partial fields', function () {
    $data = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ];

    $dto = UpdateEmployeeData::fromArray($data);

    expect($dto->name)->toBe('Jane Doe');
    expect($dto->email)->toBe('jane@example.com');
    expect($dto->phone)->toBeNull();
    expect($dto->department)->toBeNull();
    expect($dto->position)->toBeNull();
    expect($dto->salary)->toBeNull();
    expect($dto->hire_date)->toBeNull();
});

test('fromArray creates DTO with empty array', function () {
    $dto = UpdateEmployeeData::fromArray([]);

    expect($dto->name)->toBeNull();
    expect($dto->email)->toBeNull();
    expect($dto->phone)->toBeNull();
    expect($dto->department)->toBeNull();
    expect($dto->position)->toBeNull();
    expect($dto->salary)->toBeNull();
    expect($dto->hire_date)->toBeNull();
});

test('toArray returns only non-null values', function () {
    $dto = new UpdateEmployeeData(
        name: 'John Doe',
        email: 'john@example.com',
        phone: null,
        department: 'Engineering',
        position: null,
        salary: '75000',
        hire_date: null
    );

    $result = $dto->toArray();

    expect($result)->toBe([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'department' => 'Engineering',
        'salary' => '75000',
    ]);
});

test('toArray returns empty array when all values are null', function () {
    $dto = new UpdateEmployeeData(
        name: null,
        email: null,
        phone: null,
        department: null,
        position: null,
        salary: null,
        hire_date: null
    );

    $result = $dto->toArray();

    expect($result)->toBe([]);
});

test('toArray includes phone when not null', function () {
    $dto = new UpdateEmployeeData(
        name: null,
        email: null,
        phone: '123-456-7890',
        department: null,
        position: null,
        salary: null,
        hire_date: null
    );

    $result = $dto->toArray();

    expect($result)->toBe([
        'phone' => '123-456-7890',
    ]);
});

test('toArray includes position when not null', function () {
    $dto = new UpdateEmployeeData(
        name: null,
        email: null,
        phone: null,
        department: null,
        position: 'Manager',
        salary: null,
        hire_date: null
    );

    $result = $dto->toArray();

    expect($result)->toBe([
        'position' => 'Manager',
    ]);
});

test('toArray includes hire_date when not null', function () {
    $dto = new UpdateEmployeeData(
        name: null,
        email: null,
        phone: null,
        department: null,
        position: null,
        salary: null,
        hire_date: '2023-01-15'
    );

    $result = $dto->toArray();

    expect($result)->toBe([
        'hire_date' => '2023-01-15',
    ]);
});
