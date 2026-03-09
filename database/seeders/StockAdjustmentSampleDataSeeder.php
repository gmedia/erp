<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class StockAdjustmentSampleDataSeeder extends Seeder
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

        $year = now()->format('Y');
        $completedStocktakeNumber = "SO-{$year}-920003";
        $completedStocktakeId = InventoryStocktake::query()
            ->where('stocktake_number', $completedStocktakeNumber)
            ->value('id');

        $samples = [
            [
                'adjustment_number' => "SA-{$year}-910001",
                'status' => 'draft',
                'adjustment_date' => now()->subDays(6)->toDateString(),
                'adjustment_type' => 'other',
                'inventory_stocktake_id' => null,
                'approved' => null,
                'items' => $this->makeManualAdjustmentItems($products, $branchIdForStock, -2.0),
            ],
            [
                'adjustment_number' => "SA-{$year}-910002",
                'status' => 'pending_approval',
                'adjustment_date' => now()->subDays(5)->toDateString(),
                'adjustment_type' => 'damage',
                'inventory_stocktake_id' => null,
                'approved' => null,
                'items' => $this->makeManualAdjustmentItems($products, $branchIdForStock, -5.0),
            ],
            [
                'adjustment_number' => "SA-{$year}-910003",
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
                'adjustment_number' => "SA-{$year}-910004",
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
                    'notes' => 'Sample data',
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

    private function makeManualAdjustmentItems(Collection $products, int $branchId, float $adjustment): array
    {
        $items = [];
        $i = 0;

        foreach ($products as $product) {
            $i++;
            if ($i > 2) {
                break;
            }

            $quantityBefore = (float) (ProductStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->value('quantity_on_hand') ?? 0);

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
                'reason' => 'Sample item',
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

            $quantityBefore = (float) (ProductStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->value('quantity_on_hand') ?? $stItem->system_quantity);

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
                'reason' => 'Generated from stocktake sample',
            ];
        }

        if (empty($items)) {
            $products = $this->ensureProductsWithStock($branchId);

            return $this->makeManualAdjustmentItems($products, $branchId, -1.0);
        }

        return $items;
    }
}
