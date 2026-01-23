<?php

use App\Domain\Customers\CustomerFilterService;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('applySearch adds where clause for search term', function () {
    $service = new CustomerFilterService;

    Customer::factory()->create(['name' => 'John Doe', 'email' => 'unique@example.com']);
    Customer::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    Customer::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

    $query = Customer::query();
    $service->applySearch($query, 'john', ['name', 'email']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('John Doe');
});

test('applyAdvancedFilters applies branch filter', function () {
    $service = new CustomerFilterService;

    $branchA = Branch::factory()->create(['name' => 'Branch A']);
    $branchB = Branch::factory()->create(['name' => 'Branch B']);

    Customer::factory()->create(['branch_id' => $branchA->id]);
    Customer::factory()->create(['branch_id' => $branchB->id]);

    $query = Customer::query();
    $service->applyAdvancedFilters($query, ['branch_id' => $branchA->id]);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->branch_id)->toBe($branchA->id);
});

test('applyAdvancedFilters applies customer type filter', function () {
    $service = new CustomerFilterService;

    Customer::factory()->create(['customer_type' => 'individual']);
    Customer::factory()->create(['customer_type' => 'company']);

    $query = Customer::query();
    $service->applyAdvancedFilters($query, ['customer_type' => 'company']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->customer_type)->toBe('company');
});

test('applyAdvancedFilters applies status filter', function () {
    $service = new CustomerFilterService;

    Customer::factory()->create(['status' => 'active']);
    Customer::factory()->create(['status' => 'inactive']);

    $query = Customer::query();
    $service->applyAdvancedFilters($query, ['status' => 'inactive']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->status)->toBe('inactive');
});

test('applyAdvancedFilters handles empty filters', function () {
    $service = new CustomerFilterService;

    Customer::factory()->count(3)->create();

    $query = Customer::query();
    $originalCount = $query->count();

    $service->applyAdvancedFilters($query, []);

    expect($query->count())->toBe($originalCount);
});

test('applySorting applies ascending sort when allowed', function () {
    $service = new CustomerFilterService;

    Customer::factory()->create(['name' => 'Z Customer']);
    Customer::factory()->create(['name' => 'A Customer']);

    $query = Customer::query();
    $service->applySorting($query, 'name', 'asc', ['id', 'name', 'email', 'branch_id', 'customer_type', 'status', 'created_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('A Customer')
        ->and($results->last()->name)->toBe('Z Customer');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new CustomerFilterService;

    Customer::factory()->create(['name' => 'A Customer']);
    Customer::factory()->create(['name' => 'Z Customer']);

    $query = Customer::query();
    $service->applySorting($query, 'name', 'desc', ['id', 'name', 'email', 'branch_id', 'customer_type', 'status', 'created_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('Z Customer')
        ->and($results->last()->name)->toBe('A Customer');
});
