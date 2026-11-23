<?php

namespace App\Actions\StockAdjustments;

use App\Models\StockAdjustment;
use App\Services\StockPostingService;

class PostStockAdjustmentMovementsAction
{
    public function __construct(private readonly StockPostingService $stockPosting) {}

    /**
     * Catat pergerakan stok untuk adjustment yang sudah approved. Posting
     * melalui StockPostingService agar rantai `balance_after` konsisten.
     */
    public function execute(StockAdjustment $stockAdjustment): void
    {
        $this->stockPosting->postStockAdjustment($stockAdjustment);
    }
}
