<?php

namespace Tests\Unit\Resources\Assets;

use App\Http\Resources\Assets\AssetCollection;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('assets');

test('asset collection transforms assets correctly', function () {
    $assets = Asset::factory()->count(1)->create();
    
    $collection = new AssetCollection($assets);
    $request = Request::create('/api/assets', 'GET');
    
    // ResourceCollection::toArray returns an array, but the collection property inside might be mixed.
    // Let's use toResponse and getData to be sure of the final structure if toArray is ambiguous.
    $data = $collection->toArray($request);

    // AssetCollection.php specifically returns ['data' => $this->collection]
    $items = $data['data'];

    // In Pest/PHPUnit, if 'data' is a collection object, we want it as an array to test keys.
    if ($items instanceof \Illuminate\Support\Collection) {
        $items = $items->toArray();
    }

    expect($items)->toBeArray();
    expect(count($items))->toBeGreaterThanOrEqual(1);
        
    // items[0] should be from AssetResource, so it's a resource or array.
    $firstItem = $items[0];
    if (is_object($firstItem) && method_exists($firstItem, 'toArray')) {
        $firstItem = $firstItem->toArray($request);
    }

    expect($firstItem)->toHaveKeys(['id', 'asset_code', 'name', 'status']);
});
