<?php

use App\Exports\ProductExport;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

describe('ProductExport', function () {

    test('query applies search filter across code, name and description fields', function () {
        Product::factory()->create(['code' => 'ABC-123', 'name' => 'Widget', 'description' => 'A small widget']);
        Product::factory()->create(['code' => 'XYZ-789', 'name' => 'Gadget', 'description' => 'A large gadget']);

        $export = new ProductExport(['search' => 'widg']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Widget');

        $export = new ProductExport(['search' => '789']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->code)->toBe('XYZ-789');
    });

    test('query applies category filter', function () {
        $cat1 = ProductCategory::factory()->create();
        $cat2 = ProductCategory::factory()->create();

        Product::factory()->create(['category_id' => $cat1->id]);
        Product::factory()->create(['category_id' => $cat2->id]);

        $export = new ProductExport(['category_id' => $cat1->id]);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->category_id)->toBe($cat1->id);
    });

    test('query applies unit filter', function () {
        $unit1 = Unit::factory()->create();
        $unit2 = Unit::factory()->create();

        Product::factory()->create(['unit_id' => $unit1->id]);
        Product::factory()->create(['unit_id' => $unit2->id]);

        $export = new ProductExport(['unit_id' => $unit1->id]);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->unit_id)->toBe($unit1->id);
    });

    test('query applies branch filter', function () {
        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();

        Product::factory()->create(['branch_id' => $branch1->id]);
        Product::factory()->create(['branch_id' => $branch2->id]);

        $export = new ProductExport(['branch_id' => $branch1->id]);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->branch_id)->toBe($branch1->id);
    });

    test('query applies type filter', function () {
        Product::factory()->create(['type' => 'finished_good']);
        Product::factory()->create(['type' => 'raw_material']);

        $export = new ProductExport(['type' => 'finished_good']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->type)->toBe('finished_good');
    });

    test('query applies status filter', function () {
        Product::factory()->create(['status' => 'active']);
        Product::factory()->create(['status' => 'inactive']);

        $export = new ProductExport(['status' => 'active']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->status)->toBe('active');
    });

    test('map transforms product data correctly', function () {
        $category = ProductCategory::factory()->create(['name' => 'Electronics']);
        $unit = Unit::factory()->create(['name' => 'Pcs']);
        
        $product = Product::factory()->create([
            'code' => 'P-001',
            'name' => 'Smartphone',
            'type' => 'finished_good',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'cost' => 500.00,
            'selling_price' => 750.00,
            'status' => 'active',
            'created_at' => '2023-01-01 10:00:00',
        ]);

        $export = new ProductExport([]);
        $mapped = $export->map($product);

        expect($mapped)->toBe([
            $product->id,
            'P-001',
            'Smartphone',
            'finished_good',
            'Electronics',
            'Pcs',
            '500.00',
            '750.00',
            'active',
            '2023-01-01T10:00:00+00:00',
        ]);
    });

    test('headings returns correct array', function () {
        $export = new ProductExport([]);
        expect($export->headings())->toBe([
            'ID',
            'Code',
            'Name',
            'Type',
            'Category',
            'Unit',
            'Cost',
            'Selling Price',
            'Status',
            'Created At',
        ]);
    });
});
