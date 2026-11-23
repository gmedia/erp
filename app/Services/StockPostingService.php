<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\SupplierReturn;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for stock posting.
 *
 * Every document that changes stock (goods receipt, supplier return, stock
 * adjustment, stock transfer dispatch/receive) goes through here so that the
 * movement ledger, `balance_after` chain and derived stock snapshots stay
 * consistent (W7-04). Balance is recomputed from the ledger in
 * (moved_at, id) order, which is the ordering every report relies on.
 */
class StockPostingService
{
    /**
     * Rebuild the `balance_after` chain for one or more product+warehouse
     * scopes. Pass null to rebuild every scope.
     *
     * @param  array<int, array{0:int,1:int}>|null  $scopes
     */
    public function rebuildBalances(?array $scopes = null): void
    {
        $query = StockMovement::query();

        if ($scopes !== null) {
            $query->where(function ($outer) use ($scopes): void {
                foreach ($scopes as [$productId, $warehouseId]) {
                    $outer->orWhere(function ($inner) use ($productId, $warehouseId): void {
                        $inner->where('product_id', $productId)
                            ->where('warehouse_id', $warehouseId);
                    });
                }
            });
        }

        $grouped = $query
            ->orderBy('product_id')
            ->orderBy('warehouse_id')
            ->orderBy('moved_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (StockMovement $movement): string => $movement->product_id . ':' . $movement->warehouse_id);

        foreach ($grouped as $movements) {
            $balance = 0.0;

            foreach ($movements as $movement) {
                $balance += (float) $movement->quantity_in - (float) $movement->quantity_out;

                if ((float) $movement->balance_after !== $balance) {
                    $movement->forceFill(['balance_after' => $balance])->saveQuietly();
                }
            }
        }
    }

    public function postGoodsReceipt(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->status !== 'confirmed') {
            return;
        }

        $goodsReceipt->load('items');
        $scopes = [];

        foreach ($goodsReceipt->items as $item) {
            $quantity = (float) $item->quantity_accepted;

            if ($quantity <= 0) {
                continue;
            }

            $this->record([
                'movement_type' => 'goods_receipt',
                'reference_type' => GoodsReceipt::class,
                'reference_id' => $goodsReceipt->id,
                'reference_number' => $goodsReceipt->gr_number,
                'source_line_id' => (int) $item->id,
                'warehouse_id' => (int) $goodsReceipt->warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'unit_cost' => $item->unit_price,
                'notes' => $goodsReceipt->notes,
                'moved_at' => $goodsReceipt->receipt_date->copy()->startOfDay(),
                'created_by' => $goodsReceipt->confirmed_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $goodsReceipt->warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    public function postSupplierReturn(SupplierReturn $supplierReturn): void
    {
        if ($supplierReturn->status !== 'confirmed') {
            return;
        }

        $supplierReturn->load('items');
        $scopes = [];

        foreach ($supplierReturn->items as $item) {
            $quantity = (float) $item->quantity_returned;

            if ($quantity <= 0) {
                continue;
            }

            $this->record([
                'movement_type' => 'supplier_return',
                'reference_type' => SupplierReturn::class,
                'reference_id' => $supplierReturn->id,
                'reference_number' => $supplierReturn->return_number,
                'source_line_id' => (int) $item->id,
                'warehouse_id' => (int) $supplierReturn->warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'unit_cost' => $item->unit_price,
                'notes' => $supplierReturn->notes,
                'moved_at' => $supplierReturn->return_date->copy()->startOfDay(),
                'created_by' => $supplierReturn->confirmed_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $supplierReturn->warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    public function postStockAdjustment(StockAdjustment $stockAdjustment): void
    {
        if ($stockAdjustment->status !== 'approved') {
            return;
        }

        $stockAdjustment->load('items');
        $scopes = [];

        foreach ($stockAdjustment->items as $item) {
            $quantity = (float) $item->quantity_adjusted;

            if ($quantity === 0.0) {
                continue;
            }

            $this->record([
                'movement_type' => $quantity > 0 ? 'adjustment_in' : 'adjustment_out',
                'reference_type' => StockAdjustment::class,
                'reference_id' => $stockAdjustment->id,
                'reference_number' => $stockAdjustment->adjustment_number,
                'source_line_id' => (int) $item->id,
                'warehouse_id' => (int) $stockAdjustment->warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => $quantity > 0 ? abs($quantity) : 0,
                'quantity_out' => $quantity < 0 ? abs($quantity) : 0,
                'unit_cost' => $item->unit_cost,
                'notes' => $stockAdjustment->notes,
                'moved_at' => $stockAdjustment->adjustment_date->copy()->startOfDay(),
                'created_by' => $stockAdjustment->approved_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $stockAdjustment->warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    /**
     * Dispatch moves stock out of the source warehouse. Posted once the
     * transfer is in transit (or already received).
     */
    public function postStockTransferDispatch(StockTransfer $stockTransfer): void
    {
        if (! in_array($stockTransfer->status, ['in_transit', 'received'], true)) {
            return;
        }

        $stockTransfer->load('items');
        $scopes = [];

        foreach ($stockTransfer->items as $item) {
            $quantity = (float) $item->quantity;

            if ($quantity <= 0) {
                continue;
            }

            $this->record([
                'movement_type' => 'transfer_out',
                'reference_type' => StockTransfer::class,
                'reference_id' => $stockTransfer->id,
                'reference_number' => $stockTransfer->transfer_number,
                'source_line_id' => (int) $item->id,
                'warehouse_id' => (int) $stockTransfer->from_warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'unit_cost' => $item->unit_cost,
                'notes' => $stockTransfer->notes,
                'moved_at' => $stockTransfer->transfer_date->copy()->startOfDay(),
                'created_by' => $stockTransfer->shipped_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $stockTransfer->from_warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    /**
     * Receive moves stock into the destination warehouse.
     */
    public function postStockTransferReceive(StockTransfer $stockTransfer): void
    {
        if ($stockTransfer->status !== 'received') {
            return;
        }

        $stockTransfer->load('items');
        $scopes = [];

        foreach ($stockTransfer->items as $item) {
            $quantity = (float) $item->quantity_received;

            if ($quantity <= 0) {
                continue;
            }

            $this->record([
                'movement_type' => 'transfer_in',
                'reference_type' => StockTransfer::class,
                'reference_id' => $stockTransfer->id,
                'reference_number' => $stockTransfer->transfer_number,
                'source_line_id' => (int) $item->id,
                'warehouse_id' => (int) $stockTransfer->to_warehouse_id,
                'product_id' => (int) $item->product_id,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'unit_cost' => $item->unit_cost,
                'notes' => $stockTransfer->notes,
                'moved_at' => ($stockTransfer->received_at ?? $stockTransfer->transfer_date)->copy()->startOfDay(),
                'created_by' => $stockTransfer->received_by ?? Auth::id(),
            ]);

            $scopes[] = [(int) $item->product_id, (int) $stockTransfer->to_warehouse_id];
        }

        $this->rebuildBalances($scopes);
    }

    /**
     * Membalik semua movement dokumen yang sudah terposting dengan movement
     * pembalik. Histori tidak dihapus (Issue #356, baris 3-4).
     *
     * Pembalik dibuat per movement asal dan bersifat idempoten: jika pembalik
     * untuk movement asal sudah ada, tidak dibuat lagi.
     */
    public function reverseDocument(string $referenceType, int $referenceId, string $reversalType, ?int $createdBy = null): void
    {
        $originals = StockMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereNotIn('movement_type', [
                'goods_receipt_reversal',
                'supplier_return_reversal',
                'adjustment_reversal',
                'transfer_out_reversal',
                'transfer_in_reversal',
            ])
            ->get();

        $scopes = [];

        foreach ($originals as $original) {
            $alreadyReversed = StockMovement::query()
                ->where('movement_type', $reversalType . '_reversal')
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->where('source_line_id', $original->source_line_id)
                ->where('warehouse_id', $original->warehouse_id)
                ->where('product_id', $original->product_id)
                ->exists();

            if ($alreadyReversed) {
                continue;
            }

            $this->record([
                'movement_type' => $reversalType . '_reversal',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $original->reference_number,
                'source_line_id' => $original->source_line_id,
                'warehouse_id' => $original->warehouse_id,
                'product_id' => $original->product_id,
                'quantity_in' => (float) $original->quantity_out,
                'quantity_out' => (float) $original->quantity_in,
                'unit_cost' => $original->unit_cost,
                'notes' => 'Pembalik dokumen dibatalkan',
                'moved_at' => now(),
                'created_by' => $createdBy ?? Auth::id(),
            ]);

            $scopes[] = [(int) $original->product_id, (int) $original->warehouse_id];
        }

        if ($scopes !== []) {
            $this->rebuildBalances($scopes);
        }
    }

    /**
     * Menolak pengeluaran yang membuat stok negatif (Issue #356, baris 6).
     *
     * Dipanggil di dalam transaksi. Baris saldo diambil dengan lockForUpdate
     * agar dua dokumen keluar bersamaan tidak sama-sama lolos.
     *
     * @param  array<int, array{0:int,1:int,2:float}>  $outflows  [product_id, warehouse_id, quantity]
     */
    public function assertNoNegativeStock(array $outflows): void
    {
        $required = [];

        foreach ($outflows as [$productId, $warehouseId, $quantity]) {
            if ($quantity <= 0) {
                continue;
            }

            $key = $productId . ':' . $warehouseId;
            $required[$key] = ($required[$key] ?? 0.0) + $quantity;
        }

        foreach ($required as $key => $quantity) {
            [$productId, $warehouseId] = array_map('intval', explode(':', $key));

            $available = (float) StockMovement::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->orderByDesc('moved_at')
                ->orderByDesc('id')
                ->value('balance_after');

            if ($available < $quantity) {
                // Issue #360 baris 5: pesan memakai nama, bukan id mentah (i18n en/id).
                $productName = Product::query()->whereKey($productId)->value('name');
                $warehouseName = Warehouse::query()->whereKey($warehouseId)->value('name');

                throw ValidationException::withMessages([
                    'items' => [__('stock.insufficient', [
                        'product' => $productName ?? '#' . $productId,
                        'warehouse' => $warehouseName ?? '#' . $warehouseId,
                        'available' => $available,
                        'required' => $quantity,
                    ])],
                ])->status(422);
            }
        }
    }

    /**
     * Membalik movement transfer: dispatch (transfer_out) dan receive (transfer_in).
     * Dipisah agar tipe pembalik mengikuti tipe asal.
     */
    public function reverseTransfer(StockTransfer $stockTransfer): void
    {
        $this->reverseMovementsOfType(StockTransfer::class, $stockTransfer->id, 'transfer_out', 'transfer_out_reversal');
        $this->reverseMovementsOfType(StockTransfer::class, $stockTransfer->id, 'transfer_in', 'transfer_in_reversal');
    }

    private function reverseMovementsOfType(string $referenceType, int $referenceId, string $originalType, string $reversalType): void
    {
        $originals = StockMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('movement_type', $originalType)
            ->get();

        $scopes = [];

        foreach ($originals as $original) {
            $alreadyReversed = StockMovement::query()
                ->where('movement_type', $reversalType)
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->where('source_line_id', $original->source_line_id)
                ->where('warehouse_id', $original->warehouse_id)
                ->where('product_id', $original->product_id)
                ->exists();

            if ($alreadyReversed) {
                continue;
            }

            $this->record([
                'movement_type' => $reversalType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $original->reference_number,
                'source_line_id' => $original->source_line_id,
                'warehouse_id' => $original->warehouse_id,
                'product_id' => $original->product_id,
                'quantity_in' => (float) $original->quantity_out,
                'quantity_out' => (float) $original->quantity_in,
                'unit_cost' => $original->unit_cost,
                'notes' => 'Pembalik dokumen dibatalkan',
                'moved_at' => now(),
                'created_by' => Auth::id(),
            ]);

            $scopes[] = [(int) $original->product_id, (int) $original->warehouse_id];
        }

        if ($scopes !== []) {
            $this->rebuildBalances($scopes);
        }
    }

    /**
     * Idempotent upsert keyed by reference + type + warehouse + product.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes): StockMovement
    {
        $movement = StockMovement::query()->updateOrCreate(
            [
                'movement_type' => $attributes['movement_type'],
                'reference_type' => $attributes['reference_type'],
                'reference_id' => $attributes['reference_id'],
                'source_line_id' => $attributes['source_line_id'],
                'warehouse_id' => $attributes['warehouse_id'],
                'product_id' => $attributes['product_id'],
            ],
            $attributes + ['balance_after' => 0],
        );

        return $movement;
    }
}
