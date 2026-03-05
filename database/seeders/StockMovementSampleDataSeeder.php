<?php

namespace Database\Seeders;

use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
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

        $adjustmentItem = StockAdjustmentItem::query()
            ->with(['stockAdjustment'])
            ->first();

        if ($adjustmentItem?->stockAdjustment) {
            $adjustment = $adjustmentItem->stockAdjustment;
            $movementType = (float) $adjustmentItem->quantity_adjusted >= 0 ? 'adjustment_in' : 'adjustment_out';

            StockMovement::updateOrCreate(
                [
                    'movement_type' => $movementType,
                    'reference_type' => \App\Models\StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'product_id' => $adjustmentItem->product_id,
                ],
                [
                    'quantity_in' => $movementType === 'adjustment_in' ? abs((float) $adjustmentItem->quantity_adjusted) : 0,
                    'quantity_out' => $movementType === 'adjustment_out' ? abs((float) $adjustmentItem->quantity_adjusted) : 0,
                    'balance_after' => (float) $adjustmentItem->quantity_after,
                    'unit_cost' => $adjustmentItem->unit_cost,
                    'average_cost_after' => null,
                    'reference_number' => $adjustment->adjustment_number,
                    'notes' => $adjustment->notes,
                    'moved_at' => $adjustment->adjustment_date ? $adjustment->adjustment_date->startOfDay() : now(),
                    'created_by' => $adminUserId,
                ],
            );
        }

        $transferItem = StockTransferItem::query()
            ->with(['stockTransfer'])
            ->first();

        if ($transferItem?->stockTransfer) {
            $transfer = $transferItem->stockTransfer;

            StockMovement::updateOrCreate(
                [
                    'movement_type' => 'transfer_out',
                    'reference_type' => \App\Models\StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'product_id' => $transferItem->product_id,
                ],
                [
                    'quantity_in' => 0,
                    'quantity_out' => (float) $transferItem->quantity,
                    'balance_after' => 0,
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
                    'reference_type' => \App\Models\StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $transferItem->product_id,
                ],
                [
                    'quantity_in' => (float) $transferItem->quantity_received,
                    'quantity_out' => 0,
                    'balance_after' => 0,
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
}

