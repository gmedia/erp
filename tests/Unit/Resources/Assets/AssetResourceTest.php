<?php

namespace Tests\Unit\Resources\Assets;

use App\Http\Resources\Assets\AssetResource;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('assets', 'asset-unit');

test('asset resource transforms asset correctly', function () {
    $asset = Asset::factory()->create();
    
    // Load relations to avoid lazy loading issues in resource
    $asset->load(['category', 'model', 'branch', 'location', 'department', 'employee', 'supplier']);

    $resource = new AssetResource($asset);
    $request = Request::create('/api/assets/' . $asset->id, 'GET');
    $data = $resource->toArray($request);

    expect($data)->toBeArray()
        ->and($data['id'])->toBe($asset->id)
        ->and($data['asset_code'])->toBe($asset->asset_code)
        ->and($data['name'])->toBe($asset->name)
        ->and($data['serial_number'])->toBe($asset->serial_number)
        ->and($data['barcode'])->toBe($asset->barcode)
        ->and($data['purchase_date'])->toBe($asset->purchase_date->toIso8601String())
        ->and($data['purchase_cost'])->toBe((string)$asset->purchase_cost)
        ->and($data['currency'])->toBe($asset->currency)
        ->and($data['status'])->toBe($asset->status)
        ->and($data['condition'])->toBe($asset->condition)
        ->and($data['useful_life_months'])->toBe($asset->useful_life_months)
        ->and($data['salvage_value'])->toBe((string)$asset->salvage_value)
        ->and($data['accumulated_depreciation'])->toBe((string)$asset->accumulated_depreciation)
        ->and($data['book_value'])->toBe((string)$asset->book_value);
        
    expect($data['category'])->toBeArray()
        ->and($data['model'])->toBeArray()
        ->and($data['branch'])->toBeArray();
});
