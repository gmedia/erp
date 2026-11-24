<?php

namespace App\Actions\GoodsReceipts;

use App\Models\GoodsReceiptItem;
use Illuminate\Validation\ValidationException;

/**
 * Issue #436 review putaran 3 butir 1: konfirmasi GR tanpa payload items harus mengecek
 * accepted + rejected <= received dari item yang TERSIMPAN. Pengecekan di FormRequest hanya
 * berjalan bila items dikirim, sehingga draft lama yang tidak valid bisa lolos lewat status saja.
 */
class EnsureStoredItemsAcceptedWithinReceivedAction
{
    public function execute(int $goodsReceiptId): void
    {
        $items = GoodsReceiptItem::query()
            ->where('goods_receipt_id', $goodsReceiptId)
            ->orderBy('id')
            ->get(['quantity_received', 'quantity_accepted', 'quantity_rejected']);

        foreach ($items as $index => $item) {
            $received = (float) $item->quantity_received;
            $accepted = (float) $item->quantity_accepted;
            $rejected = (float) ($item->quantity_rejected ?? 0);

            if ($accepted + $rejected > $received + 0.00001) {
                throw ValidationException::withMessages([
                    'items.' . $index . '.quantity_accepted' => [
                        __('goods_receipt.accepted_exceeds_received'),
                    ],
                ])->status(422);
            }
        }
    }
}
