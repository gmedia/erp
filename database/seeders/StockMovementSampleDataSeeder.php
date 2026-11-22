<?php

namespace Database\Seeders;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockMovementSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = User::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? User::query()->value('id');

        // Hanya adjustment approved yang menghasilkan movement, dan semua item
        // non-nol ikut tercatat (W5-19).
        $adjustmentItems = StockAdjustmentItem::query()
            ->with(['stockAdjustment'])
            ->whereHas('stockAdjustment', fn ($query) => $query->where('status', 'approved'))
            ->where('quantity_adjusted', '<>', 0)
            ->get();

        foreach ($adjustmentItems as $adjustmentItem) {
            $adjustment = $adjustmentItem->stockAdjustment;
            $movementType = (float) $adjustmentItem->quantity_adjusted > 0
                ? 'adjustment_in'
                : 'adjustment_out';

            StockMovement::updateOrCreate(
                [
                    'movement_type' => $movementType,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'product_id' => $adjustmentItem->product_id,
                ],
                [
                    'quantity_in' => $movementType === 'adjustment_in'
                        ? abs((float) $adjustmentItem->quantity_adjusted)
                        : 0,
                    'quantity_out' => $movementType === 'adjustment_out'
                        ? abs((float) $adjustmentItem->quantity_adjusted)
                        : 0,
                    'balance_after' => (float) $adjustmentItem->quantity_after,
                    'unit_cost' => $adjustmentItem->unit_cost,
                    'average_cost_after' => null,
                    'reference_number' => $adjustment->adjustment_number,
                    'notes' => $adjustment->notes,
                    'moved_at' => $adjustment->adjustment_date->startOfDay(),
                    'created_by' => $adminUserId,
                ],
            );
        }

        $transferItem = StockTransferItem::query()
            ->with(['stockTransfer'])
            ->whereHas('stockTransfer', fn ($query) => $query->where('status', 'received'))
            ->first()
            ?? StockTransferItem::query()->with(['stockTransfer'])->first();

        if ($transferItem?->stockTransfer) {
            $transfer = $transferItem->stockTransfer;

            $sourceBalance = $this->currentBalance(
                (int) $transferItem->product_id,
                (int) $transfer->from_warehouse_id,
                (int) $transfer->id,
            );
            $outQuantity = (float) $transferItem->quantity;
            $receivedQuantity = (float) $transferItem->quantity_received;
            $targetBalance = $this->currentBalance(
                (int) $transferItem->product_id,
                (int) $transfer->to_warehouse_id,
                (int) $transfer->id,
            );

            StockMovement::updateOrCreate(
                [
                    'movement_type' => 'transfer_out',
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'product_id' => $transferItem->product_id,
                ],
                [
                    'quantity_in' => 0,
                    'quantity_out' => $outQuantity,
                    'balance_after' => max(0, $sourceBalance - $outQuantity),
                    'unit_cost' => $transferItem->unit_cost,
                    'average_cost_after' => null,
                    'reference_number' => $transfer->transfer_number,
                    'notes' => $transfer->notes,
                    'moved_at' => $transfer->transfer_date ? $transfer->transfer_date->startOfDay() : now(),
                    'created_by' => $adminUserId,
                ],
            );

            StockMovement::updateOrCreate(
                [
                    'movement_type' => 'transfer_in',
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $transferItem->product_id,
                ],
                [
                    'quantity_in' => $receivedQuantity,
                    'quantity_out' => 0,
                    'balance_after' => $targetBalance + $receivedQuantity,
                    'unit_cost' => $transferItem->unit_cost,
                    'average_cost_after' => null,
                    'reference_number' => $transfer->transfer_number,
                    'notes' => $transfer->notes,
                    'moved_at' => $transfer->transfer_date ? $transfer->transfer_date->startOfDay() : now(),
                    'created_by' => $adminUserId,
                ],
            );
        }
    }

    /**
     * Saldo berjalan terakhir untuk produk+gudang, mengabaikan gerakan dari
     * transfer yang sedang diproses agar seeder tetap idempoten.
     */
    private function currentBalance(int $productId, int $warehouseId, ?int $excludeTransferId = null): float
    {
        return (float) (StockMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($excludeTransferId !== null, fn ($query) => $query
                ->whereNot(fn ($inner) => $inner
                    ->where('reference_type', StockTransfer::class)
                    ->where('reference_id', $excludeTransferId)))
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);
    }
}
