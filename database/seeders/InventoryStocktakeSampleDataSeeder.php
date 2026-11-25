<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryStocktakeSampleDataSeeder extends Seeder
{
    public function run(): void
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
        // Kategori stocktake = kategori barang yang dihitung (semua baris berasal dari
        // produk ini), bukan kategori pertama di master (PR #432 butir 1).
        $categoryId = (int) $products->first()->product_category_id;

        $year = now()->format('Y');

        // Format kode berubah (SO-… → STO-…): bersihkan HANYA nomor demo lama
        // (SO-{tahun}-920001..920009). Nomor pengguna tidak ikut terhapus.
        $legacyDemoNumbers = array_map(
            fn (int $n) => sprintf('SO-%s-92%04d', $year, $n),
            range(1, 9),
        );
        InventoryStocktake::query()
            ->whereIn('stocktake_number', $legacyDemoNumbers)
            ->delete();

        $samples = [
            [
                'stocktake_number' => "STO-{$year}-000001",
                'status' => 'draft',
                'stocktake_date' => now()->subDays(8)->toDateString(),
                'completed' => null,
                'items' => $this->makeStocktakeItems($products, $branchIdForStock, $adminUserId, 'draft'),
            ],
            [
                'stocktake_number' => "STO-{$year}-000002",
                'status' => 'in_progress',
                'stocktake_date' => now()->subDays(6)->toDateString(),
                'completed' => null,
                'items' => $this->makeStocktakeItems($products, $branchIdForStock, $adminUserId, 'in_progress'),
            ],
            [
                'stocktake_number' => "STO-{$year}-000003",
                'status' => 'completed',
                'stocktake_date' => now()->subDays(4)->toDateString(),
                'completed' => ['completed_by' => $adminUserId, 'completed_at' => now()->subDays(2)],
                'items' => $this->makeStocktakeItems($products, $branchIdForStock, $adminUserId, 'completed'),
            ],
            [
                'stocktake_number' => "STO-{$year}-000004",
                'status' => 'cancelled',
                'stocktake_date' => now()->subDays(3)->toDateString(),
                'completed' => null,
                'items' => $this->makeStocktakeItems($products, $branchIdForStock, $adminUserId, 'cancelled'),
            ],
        ];

        foreach ($samples as $sample) {
            $items = $sample['items'];
            unset($sample['items']);

            $completed = $sample['completed'];
            unset($sample['completed']);

            $stocktake = InventoryStocktake::updateOrCreate(
                ['stocktake_number' => $sample['stocktake_number']],
                array_merge([
                    'warehouse_id' => $warehouse->id,
                    'product_category_id' => $categoryId,
                    'notes' => 'Stocktake rutin akhir bulan',
                    'created_by' => $adminUserId,
                ], $completed ?? [], [
                    'stocktake_date' => $sample['stocktake_date'],
                    'status' => $sample['status'],
                ])
            );

            foreach ($items as $item) {
                InventoryStocktakeItem::updateOrCreate(
                    [
                        'inventory_stocktake_id' => $stocktake->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'unit_id' => $item['unit_id'],
                        'system_quantity' => $item['system_quantity'],
                        'counted_quantity' => $item['counted_quantity'],
                        'variance' => $item['variance'],
                        'result' => $item['result'],
                        'notes' => $item['notes'],
                        'counted_by' => $item['counted_by'],
                        'counted_at' => $item['counted_at'],
                    ]
                );
            }

            $productIds = array_map(fn ($item) => (int) $item['product_id'], $items);
            $stocktake->items()
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
            // Barang dagang demo (PR #432 butir 1): kategori Elektronik, harga jual wajar,
            // kategori stocktake mengikuti kategori barangnya.
            $electronicsId = ProductCategory::query()
                ->where('name', DemoMasterData::PRODUCT_CATEGORY_ELECTRONICS)
                ->value('id');

            if ($electronicsId !== null) {
                Product::query()
                    ->whereIn('id', $products->pluck('id'))
                    ->update([
                        'product_category_id' => $electronicsId,
                        'type' => 'finished_good',
                        'selling_price' => DB::raw(
                            'CASE WHEN selling_price > 0 THEN selling_price ELSE cost * 1.5 END'
                        ),
                    ]);
                $products = $products->each(fn (Product $product) => $product->product_category_id = $electronicsId);
            }

            return $products->take(3);
        }

        // Fallback: tiga barang fisik dari satu kategori yang sama, agar kategori stocktake
        // konsisten dengan barang yang dihitung (PR #432 butir 1).
        $fallbackCategoryId = Product::query()
            ->where('type', '!=', 'service')
            ->orderBy('id')
            ->value('product_category_id');
        $products = Product::query()
            ->where('type', '!=', 'service')
            ->where('product_category_id', $fallbackCategoryId)
            ->orderBy('id')
            ->take(3)
            ->get();
        if ($products->count() >= 3) {
            return $products;
        }

        $categoryId = ProductCategory::query()
            ->where('name', DemoMasterData::PRODUCT_CATEGORY_ELECTRONICS)
            ->value('id')
            ?? ProductCategory::query()->value('id');
        $unitId = Unit::query()->value('id');

        $samples = [
            [
                'code' => 'INV-2026-001',
                'name' => 'Kabel HDMI 2 Meter',
                'cost' => 45000,
                'price' => 75000,
            ],
            [
                'code' => 'INV-2026-002',
                'name' => 'Mouse Wireless M170',
                'cost' => 110000,
                'price' => 165000,
            ],
            [
                'code' => 'INV-2026-003',
                'name' => 'Keyboard Mekanik K2',
                'cost' => 420000,
                'price' => 650000,
            ],
        ];

        foreach ($samples as $sample) {
            Product::updateOrCreate(
                ['code' => $sample['code']],
                [
                    'name' => $sample['name'],
                    'type' => 'finished_good',
                    'product_category_id' => $categoryId,
                    'unit_id' => $unitId,
                    'branch_id' => $branchId,
                    'cost' => $sample['cost'],
                    'selling_price' => $sample['price'],
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

    private function makeStocktakeItems(Collection $products, int $branchId, int $adminUserId, string $mode): array
    {
        $items = [];
        $i = 0;

        foreach ($products as $product) {
            $i++;
            $systemQuantity = 100.0;

            $countedQuantity = null;
            $countedBy = null;
            $countedAt = null;

            if ($mode === 'in_progress' && $i === 1) {
                $countedQuantity = $systemQuantity;
                $countedBy = $adminUserId;
                $countedAt = now()->subDays(5);
            }

            if ($mode === 'completed') {
                if ($i === 1) {
                    $countedQuantity = $systemQuantity;
                } elseif ($i === 2) {
                    $countedQuantity = $systemQuantity + 3.0;
                } else {
                    $countedQuantity = max(0.0, $systemQuantity - 2.0);
                }

                $countedBy = $adminUserId;
                $countedAt = now()->subDays(2);
            }

            $variance = $countedQuantity === null ? null : $countedQuantity - $systemQuantity;
            $result = 'uncounted';
            if ($countedQuantity !== null) {
                if ($variance === 0.0) {
                    $result = 'match';
                } elseif ($variance > 0.0) {
                    $result = 'surplus';
                } else {
                    $result = 'deficit';
                }
            }

            $items[] = [
                'product_id' => (int) $product->id,
                'unit_id' => (int) $product->unit_id,
                'system_quantity' => $systemQuantity,
                'counted_quantity' => $countedQuantity,
                'variance' => $variance,
                'result' => $result,
                'notes' => 'Hasil hitung fisik sesuai sistem',
                'counted_by' => $countedBy,
                'counted_at' => $countedAt,
            ];
        }

        return $items;
    }
}
