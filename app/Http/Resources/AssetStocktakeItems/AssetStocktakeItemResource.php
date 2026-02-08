<?php

namespace App\Http\Resources\AssetStocktakeItems;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetStocktakeItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stocktake_reference' => $this->stocktake?->reference,
            'stocktake_date' => $this->stocktake?->performed_at?->toDateString() ?? $this->stocktake?->planned_at?->toDateString(),
            'branch' => $this->stocktake?->branch?->name,
            'expected_location' => $this->expectedLocation?->name,
            'found_location' => $this->foundLocation?->name,
            'result' => $this->result,
            'notes' => $this->notes,
            'checked_at' => $this->checked_at?->toIso8601String(),
            'checked_by' => $this->checkedBy?->name,
        ];
    }
}
