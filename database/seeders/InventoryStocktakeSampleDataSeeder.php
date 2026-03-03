<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class InventoryStocktakeSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = User::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? User::query()->value('id');

        $warehouse = Warehouse::query()->where('code', 'MAIN')->first();

        if ($warehouse === null) {
            $branchId = Branch::query()->where('name', 'Head Office')->value('id') ?? Branch::query()->value('id');
            $warehouse = Warehouse::updateOrCreate(
                ['branch_id' => $branchId, 'code' => 'MAIN'],
                ['name' => 'Main Warehouse']
            );
        }

        $branchIdForStock = $warehouse->branch_id ?? Branch::query()->value('id');
        $products = $this->ensureProductsWithStock($branchIdForStock);
        $categoryId = $products->first()?->category_id ?? ProductCategory::query()->value('id');

        $year = now()->format('Y');

        $samples = [
            [
                'stocktake_number' => "SO-{$year}-920001",
                'status' => 'draft',
                'stocktake_date' => now()->subDays(8)->toDateString(),
                'completed' => null,
                'items' => $this->makeStocktakeItems($products, $branchIdForStock, $adminUserId, 'draft'),
            ],
            [
                'stocktake_number' => "SO-{$year}-920002",
                'status' => 'in_progress',
                'stocktake_date' => now()->subDays(6)->toDateString(),
                'completed' => null,
                'items' => $this->makeStocktakeItems($products, $branchIdForStock, $adminUserId, 'in_progress'),
            ],
            [
                'stocktake_number' => "SO-{$year}-920003",
                'status' => 'completed',
                'stocktake_date' => now()->subDays(4)->toDateString(),
                'completed' => ['completed_by' => $adminUserId, 'completed_at' => now()->subDays(2)],
                'items' => $this->makeStocktakeItems($products, $branchIdForStock, $adminUserId, 'completed'),
            ],
            [
                'stocktake_number' => "SO-{$year}-920004",
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
                    'notes' => 'Sample data',
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

    private function ensureProductsWithStock(int $branchId): Collection
    {
        $products = Product::query()
            ->whereIn('code', ['INV-SMP-001', 'INV-SMP-002', 'INV-SMP-003'])
            ->orderBy('id')
            ->get();

        if ($products->count() >= 3) {
            return $products->take(3);
        }

        $productIdsWithStock = ProductStock::query()
            ->where('branch_id', $branchId)
            ->orderBy('product_id')
            ->limit(3)
            ->pluck('product_id');

        if ($productIdsWithStock->count() >= 3) {
            return Product::query()
                ->whereIn('id', $productIdsWithStock)
                ->orderBy('id')
                ->get();
        }

        $products = Product::query()->orderBy('id')->take(3)->get();
        if ($products->count() >= 3) {
            return $products;
        }

        $categoryId = ProductCategory::query()->value('id');
        $unitId = Unit::query()->value('id');

        $samples = [
            ['code' => 'INV-SMP-001', 'name' => 'Inventory Sample Product A', 'type' => 'raw_material', 'cost' => 10000],
            ['code' => 'INV-SMP-002', 'name' => 'Inventory Sample Product B', 'type' => 'raw_material', 'cost' => 15000],
            ['code' => 'INV-SMP-003', 'name' => 'Inventory Sample Product C', 'type' => 'finished_good', 'cost' => 25000],
        ];

        foreach ($samples as $sample) {
            $product = Product::updateOrCreate(
                ['code' => $sample['code']],
                [
                    'name' => $sample['name'],
                    'type' => $sample['type'],
                    'category_id' => $categoryId,
                    'unit_id' => $unitId,
                    'branch_id' => $branchId,
                    'cost' => $sample['cost'],
                    'selling_price' => 0,
                    'billing_model' => 'one_time',
                    'is_recurring' => false,
                    'allow_one_time_purchase' => true,
                    'is_manufactured' => false,
                    'is_purchasable' => true,
                    'is_sellable' => true,
                    'is_taxable' => false,
                    'status' => 'active',
                ]
            );

            ProductStock::updateOrCreate(
                ['product_id' => $product->id, 'branch_id' => $branchId],
                [
                    'quantity_on_hand' => 100,
                    'quantity_reserved' => 0,
                    'minimum_quantity' => 10,
                    'average_cost' => $sample['cost'],
                ]
            );
        }

        return Product::query()
            ->whereIn('code', ['INV-SMP-001', 'INV-SMP-002', 'INV-SMP-003'])
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
            $systemQuantity = (float) (ProductStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->value('quantity_on_hand') ?? 0);

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
                'notes' => 'Sample item',
                'counted_by' => $countedBy,
                'counted_at' => $countedAt,
            ];
        }

        return $items;
    }
}
