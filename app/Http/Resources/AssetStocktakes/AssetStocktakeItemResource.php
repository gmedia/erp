<?php

namespace App\Http\Resources\AssetStocktakes;

use App\Http\Resources\Assets\AssetResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetStocktakeItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_stocktake_id' => $this->asset_stocktake_id,
            'asset_id' => $this->asset_id,
            'asset' => new AssetResource($this->whenLoaded('asset')),
            'expected_branch_id' => $this->expected_branch_id,
            'expected_location_id' => $this->expected_location_id,
            'found_branch_id' => $this->found_branch_id,
            'found_location_id' => $this->found_location_id,
            'result' => $this->result,
            'notes' => $this->notes,
            'checked_at' => $this->checked_at?->toISOString(),
            'checked_by' => $this->checked_by,
        ];
    }
}
