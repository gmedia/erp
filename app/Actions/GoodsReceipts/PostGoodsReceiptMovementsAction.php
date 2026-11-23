<?php

namespace App\Actions\GoodsReceipts;

use App\Models\GoodsReceipt;
use App\Services\PurchaseOrderReceiptService;
use App\Services\StockPostingService;

class PostGoodsReceiptMovementsAction
{
    public function __construct(
        private readonly StockPostingService $stockPosting,
        private readonly PurchaseOrderReceiptService $purchaseOrderReceipts,
    ) {}

    /**
     * Barang diterima menambah stok gudang tujuan. Posting melalui
     * StockPostingService agar rantai `balance_after` konsisten (W7-04).
     * Received qty PO ikut disinkronkan (Issue #356).
     */
    public function execute(GoodsReceipt $goodsReceipt): void
    {
        $this->stockPosting->postGoodsReceipt($goodsReceipt);
        $this->purchaseOrderReceipts->syncFromGoodsReceipt($goodsReceipt);
    }
}
