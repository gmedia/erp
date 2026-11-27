<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Penyesuaian stok bulanan untuk jendela demo 13 bulan (#571).
 *
 * Tiap bulan mendapat satu penyesuaian `approved` (SA-YYYYMM-NNNN) di gudang utama. Pergerakan
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

        foreach (DemoCalendar::months() as $index => $month) {
            $adjustmentDate = DemoCalendar::dateInMonth($index, 22);
            $number = sprintf('%s%s-%04d', self::MONTHLY_PREFIX, $month->format('Ym'), 1);
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
}
