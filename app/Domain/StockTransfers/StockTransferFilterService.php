<?php

namespace App\Domain\StockTransfers;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class StockTransferFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\StockTransfer>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'from_warehouse_id' => 'from_warehouse_id',
            'to_warehouse_id' => 'to_warehouse_id',
            'status' => 'status',
        ]);

        $this->applyDateRanges($query, $filters, [
            'transfer_date' => ['from' => 'transfer_date_from', 'to' => 'transfer_date_to'],
            'expected_arrival_date' => ['from' => 'expected_arrival_date_from', 'to' => 'expected_arrival_date_to'],
        ]);
    }
}
