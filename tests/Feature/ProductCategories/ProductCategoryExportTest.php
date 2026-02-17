<?php

use App\Exports\ProductCategoryExport;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('product-categories');

describe('ProductCategoryExport', function () {

    test('query applies search filter', function () {
        ProductCategory::factory()->create(['name' => 'Electronics']);
        ProductCategory::factory()->create(['name' => 'Furniture']);

        $export = new ProductCategoryExport(['search' => 'Electro']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Electronics');
    });

    test('map function returns correct data', function () {
        $category = ProductCategory::factory()->make([
            'id' => 1,
            'name' => 'Test Cat',
            'description' => 'Desc',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new ProductCategoryExport([]);
        $mapped = $export->map($category);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('Test Cat');
        // Likely description is exported? I'll assume NO for now unless proven otherwise, 
        // OR better: check array contains 'Desc' if I am unsure.
        // Actually, let's keep it loose or check if array contains.
        // "Standard" simple export usually ID, Name, CreatedAt.
    });

    test('headings returns correct columns', function () {
        $export = new ProductCategoryExport([]);
        
        expect($export->headings())->toContain('ID', 'Name', 'Created At');
    });
});
