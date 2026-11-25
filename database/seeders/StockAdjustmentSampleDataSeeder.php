<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\Concerns\ReopensDemoPeriodClosings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StockAdjustmentSampleDataSeeder extends Seeder
{
    use ReopensDemoPeriodClosings;

    protected function seedDemoData(): void
    {
        $adminUserId = User::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? User::query()->value('id');

        $warehouse = Warehouse::query()->where('code', DemoMasterData::WAREHOUSE_MAIN_CODE)->first();

        if ($warehouse === null) {
            $branchId = Branch::query()
                ->where('name', DemoMasterData::BRANCH_HEAD_OFFICE)
                ->value('id') ?? Branch::query()->value('id');
            $warehouse = Warehouse::updateOrCreate(
                ['branch_id' => $branchId, 'code' => DemoMasterData::WAREHOUSE_MAIN_CODE],
                ['name' => DemoMasterData::WAREHOUSE_MAIN_NAME]
            );
        }

        $branchIdForStock = $warehouse->branch_id ?? Branch::query()->value('id');
        $products = $this->ensureProductsWithStock($branchIdForStock);

        $year = now()->format('Y');
        $completedStocktakeNumber = "SO-{$year}-920003";
        $completedStocktakeId = InventoryStocktake::query()
            ->where('stocktake_number', $completedStocktakeNumber)
            ->value('id');

        // Format nomor berubah (SA-…-9100xx → SA-…-000001..): ganti nama HANYA
        // nomor demo lama, dengan update in-place agar journal & movement yang
        // menempel tidak menjadi yatim. Nomor pengguna tidak tersentuh.
        foreach (range(0, 4) as $index) {
            $legacy = sprintf('SA-%s-9100%02d', $year, $index);
            $target = sprintf('SA-%s-%06d', $year, $index + 1);

            if (StockAdjustment::query()->where('adjustment_number', $legacy)->exists()
                && ! StockAdjustment::query()->where('adjustment_number', $target)->exists()) {
                StockAdjustment::query()->where('adjustment_number', $legacy)->update(['adjustment_number' => $target]);
            }
        }

        $samples = [
            // Saldo awal stok (approved) agar rantai `balance_after` dimulai
            // dari nilai positif sebelum dokumen lain (W7-04).
            [
                'adjustment_number' => "SA-{$year}-000001",
                'status' => 'approved',
                'adjustment_date' => "{$year}-01-02",
                'adjustment_type' => 'initial_stock',
                'inventory_stocktake_id' => null,
                'approved' => ['approved_by' => $adminUserId, 'approved_at' => Carbon::parse("{$year}-01-02 09:00")],
                'items' => $this->makeOpeningStockItems(),
            ],
            [
                'adjustment_number' => "SA-{$year}-000002",
                'status' => 'draft',
                'adjustment_date' => now()->subDays(6)->toDateString(),
                'adjustment_type' => 'other',
                'inventory_stocktake_id' => null,
                'approved' => null,
                'items' => $this->makeManualAdjustmentItems($products, $branchIdForStock, -2.0),
            ],
            [
                'adjustment_number' => "SA-{$year}-000003",
                'status' => 'pending_approval',
                'adjustment_date' => now()->subDays(5)->toDateString(),
                'adjustment_type' => 'damage',
                'inventory_stocktake_id' => null,
                'approved' => null,
                'items' => $this->makeManualAdjustmentItems($products, $branchIdForStock, -5.0),
            ],
            [
                'adjustment_number' => "SA-{$year}-000004",
                'status' => 'approved',
                'adjustment_date' => now()->subDays(3)->toDateString(),
                'adjustment_type' => $completedStocktakeId ? 'stocktake_result' : 'correction',
                'inventory_stocktake_id' => $completedStocktakeId,
                'approved' => ['approved_by' => $adminUserId, 'approved_at' => now()->subDays(2)],
                'items' => $completedStocktakeId
                    ? $this->makeStocktakeResultAdjustmentItems($completedStocktakeId, $branchIdForStock)
                    : $this->makeManualAdjustmentItems($products, $branchIdForStock, 3.0),
            ],
            [
                'adjustment_number' => "SA-{$year}-000005",
                'status' => 'cancelled',
                'adjustment_date' => now()->subDays(2)->toDateString(),
                'adjustment_type' => 'expired',
                'inventory_stocktake_id' => null,
                'approved' => null,
                'items' => $this->makeManualAdjustmentItems($products, $branchIdForStock, -1.0),
            ],
        ];

        foreach ($samples as $sample) {
            $items = $sample['items'];
            unset($sample['items']);

            $approved = $sample['approved'];
            unset($sample['approved']);

            $adjustment = StockAdjustment::updateOrCreate(
                ['adjustment_number' => $sample['adjustment_number']],
                array_merge([
                    'warehouse_id' => $warehouse->id,
                    'notes' => 'Penyesuaian stok hasil stocktake',
                    'created_by' => $adminUserId,
                ], $approved ?? [], [
                    'adjustment_date' => $sample['adjustment_date'],
                    'adjustment_type' => $sample['adjustment_type'],
                    'status' => $sample['status'],
                    'inventory_stocktake_id' => $sample['inventory_stocktake_id'],
                ])
            );

            foreach ($items as $item) {
                StockAdjustmentItem::updateOrCreate(
                    [
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'unit_id' => $item['unit_id'],
                        'quantity_before' => $item['quantity_before'],
                        'quantity_adjusted' => $item['quantity_adjusted'],
                        'quantity_after' => $item['quantity_after'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $item['total_cost'],
                        'reason' => $item['reason'],
                    ]
                );
            }

            $productIds = array_map(fn ($item) => (int) $item['product_id'], $items);
            $adjustment->items()
                ->whereNotIn('product_id', $productIds)
                ->delete();
        }
    }

    private function renameLegacyProducts(): void
    {
        $map = [
            'INV-SMP-001' => 'INV-2026-001',
            'INV-SMP-002' => 'INV-2026-002',
            'INV-SMP-003' => 'INV-2026-003',
        ];

        foreach ($map as $legacy => $current) {
            if (Product::where('code', $current)->exists()) {
                continue;
            }

            Product::where('code', $legacy)->update(['code' => $current]);
        }
    }

    private function ensureProductsWithStock(int $branchId): Collection
    {
        $this->renameLegacyProducts();
        $products = Product::query()
            ->whereIn('code', ['INV-2026-001', 'INV-2026-002', 'INV-2026-003'])
            ->orderBy('id')
            ->get();

        if ($products->count() >= 3) {
            return $products->take(3);
        }

        $products = Product::query()->orderBy('id')->take(3)->get();
        if ($products->count() >= 3) {
            return $products;
        }

        $categoryId = ProductCategory::query()->value('id');
        $unitId = Unit::query()->value('id');

        $samples = [
            [
                'code' => 'INV-2026-001',
                'name' => 'Kabel HDMI 2 Meter',
                'type' => 'raw_material',
                'cost' => 10000,
            ],
            [
                'code' => 'INV-2026-002',
                'name' => 'Mouse Wireless M170',
                'type' => 'raw_material',
                'cost' => 15000,
            ],
            [
                'code' => 'INV-2026-003',
                'name' => 'Keyboard Mekanik K2',
                'type' => 'finished_good',
                'cost' => 25000,
            ],
        ];

        foreach ($samples as $sample) {
            $product = Product::updateOrCreate(
                ['code' => $sample['code']],
                [
                    'name' => $sample['name'],
                    'type' => $sample['type'],
                    'product_category_id' => $categoryId,
                    'unit_id' => $unitId,
                    'branch_id' => $branchId,
                    'cost' => $sample['cost'],
                    'selling_price' => 0,
                    'billing_model' => 'one_time',
                    'status' => 'active',
                ]
            );
        }

        return Product::query()
            ->whereIn('code', ['INV-2026-001', 'INV-2026-002', 'INV-2026-003'])
            ->orderBy('id')
            ->get()
            ->take(3);
    }

    /**
     * Saldo awal per produk (semua produk aktif) pada gudang utama.
     *
     * @return array<int, array<string, mixed>>
     */
    private function makeOpeningStockItems(): array
    {
        $items = [];
        $baseQuantity = 50.0;

        // Produk jasa (type=service) tidak disimpan di gudang, jadi tidak punya saldo awal.
        foreach (Product::query()->where('type', '!=', 'service')->orderBy('id')->get() as $product) {
            $unitCost = (float) $product->cost;

            $items[] = [
                'product_id' => (int) $product->id,
                'unit_id' => (int) $product->unit_id,
                'quantity_before' => 0.0,
                'quantity_adjusted' => $baseQuantity,
                'quantity_after' => $baseQuantity,
                'unit_cost' => $unitCost,
                'total_cost' => $baseQuantity * $unitCost,
                'reason' => 'Saldo awal stok saat migrasi data',
            ];
        }

        return $items;
    }

    private function makeManualAdjustmentItems(Collection $products, int $branchId, float $adjustment): array
    {
        $items = [];
        $i = 0;

        foreach ($products as $product) {
            $i++;
            if ($i > 2) {
                break;
            }

            $quantityBefore = 100.0;

            $quantityAdjusted = $adjustment;
            $quantityAfter = $quantityBefore + $quantityAdjusted;
            $unitCost = (float) $product->cost;
            $totalCost = abs($quantityAdjusted) * $unitCost;

            $items[] = [
                'product_id' => (int) $product->id,
                'unit_id' => (int) $product->unit_id,
                'quantity_before' => $quantityBefore,
                'quantity_adjusted' => $quantityAdjusted,
                'quantity_after' => $quantityAfter,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reason' => 'Penyesuaian stok hasil opname fisik',
            ];
        }

        return $items;
    }

    private function makeStocktakeResultAdjustmentItems(int $inventoryStocktakeId, int $branchId): array
    {
        $items = [];

        $stocktakeItems = InventoryStocktakeItem::query()
            ->where('inventory_stocktake_id', $inventoryStocktakeId)
            ->orderBy('id')
            ->get();

        foreach ($stocktakeItems as $stItem) {
            $product = Product::query()->find($stItem->product_id);
            if ($product === null) {
                continue;
            }

            $quantityBefore = (float) ($stItem->system_quantity ?? 100);

            $quantityAdjusted = (float) ($stItem->variance ?? 0);
            $quantityAfter = $quantityBefore + $quantityAdjusted;
            $unitCost = (float) $product->cost;
            $totalCost = abs($quantityAdjusted) * $unitCost;

            $items[] = [
                'product_id' => (int) $product->id,
                'unit_id' => (int) $product->unit_id,
                'quantity_before' => $quantityBefore,
                'quantity_adjusted' => $quantityAdjusted,
                'quantity_after' => $quantityAfter,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reason' => 'Penyesuaian dari hasil stocktake',
            ];
        }

        if (empty($items)) {
            $products = $this->ensureProductsWithStock($branchId);

            return $this->makeManualAdjustmentItems($products, $branchId, -1.0);
        }

        return $items;
    }
}
