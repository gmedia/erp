<?php

use App\Exports\SupplierExport;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('suppliers');

describe('SupplierExport', function () {

    test('query applies search filter across name and email fields', function () {
        Supplier::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Supplier::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Supplier::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $export = new SupplierExport(['search' => 'doe']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('John Doe');
    });

    test('query applies exact branch filter', function () {
        $branchA = Branch::factory()->create(['name' => 'Branch A']);
        $branchB = Branch::factory()->create(['name' => 'Branch B']);

        Supplier::factory()->create(['branch_id' => $branchA->id]);
        Supplier::factory()->create(['branch_id' => $branchB->id]);

        $export = new SupplierExport(['branch_id' => $branchA->id]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->branch->name)->toBe('Branch A');
    });

    test('query applies exact category filter', function () {
        $category = \App\Models\SupplierCategory::factory()->create();
        Supplier::factory()->create(['category_id' => $category->id]);
        Supplier::factory()->create(); // different category

        $export = new SupplierExport(['category_id' => $category->id]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->category_id)->toBe($category->id);
    });

    test('query applies exact status filter', function () {
        Supplier::factory()->create(['status' => 'active']);
        Supplier::factory()->create(['status' => 'inactive']);

        $export = new SupplierExport(['status' => 'active']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->status)->toBe('active');
    });

    test('map function returns correct data', function () {
        $category = \App\Models\SupplierCategory::factory()->make(['name' => 'Electronics']);
        $supplier = Supplier::factory()->make([
            'id' => 1,
            'name' => 'Test Supplier',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'status' => 'active',
            'created_at' => '2023-01-01 10:00:00',
        ]);
        $supplier->setRelation('branch', Branch::factory()->make(['name' => 'Test Branch']));
        $supplier->setRelation('category', $category);

        $export = new SupplierExport([]);
        $mapped = $export->map($supplier);

        expect($mapped)->toBe([
            1,
            'Test Supplier',
            'test@example.com',
            '1234567890',
            '123 Test St',
            'Test Branch',
            'Electronics',
            'Active',
            '2023-01-01T10:00:00+00:00',
        ]);
    });

    test('handles empty filters gracefully', function () {
        Supplier::factory()->count(5)->create();

        $export = new SupplierExport([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(5);
    });

});
