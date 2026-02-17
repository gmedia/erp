<?php

namespace Tests\Unit\Actions\Assets;

use App\Actions\Assets\IndexAssetsAction;
use App\Domain\Assets\AssetFilterService;
use App\Http\Requests\Assets\IndexAssetRequest;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('assets');

test('index assets action returns paginated results', function () {
    Asset::factory()->count(20)->create();
    
    $request = new IndexAssetRequest(['per_page' => 10]);
    $service = new AssetFilterService();
    $action = new IndexAssetsAction($service);
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(10)
        ->and($result->total())->toBe(20);
});
