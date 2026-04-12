<?php

namespace App\Http\Resources\Reports;

use App\Http\Resources\Concerns\BuildsStockMovementInventoryResourceData;
use App\Models\StockMovement;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StockMovement $resource
 */
/**
 * @mixin \App\Models\StockMovement
 */
class InventoryValuationReportResource extends JsonResource
{
    use BuildsStockMovementInventoryResourceData;

    public function toArray($request): array
    {
        return $this->stockMovementInventoryResourceData(includeUnit: true);
    }
}
