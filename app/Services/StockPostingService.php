<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\SupplierReturn;
use Illuminate\Support\Facades\Auth;

/**
 * Single source of truth for stock posting.
 *
 * Every document that changes stock (goods receipt, supplier return, stock
 * adjustment, stock transfer dispatch/receive) goes through here so that the
 * movement ledger, `balance_after` chain and derived stock snapshots stay
 * consistent (W7-04). Balance is recomputed from the ledger in
 * (moved_at, id) order, which is the ordering every report relies on.
 */
class StockPostingService
{
    /**
     * Rebuild the `balance_after` chain for one or more product+warehouse
     * scopes. Pass null to rebuild every scope.
     *
     * @param  array<int, array{0:int,1:int}>|null  $scopes
     */
    public function rebuildBalances(?array $scopes = null): void
    {
        $query = StockMovement::query();

        if ($scopes !== null) {
            $query->where(function ($outer) use ($scopes): void {
                foreach ($scopes as [$productId, $warehouseId]) {
                    $outer->orWhere(function ($inner) use ($productId, $warehouseId): void {
                        $inner->where('product_id', $productId)
                            ->where('warehouse_id', $warehouseId);
                    });
                }
            });
        }

        $grouped = $query
            ->orderBy('product_id')
            ->orderBy('warehouse_id')
            ->orderBy('moved_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (StockMovement $movement): string => $movement->product_id . ':' . $movement->warehouse_id);

        foreach ($grouped as $movements) {
            $balance = 0.0;

            foreach ($movements as $movement) {
                $balance += (float) $movement->quantity_in - (float) $movement->quantity_out;

                if ((float) $movement->balance_after !== $balance) {
                    $movement->forceFill(['balance_after' => $balance])->saveQuietly();
                }
            }
        }
    }

    public function postGoodsReceipt(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->status !== 'confirmed') {
            return;
        }

        $goodsReceipt->load('items');
        $scopes = [];

        foreach ($goodsReceipt->items as $item) {
            $quantity = (float) $item->quantity_accepted;

            if ($quantity <= 0) {
                continue;
            }

            $this->record([
                'movement_type' => 'goods_receipt',
                'reference_type' => GoodsReceipt::class,
                'reference_id' => $goodsReceipt->id,
                'reference_number' => $goodsReceipt->gr_number,
                'warehouse_id' => (int) $goodsReceipt->warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'unit_cost' => $item->unit_price,
                'notes' => $goodsReceipt->notes,
                'moved_at' => $goodsReceipt->receipt_date->copy()->startOfDay(),
                'created_by' => $goodsReceipt->confirmed_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $goodsReceipt->warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    public function postSupplierReturn(SupplierReturn $supplierReturn): void
    {
        if ($supplierReturn->status !== 'confirmed') {
            return;
        }

        $supplierReturn->load('items');
        $scopes = [];

        foreach ($supplierReturn->items as $item) {
            $quantity = (float) $item->quantity_returned;

            if ($quantity <= 0) {
                continue;
            }

            $this->record([
                'movement_type' => 'supplier_return',
                'reference_type' => SupplierReturn::class,
                'reference_id' => $supplierReturn->id,
                'reference_number' => $supplierReturn->return_number,
                'warehouse_id' => (int) $supplierReturn->warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'unit_cost' => $item->unit_price,
                'notes' => $supplierReturn->notes,
                'moved_at' => $supplierReturn->return_date->copy()->startOfDay(),
                'created_by' => $supplierReturn->confirmed_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $supplierReturn->warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    public function postStockAdjustment(StockAdjustment $stockAdjustment): void
    {
        if ($stockAdjustment->status !== 'approved') {
            return;
        }

        $stockAdjustment->load('items');
        $scopes = [];

        foreach ($stockAdjustment->items as $item) {
            $quantity = (float) $item->quantity_adjusted;

            if ($quantity === 0.0) {
                continue;
            }

            $this->record([
                'movement_type' => $quantity > 0 ? 'adjustment_in' : 'adjustment_out',
                'reference_type' => StockAdjustment::class,
                'reference_id' => $stockAdjustment->id,
                'reference_number' => $stockAdjustment->adjustment_number,
                'warehouse_id' => (int) $stockAdjustment->warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => $quantity > 0 ? abs($quantity) : 0,
                'quantity_out' => $quantity < 0 ? abs($quantity) : 0,
                'unit_cost' => $item->unit_cost,
                'notes' => $stockAdjustment->notes,
                'moved_at' => $stockAdjustment->adjustment_date->copy()->startOfDay(),
                'created_by' => $stockAdjustment->approved_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $stockAdjustment->warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    /**
     * Dispatch moves stock out of the source warehouse. Posted once the
     * transfer is in transit (or already received).
     */
    public function postStockTransferDispatch(StockTransfer $stockTransfer): void
    {
        if (! in_array($stockTransfer->status, ['in_transit', 'received'], true)) {
            return;
        }

        $stockTransfer->load('items');
        $scopes = [];

        foreach ($stockTransfer->items as $item) {
            $quantity = (float) $item->quantity;

            if ($quantity <= 0) {
                continue;
            }

            $this->record([
                'movement_type' => 'transfer_out',
                'reference_type' => StockTransfer::class,
                'reference_id' => $stockTransfer->id,
                'reference_number' => $stockTransfer->transfer_number,
                'warehouse_id' => (int) $stockTransfer->from_warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'unit_cost' => $item->unit_cost,
                'notes' => $stockTransfer->notes,
                'moved_at' => $stockTransfer->transfer_date->copy()->startOfDay(),
                'created_by' => $stockTransfer->shipped_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $stockTransfer->from_warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    /**
     * Receive moves stock into the destination warehouse.
     */
    public function postStockTransferReceive(StockTransfer $stockTransfer): void
    {
        if ($stockTransfer->status !== 'received') {
            return;
        }

        $stockTransfer->load('items');
        $scopes = [];

        foreach ($stockTransfer->items as $item) {
            $quantity = (float) $item->quantity_received;

            if ($quantity <= 0) {
                continue;
            }

            $this->record([
                'movement_type' => 'transfer_in',
                'reference_type' => StockTransfer::class,
                'reference_id' => $stockTransfer->id,
                'reference_number' => $stockTransfer->transfer_number,
                'warehouse_id' => (int) $stockTransfer->to_warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'unit_cost' => $item->unit_cost,
                'notes' => $stockTransfer->notes,
                'moved_at' => ($stockTransfer->received_at ?? $stockTransfer->transfer_date)->copy()->startOfDay(),
                'created_by' => $stockTransfer->received_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $stockTransfer->to_warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    /**
     * Idempotent upsert keyed by reference + type + warehouse + product.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes): StockMovement
    {
        $movement = StockMovement::query()->updateOrCreate(
            [
                'movement_type' => $attributes['movement_type'],
                'reference_type' => $attributes['reference_type'],
                'reference_id' => $attributes['reference_id'],
                'warehouse_id' => $attributes['warehouse_id'],
                'product_id' => $attributes['product_id'],
            ],
            $attributes + ['balance_after' => 0],
        );

        return $movement;
    }
}
