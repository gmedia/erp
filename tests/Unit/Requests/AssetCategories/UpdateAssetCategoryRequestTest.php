<?php

namespace Tests\Unit\Requests\AssetCategories;

use App\Http\Requests\AssetCategories\UpdateAssetCategoryRequest;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rule;

uses(RefreshDatabase::class)->group('asset-categories');

test('update asset category request authorize returns true', function () {
    $request = new UpdateAssetCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('update asset category request rules are correct', function () {
    $category = AssetCategory::factory()->create();
    $request = new UpdateAssetCategoryRequest();
    
    // Inject the category into the request set for the ignore rule if needed?
    // Actually, we just check if it returns the expected rules structure.
    
    $rules = $request->rules();
    
    expect($rules['name'])->toBe(['required', 'string', 'max:255'])
        ->and($rules['useful_life_months_default'])->toBe(['nullable', 'integer', 'min:0'])
        ->and($rules['code'])->toBeArray()
        ->and($rules['code'][0])->toBe('required');
});
