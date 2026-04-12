<?php

namespace App\Http\Resources\StockMonitor;

use App\Http\Resources\Concerns\BuildsStockMovementInventoryResourceData;
use App\Models\StockMovement;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StockMovement $resource
 */
/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class StockMonitorResource extends JsonResource
{
    use BuildsStockMovementInventoryResourceData;

    public function toArray($request): array
    {
        return $this->stockMovementInventoryResourceData();
    }
}
