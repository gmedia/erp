<?php

namespace App\Actions\SupplierReturns;

use App\Models\SupplierReturn;
use App\Services\StockPostingService;

class PostSupplierReturnMovementsAction
{
    public function __construct(private readonly StockPostingService $stockPosting) {}

    /**
     * Retur ke supplier mengurangi stok gudang asal. Posting melalui
     * StockPostingService agar rantai `balance_after` konsisten (W7-04).
     */
    public function execute(SupplierReturn $supplierReturn): void
    {
        $this->stockPosting->postSupplierReturn($supplierReturn);
    }
}
