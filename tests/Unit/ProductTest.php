<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product has correct relationships', function () {
    $category = ProductCategory::factory()->create();
    $unit = Unit::factory()->create();
    $branch = Branch::factory()->create();

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'branch_id' => $branch->id,
    ]);

    expect($product->category)->toBeInstanceOf(ProductCategory::class)
        ->and($product->category->id)->toBe($category->id)
        ->and($product->unit)->toBeInstanceOf(Unit::class)
        ->and($product->unit->id)->toBe($unit->id)
        ->and($product->branch)->toBeInstanceOf(Branch::class)
        ->and($product->branch->id)->toBe($branch->id);
});

test('product has scope for active status', function () {
    Product::factory()->create(['status' => 'active']);
    Product::factory()->create(['status' => 'inactive']);

    expect(Product::active()->count())->toBe(1);
});

test('product has scope for type', function () {
    Product::factory()->create(['type' => 'finished_good']);
    Product::factory()->create(['type' => 'raw_material']);

    expect(Product::where('type', 'finished_good')->count())->toBe(1);
});
