<?php

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('factory creates a valid customer', function () {
    $customer = Customer::factory()->create();

    assertDatabaseHas('customers', ['id' => $customer->id]);

    expect($customer->getAttributes())->toMatchArray([
        'name' => $customer->name,
        'email' => $customer->email,
        'phone' => $customer->phone,
        'address' => $customer->address,
        'branch_id' => $customer->branch_id,
        'customer_type' => $customer->customer_type,
        'status' => $customer->status,
        'notes' => $customer->notes,
    ]);
});

test('customer belongs to a branch', function () {
    $branch = Branch::factory()->create();
    $customer = Customer::factory()->create(['branch_id' => $branch->id]);

    expect($customer->branch)->toBeInstanceOf(Branch::class)
        ->and($customer->branch->id)->toBe($branch->id);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Customer)->getFillable();

    expect($fillable)->toBe([
        'name',
        'email',
        'phone',
        'address',
        'branch_id',
        'customer_type',
        'status',
        'notes',
    ]);
});
