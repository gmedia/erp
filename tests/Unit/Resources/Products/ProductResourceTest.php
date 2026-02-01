<?php

use App\Http\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

test('ProductResource transforms product correctly', function () {
    $category = ProductCategory::factory()->create(['name' => 'Tools']);
    $unit = Unit::factory()->create(['name' => 'Kilo', 'symbol' => 'kg']);
    $branch = Branch::factory()->create(['name' => 'Main Branch']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'branch_id' => $branch->id,
        'cost' => 10.50,
        'selling_price' => 15.75,
        'created_at' => '2023-01-01 12:00:00',
    ]);

    $resource = new ProductResource($product);
    $data = $resource->toArray(request());

    expect($data['id'])->toBe($product->id)
        ->and($data['name'])->toBe($product->name)
        ->and($data['category']['name'])->toBe('Tools')
        ->and($data['unit']['symbol'])->toBe('kg')
        ->and($data['branch']['name'])->toBe('Main Branch')
        ->and($data['cost'])->toBe('10.50')
        ->and($data['selling_price'])->toBe('15.75')
        ->and($data['created_at'])->toBe($product->created_at->toIso8601String());
});
