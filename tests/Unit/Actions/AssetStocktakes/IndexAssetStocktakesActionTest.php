<?php

namespace Tests\Unit\Actions\AssetStocktakes;

use App\Actions\AssetStocktakes\IndexAssetStocktakesAction;
use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeRequest;
use App\Models\AssetStocktake;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class)->group('asset-stocktakes');

test('it can index asset stocktakes with filters', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    $stocktakeA = AssetStocktake::factory()->create([
        'branch_id' => $branchA->id,
        'status' => 'draft',
    ]);
    AssetStocktake::factory()->create([
        'branch_id' => $branchB->id,
        'status' => 'completed',
    ]);

    $request = Mockery::mock(IndexAssetStocktakeRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->andReturnUsing(function ($key, $default = null) use ($branchA) {
        $map = [
            'branch_id' => $branchA->id,
            'status' => 'draft',
            'planned_at_from' => null,
            'planned_at_to' => null,
            'sort_by' => 'id',
            'sort_direction' => 'asc',
            'per_page' => 15,
            'page' => 1,
        ];

        return $map[$key] ?? $default;
    });

    $action = app(IndexAssetStocktakesAction::class);
    $result = $action->execute($request);

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->id)->toBe($stocktakeA->id);
});
