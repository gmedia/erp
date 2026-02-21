<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookValueDepreciationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'asset_code' => $this->asset_code,
            'name' => $this->name,
            'category_name' => $this->category?->name,
            'branch_name' => $this->branch?->name,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'purchase_cost' => (float) $this->purchase_cost,
            'salvage_value' => (float) $this->salvage_value,
            'useful_life_months' => $this->useful_life_months,
            'accumulated_depreciation' => (float) $this->accumulated_depreciation,
            'book_value' => (float) $this->book_value,
        ];
    }
}
