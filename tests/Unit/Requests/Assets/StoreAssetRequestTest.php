<?php

namespace Tests\Unit\Requests\Assets;

use App\Http\Requests\Assets\StoreAssetRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('assets');

test('store asset request validation results', function (array $data, bool $shouldPass) {
    // Preparation for foreign keys
    $category = AssetCategory::factory()->create();
    $branch = Branch::factory()->create();

    // Fill IDs if needed
    if (isset($data['asset_category_id']) && $data['asset_category_id'] === 'exists') {
        $data['asset_category_id'] = $category->id;
    }
    if (isset($data['branch_id']) && $data['branch_id'] === 'exists') {
        $data['branch_id'] = $branch->id;
    }

    $request = new StoreAssetRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBe($shouldPass);
})->with([
    'valid data' => [
        [
            'asset_code' => 'ASSET-001',
            'name' => 'Test Asset',
            'asset_category_id' => 'exists',
            'branch_id' => 'exists',
            'purchase_date' => '2023-01-01',
            'purchase_cost' => 1000000,
            'currency' => 'IDR',
            'status' => 'draft',
            'depreciation_method' => 'straight_line',
        ],
        true
    ],
    'missing required fields' => [
        [],
        false
    ],
    'invalid status' => [
        [
            'asset_code' => 'ASSET-002',
            'name' => 'Test Asset',
            'asset_category_id' => 'exists',
            'branch_id' => 'exists',
            'purchase_date' => '2023-01-01',
            'purchase_cost' => 1000000,
            'currency' => 'IDR',
            'status' => 'invalid-status',
            'depreciation_method' => 'straight_line',
        ],
        false
    ],
]);
