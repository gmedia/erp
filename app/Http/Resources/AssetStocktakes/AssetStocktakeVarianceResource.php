<?php

namespace App\Http\Resources\AssetStocktakes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetStocktakeVarianceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_stocktake_id' => $this->asset_stocktake_id,
            'stocktake_reference' => $this->whenLoaded('stocktake', fn () => $this->stocktake->reference),
            'asset_id' => $this->asset_id,
            'asset_code' => $this->whenLoaded('asset', fn () => $this->asset->asset_code),
            'asset_name' => $this->whenLoaded('asset', fn () => $this->asset->name),
            'expected_branch_id' => $this->expected_branch_id,
            'expected_branch_name' => $this->whenLoaded('expectedBranch', fn () => $this->expectedBranch->name),
            'expected_location_id' => $this->expected_location_id,
            'expected_location_name' => $this->whenLoaded('expectedLocation', fn () => $this->expectedLocation->name),
            'found_branch_id' => $this->found_branch_id,
            'found_branch_name' => $this->whenLoaded('foundBranch', fn () => $this->foundBranch->name),
            'found_location_id' => $this->found_location_id,
            'found_location_name' => $this->whenLoaded('foundLocation', fn () => $this->foundLocation->name),
            'result' => $this->result,
            'notes' => $this->notes,
            'checked_at' => $this->checked_at?->toIso8601String(),
            'checked_by' => $this->checked_by,
            'checked_by_name' => $this->whenLoaded('checkedBy', fn () => $this->checkedBy->name),
        ];
    }
}
