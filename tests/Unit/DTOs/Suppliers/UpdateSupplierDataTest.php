<?php

use App\DTOs\Suppliers\UpdateSupplierData;

uses()->group('suppliers');

test('fromArray creates supplier dto with provided fields', function () {
    $dto = UpdateSupplierData::fromArray([
        'name' => 'Acme Supplier',
        'phone' => '021-555-0101',
        'category' => 'raw-materials',
    ]);

    expect($dto->name)->toBe('Acme Supplier')
        ->and($dto->phone)->toBe('021-555-0101')
        ->and($dto->category)->toBe('raw-materials')
        ->and($dto->status)->toBeNull();
});

test('toArray returns only non-null supplier values', function () {
    $dto = new UpdateSupplierData(
        name: 'Acme Supplier',
        email: null,
        phone: '021-555-0101',
        address: 'Jl. Industri 7',
        branch_id: 8,
        category: 'raw-materials',
        status: 'active',
    );

    expect($dto->toArray())->toBe([
        'name' => 'Acme Supplier',
        'phone' => '021-555-0101',
        'address' => 'Jl. Industri 7',
        'branch_id' => 8,
        'category' => 'raw-materials',
        'status' => 'active',
    ]);
});

test('toArray keeps zero-like supplier values', function () {
    $dto = new UpdateSupplierData(branch_id: 0);

    expect($dto->toArray())->toBe([
        'branch_id' => 0,
    ]);
});
