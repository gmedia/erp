<?php

namespace App\Actions\GoodsReceipts;

use App\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;

/**
 * Issue #380: GR hanya boleh berstatus confirmed bila PO induk berstatus
 * confirmed atau partially_received. Draft GR tetap diizinkan untuk PO status apa pun.
 *
 * Dipanggil di dalam transaksi create/update GR dengan lock baris PO, supaya
 * perubahan status PO yang terjadi bersamaan tidak lolos dari pemeriksaan.
 */
class EnsurePurchaseOrderAllowsConfirmedReceiptAction
{
    public const ALLOWED_PO_STATUSES = ['confirmed', 'partially_received'];

    public function execute(int $purchaseOrderId): void
    {
        $status = PurchaseOrder::query()
            ->whereKey($purchaseOrderId)
            ->lockForUpdate()
            ->value('status');

        if (in_array($status, self::ALLOWED_PO_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => [__('goods_receipt.po_not_approved')],
        ]);
    }
}
