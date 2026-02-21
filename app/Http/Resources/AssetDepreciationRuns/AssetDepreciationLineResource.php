<?php

namespace App\Http\Resources\AssetDepreciationRuns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDepreciationLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_depreciation_run_id' => $this->asset_depreciation_run_id,
            'asset_id' => $this->asset_id,
            'asset' => $this->whenLoaded('asset', function () {
                return [
                    'id' => $this->asset->id,
                    'name' => $this->asset->name,
                    'asset_code' => $this->asset->asset_code,
                ];
            }),
            'amount' => (float) $this->amount,
            'accumulated_before' => (float) $this->accumulated_before,
            'accumulated_after' => (float) $this->accumulated_after,
            'book_value_after' => (float) $this->book_value_after,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
