<?php

use App\Exports\SupplierCategoryExport;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-categories');

describe('SupplierCategoryExport', function () {

    test('query applies search filter', function () {
        SupplierCategory::factory()->create(['name' => 'Services']);
        SupplierCategory::factory()->create(['name' => 'Goods']);

        $export = new SupplierCategoryExport(['search' => 'Serv']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Services');
    });

    test('map function returns correct data', function () {
        $category = SupplierCategory::factory()->make([
            'id' => 1,
            'name' => 'Test Cat',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new SupplierCategoryExport([]);
        $mapped = $export->map($category);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('Test Cat');
    });

    test('headings returns correct columns', function () {
        $export = new SupplierCategoryExport([]);
        
        expect($export->headings())->toContain('ID', 'Name', 'Created At');
    });
});
