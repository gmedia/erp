<?php

namespace App\Http\Controllers;

use App\Actions\AssetStocktakes\SyncAssetStocktakeItemsAction;
use App\Http\Requests\AssetStocktakes\UpdateAssetStocktakeItemRequest;
use App\Http\Resources\AssetStocktakes\AssetStocktakeItemResource;
use App\Models\AssetStocktake;
use App\Models\AssetStocktakeItem;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;

class AssetStocktakeItemController extends Controller
{
    /**
     * Get items for a stocktake.
     * If no items exist, generate expected items from active assets.
     */
    public function getItems(AssetStocktake $assetStocktake): JsonResponse
    {
        $items = $assetStocktake->items()->with(['asset'])->get();

        if ($items->isEmpty()) {
            $assets = Asset::where('branch_id', $assetStocktake->branch_id)
                ->where('status', 'active')
                ->get();

            $items = $assets->map(function ($asset) use ($assetStocktake) {
                $item = new AssetStocktakeItem([
                    'asset_stocktake_id' => $assetStocktake->id,
                    'asset_id' => $asset->id,
                    'expected_branch_id' => $asset->branch_id,
                    'expected_location_id' => $asset->asset_location_id,
                ]);
                $item->setRelation('asset', $asset);
                return $item;
            });
        }

        return AssetStocktakeItemResource::collection($items)->response();
    }

    /**
     * Sync/bulk update items for a stocktake.
     */
    public function syncItems(UpdateAssetStocktakeItemRequest $request, AssetStocktake $assetStocktake): JsonResponse
    {
        (new SyncAssetStocktakeItemsAction())->execute($assetStocktake, $request->validated());

        return response()->json([
            'message' => 'Stocktake items updated successfully.'
        ]);
    }
}
