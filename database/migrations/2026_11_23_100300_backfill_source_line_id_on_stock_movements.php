<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill source_line_id pada stock_movements lama (Issue #361).
 *
 * Aturan:
 * - Hanya mengisi kolom source_line_id. quantity_in, quantity_out, dan
 *   balance_after TIDAK disentuh.
 * - Baris dokumen dicocokkan lewat reference_type + reference_id + product_id
 *   + warehouse_id + qty dokumen. Jika tepat satu baris cocok, dipasangkan.
 * - Jika lebih dari satu baris cocok (produk sama di dua baris, qty identik),
 *   TIDAK ditebak. Movement dibiarkan null dan dicatat di log migrasi.
 * - Idempoten: hanya baris dengan source_line_id null yang diproses.
 * - Diproses per chunk agar aman untuk data besar.
 * - down() tidak mengubah data (nilai lama tidak bisa dipulihkan).
 */
return new class extends Migration
{
    private const CHUNK = 500;

    /**
     * Pemetaan tipe movement ke dokumen sumber: tabel item, kolom FK dokumen,
     * kolom gudang header, kolom qty item, dan arah qty (in/out).
     *
     * item_sign: tanda qty di baris dokumen yang sesuai dengan movement.
     * Adjustment menyimpan qty bertanda (+ masuk, - keluar); dokumen lain positif.
     *
     * @var array<string, array{reference_type: string, items: string, document_key: string, warehouse_key: string, quantity_column: string, quantity_sign: string, item_sign: int}>
     */
    private const SOURCES = [
        'goods_receipt' => [
            'reference_type' => 'App\\Models\\GoodsReceipt',
            'items' => 'goods_receipt_items',
            'document_key' => 'goods_receipt_id',
            'warehouse_key' => 'warehouse_id',
            'quantity_column' => 'quantity_accepted',
            'quantity_sign' => 'in',
            'item_sign' => 1,
        ],
        'supplier_return' => [
            'reference_type' => 'App\\Models\\SupplierReturn',
            'items' => 'supplier_return_items',
            'document_key' => 'supplier_return_id',
            'warehouse_key' => 'warehouse_id',
            'quantity_column' => 'quantity_returned',
            'quantity_sign' => 'out',
            'item_sign' => 1,
        ],
        'adjustment_in' => [
            'reference_type' => 'App\\Models\\StockAdjustment',
            'items' => 'stock_adjustment_items',
            'document_key' => 'stock_adjustment_id',
            'warehouse_key' => 'warehouse_id',
            'quantity_column' => 'quantity_adjusted',
            'quantity_sign' => 'in',
            'item_sign' => 1,
        ],
        'adjustment_out' => [
            'reference_type' => 'App\\Models\\StockAdjustment',
            'items' => 'stock_adjustment_items',
            'document_key' => 'stock_adjustment_id',
            'warehouse_key' => 'warehouse_id',
            'quantity_column' => 'quantity_adjusted',
            'quantity_sign' => 'out',
            'item_sign' => -1,
        ],
        'transfer_out' => [
            'reference_type' => 'App\\Models\\StockTransfer',
            'items' => 'stock_transfer_items',
            'document_key' => 'stock_transfer_id',
            'warehouse_key' => 'from_warehouse_id',
            'quantity_column' => 'quantity',
            'quantity_sign' => 'out',
            'item_sign' => 1,
        ],
        'transfer_in' => [
            'reference_type' => 'App\\Models\\StockTransfer',
            'items' => 'stock_transfer_items',
            'document_key' => 'stock_transfer_id',
            'warehouse_key' => 'to_warehouse_id',
            'quantity_column' => 'quantity_received',
            'quantity_sign' => 'in',
            'item_sign' => 1,
        ],
    ];

    public function up(): void
    {
        foreach (self::SOURCES as $movementType => $source) {
            $this->backfillType($movementType, $source);
        }
    }

    public function down(): void
    {
        // Nilai source_line_id lama tidak bisa dibedakan dari hasil backfill.
        // Dibiarkan apa adanya agar down() tidak merusak histori.
    }

    /**
     * @param  array{reference_type: string, items: string, document_key: string, warehouse_key: string, quantity_column: string, quantity_sign: string, item_sign: int}  $source
     */
    private function backfillType(string $movementType, array $source): void
    {
        DB::table('stock_movements')
            ->where('movement_type', $movementType)
            ->whereNull('source_line_id')
            ->orderBy('id')
            ->chunkById(self::CHUNK, function ($movements) use ($source): void {
                foreach ($movements as $movement) {
                    $this->attachSourceLine($movement, $source);
                }
            });
    }

    /**
     * @param  array{reference_type: string, items: string, document_key: string, warehouse_key: string, quantity_column: string, quantity_sign: string, item_sign: int}  $source
     */
    private function attachSourceLine(object $movement, array $source): void
    {
        if ($movement->reference_id === null) {
            $this->logAmbiguous($movement, 'dokumen_tanpa_referensi', 0);

            return;
        }

        if (! $this->warehouseMatches($source, $movement)) {
            $this->logAmbiguous($movement, 'gudang_tidak_cocok', 0);

            return;
        }

        if ($movement->reference_type !== $source['reference_type']) {
            $this->logAmbiguous($movement, 'reference_type_tidak_cocok', 0);

            return;
        }

        $quantity = $this->movementQuantity($movement, $source['quantity_sign']);

        // Movement menyimpan nilai absolut. Baris dokumen dicocokkan dengan tanda
        // (item_sign) agar +3 dan -3 pada produk sama tidak dianggap identik.
        // Baris yang sudah dipakai movement lain tidak dihitung, agar tidak ditebak.
        $candidates = DB::table($source['items'])
            ->where($source['document_key'], $movement->reference_id)
            ->where('product_id', $movement->product_id)
            ->whereRaw($source['quantity_column'] . ' * ? = ?', [$source['item_sign'], $quantity])
            ->whereNotIn('id', $this->claimedLineIds($movement))
            ->pluck('id');

        if ($candidates->count() !== 1) {
            $this->logAmbiguous(
                $movement,
                $candidates->isEmpty() ? 'tidak_ditemukan' : 'ambigu',
                $candidates->count(),
            );

            return;
        }

        DB::table('stock_movements')
            ->where('id', $movement->id)
            ->whereNull('source_line_id')
            ->update(['source_line_id' => (int) $candidates->first()]);
    }

    /**
     * Baris dokumen yang sudah dipasangkan ke movement lain untuk dokumen & tipe yang sama.
     *
     * @return array<int, int>
     */
    private function claimedLineIds(object $movement): array
    {
        return DB::table('stock_movements')
            ->where('movement_type', $movement->movement_type)
            ->where('reference_type', $movement->reference_type)
            ->where('reference_id', $movement->reference_id)
            ->where('product_id', $movement->product_id)
            ->whereNotNull('source_line_id')
            ->pluck('source_line_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function logAmbiguous(object $movement, string $reason, int $candidates): void
    {
        Log::warning('Issue #361 backfill source_line_id dilewati', [
            'reason' => $reason,
            'stock_movement_id' => $movement->id,
            'movement_type' => $movement->movement_type,
            'reference_type' => $movement->reference_type,
            'reference_id' => $movement->reference_id,
            'product_id' => $movement->product_id,
            'candidates' => $candidates,
        ]);
    }

    /**
     * Qty dokumen yang seharusnya tercatat di movement. Adjustment bisa negatif,
     * jadi dibandingkan dengan nilai absolut.
     */
    private function movementQuantity(object $movement, string $sign): float
    {
        return $sign === 'in'
            ? (float) $movement->quantity_in
            : (float) $movement->quantity_out;
    }

    /**
     * Gudang movement harus sama dengan gudang header dokumen sumber.
     *
     * @param  array{reference_type: string, items: string, document_key: string, warehouse_key: string, quantity_column: string, quantity_sign: string, item_sign: int}  $source
     */
    private function warehouseMatches(array $source, object $movement): bool
    {
        return DB::table($this->documentTable($source['items']))
            ->where('id', $movement->reference_id)
            ->where($source['warehouse_key'], $movement->warehouse_id)
            ->exists();
    }

    private function documentTable(string $itemsTable): string
    {
        $documents = [
            'goods_receipt_items' => 'goods_receipts',
            'supplier_return_items' => 'supplier_returns',
            'stock_adjustment_items' => 'stock_adjustments',
            'stock_transfer_items' => 'stock_transfers',
        ];

        if (! array_key_exists($itemsTable, $documents)) {
            throw new RuntimeException("Tabel item tidak dikenal untuk backfill: {$itemsTable}");
        }

        return $documents[$itemsTable];
    }
};
