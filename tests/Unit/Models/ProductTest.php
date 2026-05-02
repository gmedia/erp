<?php

use App\Models\BillOfMaterial;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

test('product has correct relationships', function () {
    $category = ProductCategory::factory()->create();
    $unit = Unit::factory()->create();
    $branch = Branch::factory()->create();

    $product = Product::factory()->create([
        'product_category_id' => $category->id,
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

test('product has prices relationship', function () {
    $product = Product::factory()->create();
    ProductPrice::factory()->count(3)->create(['product_id' => $product->id]);

    expect($product->prices)->toHaveCount(3);
});

test('product has stocks relationship', function () {
    $product = Product::factory()->create();
    ProductStock::factory()->count(2)->create(['product_id' => $product->id]);

    expect($product->stocks)->toHaveCount(2);
});

test('product has bill of materials relationship', function () {
    $finishedProduct = Product::factory()->finishedGood()->create();
    BillOfMaterial::factory()->count(2)->create(['finished_product_id' => $finishedProduct->id]);

    expect($finishedProduct->billOfMaterials)->toHaveCount(2);
});

test('product has ofType scope', function () {
    Product::factory()->create(['type' => 'service']);
    Product::factory()->create(['type' => 'raw_material']);

    expect(Product::ofType('service')->count())->toBe(1);
});
