<?php

use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('supplier-categories');

test('factory creates a valid supplier category', function () {
    $category = SupplierCategory::factory()->create();

    assertDatabaseHas('supplier_categories', ['id' => $category->id]);

    expect($category->getAttributes())->toMatchArray([
        'name' => $category->name,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new SupplierCategory)->getFillable();

    expect($fillable)->toBe([
        'name',
    ]);
});
