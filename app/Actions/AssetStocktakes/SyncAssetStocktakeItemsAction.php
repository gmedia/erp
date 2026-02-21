<?php

namespace App\Actions\AssetStocktakes;

use App\Models\AssetStocktake;
use App\Models\AssetStocktakeItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SyncAssetStocktakeItemsAction
{
    public function execute(AssetStocktake $stocktake, array $data): void
    {
        DB::transaction(function () use ($stocktake, $data) {
            $userId = Auth::id();
            $now = now();

            foreach ($data['items'] as $itemData) {
                AssetStocktakeItem::updateOrCreate(
                    [
                        'asset_stocktake_id' => $stocktake->id,
                        'asset_id' => $itemData['asset_id'],
                    ],
                    [
                        'expected_branch_id' => $itemData['expected_branch_id'],
                        'expected_location_id' => $itemData['expected_location_id'] ?? null,
                        'found_branch_id' => $itemData['found_branch_id'] ?? null,
                        'found_location_id' => $itemData['found_location_id'] ?? null,
                        'result' => $itemData['result'],
                        'notes' => $itemData['notes'] ?? null,
                        'checked_at' => $now,
                        'checked_by' => $userId,
                    ]
                );
            }
            
            // Auto update stocktake status to in_progress if it was draft
            if ($stocktake->status === 'draft') {
                $stocktake->update(['status' => 'in_progress']);
            }
        });
    }
}
