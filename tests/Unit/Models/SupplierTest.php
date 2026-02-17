<?php

use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('suppliers');

test('factory creates a valid supplier', function () {
    $supplier = Supplier::factory()->create();

    assertDatabaseHas('suppliers', ['id' => $supplier->id]);

    expect($supplier->getAttributes())->toMatchArray([
        'name' => $supplier->name,
        'email' => $supplier->email,
        'phone' => $supplier->phone,
        'address' => $supplier->address,
        'branch_id' => $supplier->branch_id,
        'category_id' => $supplier->category_id,
        'status' => $supplier->status,
    ]);
});

test('supplier belongs to a branch', function () {
    $branch = Branch::factory()->create();
    $supplier = Supplier::factory()->create(['branch_id' => $branch->id]);

    expect($supplier->branch)->toBeInstanceOf(Branch::class)
        ->and($supplier->branch->id)->toBe($branch->id);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Supplier)->getFillable();

    expect($fillable)->toBe([
        'name',
        'email',
        'phone',
        'address',
        'branch_id',
        'category_id',
        'status',
    ]);
});
