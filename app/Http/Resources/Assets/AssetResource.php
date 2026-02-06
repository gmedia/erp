<?php

namespace App\Http\Resources\Assets;

use App\Models\Asset;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Asset $resource */
class AssetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'asset_code' => $this->resource->asset_code,
            'name' => $this->resource->name,
            'serial_number' => $this->resource->serial_number,
            'barcode' => $this->resource->barcode,
            
            'category' => [
                'id' => $this->resource->asset_category_id,
                'name' => $this->resource->category?->name,
            ],
            'model' => [
                'id' => $this->resource->asset_model_id,
                'name' => $this->resource->model?->model_name,
            ],
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ],
            'location' => [
                'id' => $this->resource->asset_location_id,
                'name' => $this->resource->location?->name,
            ],
            'department' => [
                'id' => $this->resource->department_id,
                'name' => $this->resource->department?->name,
            ],
            'employee' => [
                'id' => $this->resource->employee_id,
                'name' => $this->resource->employee?->name,
            ],
            'supplier' => [
                'id' => $this->resource->supplier_id,
                'name' => $this->resource->supplier?->name,
            ],
            
            'purchase_date' => $this->resource->purchase_date?->toIso8601String(),
            'purchase_cost' => (string) $this->resource->purchase_cost,
            'currency' => $this->resource->currency,
            'warranty_end_date' => $this->resource->warranty_end_date?->toIso8601String(),
            
            'status' => $this->resource->status,
            'condition' => $this->resource->condition,
            'notes' => $this->resource->notes,
            
            'depreciation_method' => $this->resource->depreciation_method,
            'depreciation_start_date' => $this->resource->depreciation_start_date?->toIso8601String(),
            'useful_life_months' => $this->resource->useful_life_months,
            'salvage_value' => (string) $this->resource->salvage_value,
            'accumulated_depreciation' => (string) $this->resource->accumulated_depreciation,
            'book_value' => (string) $this->resource->book_value,
            
            'depreciation_expense_account_id' => $this->resource->depreciation_expense_account_id,
            'accumulated_depr_account_id' => $this->resource->accumulated_depr_account_id,
            
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
