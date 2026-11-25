<?php

namespace App\Actions\Concerns;

use App\Models\StockTransferItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Saldo persediaan dalam perjalanan (issue #495 W9-05): transfer yang sudah
 * dikirim tetapi belum diterima, dinilai qty kirim x biaya kirim (unit_cost).
 */
trait InteractsWithInTransitBalance
{
    /**
     * @return array{total_items: int, total_quantity: string, total_value: string, rows: array<int, array<string, string|int>>}
     */
    protected function buildInTransitSummary(Request $request): array
    {
        $rows = $this->inTransitItemsQuery($request)
            ->orderBy('stock_transfers.transfer_date')
            ->orderBy('stock_transfers.id')
            ->get()
            ->map(function (StockTransferItem $item): array {
                $quantity = (float) $item->quantity;
                $unitCost = (float) $item->unit_cost;

                return [
                    'transfer_id' => (int) $item->getAttribute('stock_transfer_id'),
                    'transfer_number' => (string) $item->getAttribute('transfer_number'),
                    'transfer_date' => (string) $item->getAttribute('transfer_date'),
                    'product_name' => (string) $item->getAttribute('product_name'),
                    'from_warehouse' => (string) $item->getAttribute('from_warehouse_name'),
                    'to_warehouse' => (string) $item->getAttribute('to_warehouse_name'),
                    'quantity' => number_format($quantity, 2, '.', ''),
                    'unit_cost' => number_format($unitCost, 2, '.', ''),
                    'value' => number_format($quantity * $unitCost, 2, '.', ''),
                ];
            })
            ->values();

        $totalQuantity = (float) $rows->sum(fn (array $row): float => (float) $row['quantity']);
        $totalValue = (float) $rows->sum(fn (array $row): float => (float) $row['value']);

        return [
            'total_items' => $rows->count(),
            'total_quantity' => number_format($totalQuantity, 2, '.', ''),
            'total_value' => number_format($totalValue, 2, '.', ''),
            'rows' => $rows->all(),
        ];
    }

    /**
     * @return Builder<StockTransferItem>
     */
    private function inTransitItemsQuery(Request $request): Builder
    {
        $query = StockTransferItem::query()
            ->join('stock_transfers', 'stock_transfer_items.stock_transfer_id', '=', 'stock_transfers.id')
            ->join('products', 'stock_transfer_items.product_id', '=', 'products.id')
            ->join('warehouses as from_wh', 'stock_transfers.from_warehouse_id', '=', 'from_wh.id')
            ->join('warehouses as to_wh', 'stock_transfers.to_warehouse_id', '=', 'to_wh.id')
            ->where('stock_transfers.status', 'in_transit')
            ->select([
                'stock_transfer_items.*',
                'stock_transfers.transfer_number as transfer_number',
                'stock_transfers.transfer_date as transfer_date',
                'stock_transfers.id as stock_transfer_id',
                'products.name as product_name',
                'from_wh.name as from_warehouse_name',
                'to_wh.name as to_warehouse_name',
            ]);

        if ($request->filled('warehouse_id')) {
            $query->where('stock_transfers.from_warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('from_wh.branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('stock_transfer_items.product_id', $request->integer('product_id'));
        }

        return $query;
    }
}
