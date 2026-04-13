<?php

use App\DTOs\Customers\UpdateCustomerData;

uses()->group('customers');

test('fromArray creates customer dto with provided fields', function () {
    $dto = UpdateCustomerData::fromArray([
        'name' => 'Acme Customer',
        'email' => 'customer@example.com',
        'branch_id' => 12,
        'notes' => null,
    ]);

    expect($dto->name)->toBe('Acme Customer')
        ->and($dto->email)->toBe('customer@example.com')
        ->and($dto->branch_id)->toBe(12)
        ->and($dto->notes)->toBeNull()
        ->and($dto->status)->toBeNull();
});

test('toArray returns only non-null customer values', function () {
    $dto = new UpdateCustomerData(
        name: 'Acme Customer',
        email: 'customer@example.com',
        phone: null,
        address: 'Jl. Merdeka 1',
        branch_id: 12,
        category_id: null,
        status: 'active',
        notes: 'Priority account',
    );

    expect($dto->toArray())->toBe([
        'name' => 'Acme Customer',
        'email' => 'customer@example.com',
        'address' => 'Jl. Merdeka 1',
        'branch_id' => 12,
        'status' => 'active',
        'notes' => 'Priority account',
    ]);
});

test('toArray omits null customer notes value', function () {
    $dto = new UpdateCustomerData(notes: null);

    expect($dto->toArray())->toBe([]);
});
