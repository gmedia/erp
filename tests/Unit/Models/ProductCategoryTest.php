<?php

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('product-categories');

test('factory creates a valid product category', function () {
    $productCategory = ProductCategory::factory()->create();

    assertDatabaseHas('product_categories', ['id' => $productCategory->id]);

    expect($productCategory->getAttributes())->toMatchArray([
        'name' => $productCategory->name,
        'description' => $productCategory->description,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new ProductCategory)->getFillable();

    expect($fillable)->toBe(['name', 'description']);
});
