<?php

use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('customer-categories');

test('factory creates a valid customer category', function () {
    $category = CustomerCategory::factory()->create();

    assertDatabaseHas('customer_categories', ['id' => $category->id]);

    expect($category->getAttributes())->toMatchArray([
        'name' => $category->name,
    ]);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new CustomerCategory)->getFillable();

    expect($fillable)->toBe([
        'name',
    ]);
});
