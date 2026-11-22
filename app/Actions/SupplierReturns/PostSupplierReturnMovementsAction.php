<?php

namespace App\Actions\SupplierReturns;

use App\Actions\Concerns\ResolvesStockBalance;
use App\Models\StockMovement;
use App\Models\SupplierReturn;
use Illuminate\Support\Facades\Auth;

class PostSupplierReturnMovementsAction
{
    use ResolvesStockBalance;

    /**
     * Retur ke supplier mengurangi stok gudang asal. Idempoten terhadap
     * reference (return + produk + gudang) (W5-19).
     */
    public function execute(SupplierReturn $supplierReturn): void
    {
        if ($supplierReturn->status !== 'confirmed') {
            return;
        }

        $supplierReturn->loadMissing('items');

        foreach ($supplierReturn->items as $item) {
            $quantity = (float) $item->quantity_returned;

            if ($quantity <= 0) {
                continue;
            }

            StockMovement::updateOrCreate(
                [
                    'movement_type' => 'supplier_return',
                    'reference_type' => SupplierReturn::class,
                    'reference_id' => $supplierReturn->id,
                    'warehouse_id' => $supplierReturn->warehouse_id,
                    'product_id' => $item->product_id,
                ],
                [
                    'quantity_in' => 0,
                    'quantity_out' => $quantity,
                    'balance_after' => max(
                        0,
                        $this->currentStockBalance(
                            (int) $item->product_id,
                            (int) $supplierReturn->warehouse_id,
                        ) - $quantity,
                    ),
                    'unit_cost' => $item->unit_price,
                    'reference_number' => $supplierReturn->return_number,
                    'notes' => $supplierReturn->notes,
                    'moved_at' => $supplierReturn->return_date->startOfDay(),
                    'created_by' => $supplierReturn->confirmed_by ?? Auth::id(),
                ],
            );
        }
    }
}
