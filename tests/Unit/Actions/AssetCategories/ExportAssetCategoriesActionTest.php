<?php

use App\Actions\AssetCategories\ExportAssetCategoriesAction;
use App\Http\Requests\AssetCategories\ExportAssetCategoryRequest;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('asset-categories');

test('export asset categories action execute generates excel file', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');
    
    AssetCategory::factory()->count(3)->create();

    $action = new ExportAssetCategoriesAction();
    $request = Mockery::mock(ExportAssetCategoryRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    
    $result = $action->execute($request);

    $filename = 'asset_categories_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($result->getStatusCode())->toBe(200)
        ->and($result->getData(true))->toHaveKeys(['url', 'filename'])
        ->and($result->getData(true)['filename'])->toBe($filename);

    Excel::assertStored('exports/' . $filename, 'public');
});
