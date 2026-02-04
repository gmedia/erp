<?php

use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-categories', 'unit', 'models');

test('it has fillable attributes', function () {
    $category = AssetCategory::create([
        'code' => 'AC001',
        'name' => 'Computers',
        'useful_life_months_default' => 36,
    ]);

    expect($category->code)->toBe('AC001')
        ->and($category->name)->toBe('Computers')
        ->and($category->useful_life_months_default)->toBe(36);
});

test('it casts useful_life_months_default to integer', function () {
    $category = AssetCategory::create([
        'code' => 'AC002',
        'name' => 'Furniture',
        'useful_life_months_default' => '60',
    ]);

    expect($category->useful_life_months_default)->toBe(60)
        ->and($category->useful_life_months_default)->toBeInt();
});
