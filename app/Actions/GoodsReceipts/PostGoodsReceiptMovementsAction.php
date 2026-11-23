<?php

namespace App\Actions\GoodsReceipts;

use App\Models\GoodsReceipt;
use App\Services\StockPostingService;

class PostGoodsReceiptMovementsAction
{
    public function __construct(private readonly StockPostingService $stockPosting) {}

    /**
     * Barang diterima menambah stok gudang tujuan. Posting melalui
     * StockPostingService agar rantai `balance_after` konsisten (W7-04).
     */
    public function execute(GoodsReceipt $goodsReceipt): void
    {
        $this->stockPosting->postGoodsReceipt($goodsReceipt);
    }
}
