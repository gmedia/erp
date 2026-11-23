<?php

namespace App\Services;

use App\Actions\EntityStates\SyncPipelineStateToStatusAction;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;

/**
 * Menjaga `purchase_order_items.quantity_received` dan status PO tetap
 * sejalan dengan GR confirmed (Issue #356, baris 1).
 *
 * Dipanggil dalam transaksi yang sama dengan confirm/cancel GR.
 */
class PurchaseOrderReceiptService
{
    public function syncFromGoodsReceipt(GoodsReceipt $goodsReceipt): void
    {
        $purchaseOrder = PurchaseOrder::query()
            ->with('items')
            ->find($goodsReceipt->purchase_order_id);

        if ($purchaseOrder === null) {
            return;
        }

        $receivedByLine = $this->receivedQuantitiesByLine($purchaseOrder);

        foreach ($purchaseOrder->items as $line) {
            $received = $receivedByLine[$line->id] ?? 0.0;

            if ((float) $line->quantity_received !== $received) {
                $line->forceFill(['quantity_received' => $received])->saveQuietly();
            }
        }

        $this->refreshStatus($purchaseOrder->refresh());
    }

    /**
     * @return array<int, float> purchase_order_item_id => jumlah diterima (confirmed GR)
     */
    private function receivedQuantitiesByLine(PurchaseOrder $purchaseOrder): array
    {
        $totals = [];

        GoodsReceipt::query()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->where('status', 'confirmed')
            ->with('items')
            ->get()
            ->each(function (GoodsReceipt $receipt) use (&$totals): void {
                foreach ($receipt->items as $item) {
                    $lineId = (int) $item->purchase_order_item_id;
                    $totals[$lineId] = ($totals[$lineId] ?? 0.0) + (float) $item->quantity_accepted;
                }
            });

        return $totals;
    }

    private function refreshStatus(PurchaseOrder $purchaseOrder): void
    {
        if (! in_array($purchaseOrder->status, ['confirmed', 'partially_received', 'fully_received'], true)) {
            return;
        }

        $lines = PurchaseOrderItem::query()->where('purchase_order_id', $purchaseOrder->id)->get();

        if ($lines->isEmpty()) {
            return;
        }

        $allFull = $lines->every(fn (PurchaseOrderItem $line): bool => (float) $line->quantity_received >= (float) $line->quantity);
        $anyReceived = $lines->contains(fn (PurchaseOrderItem $line): bool => (float) $line->quantity_received > 0);

        $status = match (true) {
            $allFull => 'fully_received',
            $anyReceived => 'partially_received',
            default => 'confirmed',
        };

        if ($purchaseOrder->status !== $status) {
            $purchaseOrder->forceFill(['status' => $status])->saveQuietly();
            app(SyncPipelineStateToStatusAction::class)->execute($purchaseOrder, $status);
        }
    }
}
