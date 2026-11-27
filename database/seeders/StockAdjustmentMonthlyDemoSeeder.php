<?php

namespace Database\Seeders;

use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\SupplierReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Penyesuaian stok bulanan untuk jendela demo 13 bulan (#571).
 *
 * Tiap bulan mendapat satu penyesuaian `approved` (SA-YYYY-0001NN) di gudang utama. Pergerakan
 * dibukukan oleh StockMovementSampleDataSeeder lewat StockPostingService, sehingga kartu stok
 * dan jurnal persediaan berasal dari jalur yang sama dengan aplikasi. Selisih bergantian
 * (koreksi, rusak, susut) dengan nilai kecil agar saldo tetap positif.
 *
 * Idempoten: nomor deterministik dengan updateOrCreate. Dijalankan sebelum
 * StockMovementSampleDataSeeder agar pergerakannya ikut dibangun ulang.
 */
class StockAdjustmentMonthlyDemoSeeder extends Seeder
{
    /** Pola nomor SA bulanan; dipakai juga oleh StockMovementSampleDataSeeder. */
    public const MONTHLY_PREFIX = 'SA-';

    /** Sequence awal nomor bulanan per tahun (nomor pertama = FIRST_SEQUENCE + 1). */
    private const FIRST_SEQUENCE = 100;

    /** Pola jenis penyesuaian per bulan (nilai enum stock_adjustments.adjustment_type). */
    private const TYPES = ['correction', 'damage', 'shrinkage', 'expired'];

    /** Jumlah produk per penyesuaian bulanan. */
    private const PRODUCTS_PER_ADJUSTMENT = 2;

    /** Selisih dasar per produk sebelum faktor volume. */
    private const BASE_QUANTITY = 1.0;

    public function run(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id')
            ?? User::query()->value('id');
        $warehouse = Warehouse::query()->orderBy('id')->first();
        $products = Product::query()
            ->where('type', '!=', 'service')
            ->orderBy('id')
            ->get();

        if (! $adminUserId || ! $warehouse || $products->isEmpty()) {
            return;
        }

        $sequenceByYear = [];

        foreach (DemoCalendar::months() as $index => $month) {
            $adjustmentDate = DemoCalendar::dateInMonth($index, 22);
            $year = $month->format('Y');
            $sequenceByYear[$year] = ($sequenceByYear[$year] ?? 0) + 1;
            // Nomor mengikuti pola produksi SA-YYYY-NNNNNN (W7-17). Rentang 100+ dipakai agar
            // tidak bentrok dengan nomor SA-YYYY-000001..000005 dari StockAdjustmentSampleDataSeeder.
            $number = sprintf('%s%s-%06d', self::MONTHLY_PREFIX, $year, self::FIRST_SEQUENCE + $sequenceByYear[$year]);
            $type = self::TYPES[$index % count(self::TYPES)];

            $adjustment = StockAdjustment::updateOrCreate(
                ['adjustment_number' => $number],
                [
                    'warehouse_id' => $warehouse->id,
                    'adjustment_date' => $adjustmentDate->toDateString(),
                    'adjustment_type' => $type,
                    'status' => 'approved',
                    'inventory_stocktake_id' => null,
                    'notes' => 'Penyesuaian stok bulanan',
                    'created_by' => $adminUserId,
                    'approved_by' => $adminUserId,
                    'approved_at' => $adjustmentDate->copy()->setTime(16, 0),
                ],
            );

            $this->seedItems($adjustment, $products, $index);
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function seedItems(StockAdjustment $adjustment, Collection $products, int $index): void
    {
        $lines = $products->slice(($index * self::PRODUCTS_PER_ADJUSTMENT) % max(1, $products->count()))
            ->take(self::PRODUCTS_PER_ADJUSTMENT)
            ->values();

        // Arah selisih berganti per bulan: genap = koreksi naik, ganjil = susut.
        $sign = $index % 2 === 0 ? 1.0 : -1.0;
        $keptIds = [];

        foreach ($lines as $lineIndex => $product) {
            $quantity = $sign * (self::BASE_QUANTITY + $lineIndex);

            // Penyesuaian keluar tidak boleh membuat saldo negatif pada tanggal itu.
            // Bila saldo dokumen belum cukup (penerimaan bulan ini belum dibukukan),
            // selisih dibalik menjadi penyesuaian masuk yang realistis.
            if ($quantity < 0 && $this->balanceOn($product->id, $adjustment) < abs($quantity)) {
                $quantity = abs($quantity);
            }

            $unitCost = (float) $product->cost;

            StockAdjustmentItem::updateOrCreate(
                [
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id' => $product->id,
                ],
                [
                    'unit_id' => $product->unit_id,
                    'quantity_before' => 0.0,
                    'quantity_adjusted' => $quantity,
                    'quantity_after' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => abs($quantity) * $unitCost,
                    'reason' => 'Penyesuaian stok bulanan hasil pemeriksaan gudang',
                ],
            );

            $keptIds[] = $product->id;
        }

        $adjustment->items()->whereNotIn('product_id', $keptIds)->delete();
    }

    /**
     * Saldo produk di gudang pada tanggal penyesuaian, dihitung dari dokumen yang
     * sudah ada (penerimaan dikurangi retur, ditambah penyesuaian masuk, dikurangi
     * penyesuaian keluar), tanpa bergantung pada urutan pembukuan stock movement.
     */
    private function balanceOn(int $productId, StockAdjustment $adjustment): float
    {
        $date = $adjustment->adjustment_date->toDateString();

        $received = (float) GoodsReceiptItem::query()
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->where('goods_receipts.status', 'confirmed')
            ->where('goods_receipts.warehouse_id', $adjustment->warehouse_id)
            ->where('goods_receipt_items.product_id', $productId)
            ->whereDate('goods_receipts.receipt_date', '<=', $date)
            ->sum('goods_receipt_items.quantity_received');

        $returned = (float) SupplierReturnItem::query()
            ->join('supplier_returns', 'supplier_returns.id', '=', 'supplier_return_items.supplier_return_id')
            ->where('supplier_returns.status', 'confirmed')
            ->where('supplier_returns.warehouse_id', $adjustment->warehouse_id)
            ->where('supplier_return_items.product_id', $productId)
            ->whereDate('supplier_returns.return_date', '<=', $date)
            ->sum('supplier_return_items.quantity_returned');

        $adjusted = (float) StockAdjustmentItem::query()
            ->join('stock_adjustments', 'stock_adjustments.id', '=', 'stock_adjustment_items.stock_adjustment_id')
            ->where('stock_adjustments.status', 'approved')
            ->where('stock_adjustments.warehouse_id', $adjustment->warehouse_id)
            ->where('stock_adjustment_items.product_id', $productId)
            ->where('stock_adjustments.id', '!=', $adjustment->id)
            ->whereDate('stock_adjustments.adjustment_date', '<', $date)
            ->sum('stock_adjustment_items.quantity_adjusted');

        return $received - $returned + $adjusted;
    }
}
