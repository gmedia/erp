<?php

use App\Exports\BookValueDepreciationExport;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('book value depreciation export collects all rows via action export flow', function () {
    $category = AssetCategory::factory()->create();
    $branch = Branch::factory()->create();

    Asset::factory()->count(2)->create([
        'asset_category_id' => $category->id,
        'branch_id' => $branch->id,
        'status' => 'active',
    ]);

    $rows = (new BookValueDepreciationExport)->collection();

    expect($rows)->toHaveCount(2);
});
