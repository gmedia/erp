<?php

namespace App\Actions\SupplierReturns;

use App\Models\GoodsReceiptItem;
use App\Models\SupplierReturnItem;
use Illuminate\Validation\ValidationException;

/**
 * Issue #436 butir 4 dan keputusan product owner (2): total retur per baris GR (retur tidak
 * cancelled) tidak boleh melebihi qty diterima baris GR. Retur setelah bill lunas tetap boleh.
 *
 * Dipanggil dalam transaksi create/update retur, setelah lock baris GR, supaya dua retur
 * bersamaan untuk GR yang sama tidak sama-sama lolos.
 */
class EnsureGoodsReceiptItemNotOverReturnedAction
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(array $items, ?int $excludeSupplierReturnId = null): void
    {
        $requested = [];
        $indexByLine = [];

        foreach ($items as $index => $item) {
            $lineId = (int) ($item['goods_receipt_item_id'] ?? 0);

            if ($lineId === 0) {
                continue;
            }

            $requested[$lineId] = ($requested[$lineId] ?? 0.0) + (float) ($item['quantity_returned'] ?? 0);
            $indexByLine[$lineId] ??= $index;
        }

        foreach ($requested as $lineId => $incoming) {
            // Butir 4: hanya baris GR confirmed; batas memakai quantity_accepted (yang ditolak tidak masuk stok).
            $line = GoodsReceiptItem::query()
                ->whereKey($lineId)
                ->whereHas('goodsReceipt', static fn ($query) => $query->where('status', 'confirmed'))
                ->lockForUpdate()
                ->first();

            if ($line === null) {
                throw ValidationException::withMessages([
                    'items.' . $indexByLine[$lineId] . '.goods_receipt_item_id' => [
                        __('supplier_return.returned_exceeds_received'),
                    ],
                ])->status(422);
            }

            $alreadyReturned = (float) SupplierReturnItem::query()
                ->where('goods_receipt_item_id', $lineId)
                ->whereHas('supplierReturn', function ($query) use ($excludeSupplierReturnId): void {
                    $query->where('status', 'confirmed');

                    if ($excludeSupplierReturnId !== null) {
                        $query->whereKeyNot($excludeSupplierReturnId);
                    }
                })
                ->sum('quantity_returned');

            if ($alreadyReturned + $incoming > (float) $line->quantity_accepted + 0.00001) {
                throw ValidationException::withMessages([
                    'items.' . $indexByLine[$lineId] . '.quantity_returned' => [
                        __('supplier_return.returned_exceeds_received'),
                    ],
                ])->status(422);
            }
        }
    }
}
