<?php

use App\Exports\CustomerExport;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customers');

describe('CustomerExport', function () {

    test('query applies search filter across name and email fields', function () {
        Customer::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Customer::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Customer::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $export = new CustomerExport(['search' => 'doe']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('John Doe');
    });

    test('query applies exact branch filter', function () {
        $branchA = Branch::factory()->create(['name' => 'Branch A']);
        $branchB = Branch::factory()->create(['name' => 'Branch B']);

        Customer::factory()->create(['branch_id' => $branchA->id]);
        Customer::factory()->create(['branch_id' => $branchB->id]);

        $export = new CustomerExport(['branch_id' => $branchA->id]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->branch->name)->toBe('Branch A');
    });

    test('query applies exact category filter', function () {
        $categoryA = \App\Models\CustomerCategory::factory()->create();
        $categoryB = \App\Models\CustomerCategory::factory()->create();

        Customer::factory()->create(['category_id' => $categoryA->id]);
        Customer::factory()->create(['category_id' => $categoryB->id]);

        $export = new CustomerExport(['category_id' => $categoryA->id]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->category_id)->toBe($categoryA->id);
    });

    test('query applies exact status filter', function () {
        Customer::factory()->create(['status' => 'active']);
        Customer::factory()->create(['status' => 'inactive']);

        $export = new CustomerExport(['status' => 'inactive']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->status)->toBe('inactive');
    });

    test('query applies sorting', function () {
        Customer::factory()->create(['name' => 'Z Customer']);
        Customer::factory()->create(['name' => 'A Customer']);

        $export = new CustomerExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        expect($results->first()->name)->toBe('A Customer')
            ->and($results->last()->name)->toBe('Z Customer');
    });

    test('headings returns correct columns', function () {
        $export = new CustomerExport([]);
        
        expect($export->headings())->toBe([
            'ID',
            'Name',
            'Email',
            'Phone',
            'Address',
            'Branch',
            'Category',
            'Status',
            'Notes',
            'Created At',
        ]);
    });

    test('map formats data correctly', function () {
        $branch = Branch::factory()->create(['name' => 'Test Branch']);
        $category = \App\Models\CustomerCategory::factory()->create(['name' => 'Test Category']);
        $customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'status' => 'active',
            'notes' => 'Test Notes',
            'created_at' => '2023-01-01 10:00:00',
        ]);

        $export = new CustomerExport([]);
        $mapped = $export->map($customer);

        expect($mapped)->toBe([
            $customer->id,
            'Test Customer',
            'test@example.com',
            '1234567890',
            '123 Test St',
            'Test Branch',
            'Test Category',
            'Active',
            'Test Notes',
            '2023-01-01 10:00:00',
        ]);
    });

    test('handles empty filters gracefully', function () {
        Customer::factory()->count(5)->create();

        $export = new CustomerExport([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(5);
    });

});
