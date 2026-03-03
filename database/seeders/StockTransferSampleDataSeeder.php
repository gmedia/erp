<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class StockTransferSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = User::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? User::query()->value('id');

        $requestedEmployeeId = Employee::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? Employee::query()->value('id');

        $fromWarehouse = Warehouse::query()->where('code', 'MAIN')->first();
        $toWarehouse = Warehouse::query()->where('code', 'RTN')->first();

        if ($fromWarehouse === null || $toWarehouse === null) {
            $branchId = Branch::query()->where('name', 'Head Office')->value('id') ?? Branch::query()->value('id');

            $fromWarehouse = Warehouse::updateOrCreate(
                ['branch_id' => $branchId, 'code' => 'MAIN'],
                ['name' => 'Main Warehouse']
            );

            $toWarehouse = Warehouse::updateOrCreate(
                ['branch_id' => $branchId, 'code' => 'RTN'],
                ['name' => 'Return Warehouse']
            );
        }

        $branchIdForStock = $fromWarehouse->branch_id ?? Branch::query()->value('id');
        $products = $this->ensureProducts($branchIdForStock);

        $year = now()->format('Y');

        $samples = [
            [
                'transfer_number' => "ST-{$year}-900001",
                'status' => 'draft',
                'transfer_date' => now()->subDays(10)->toDateString(),
                'expected_arrival_date' => now()->subDays(7)->toDateString(),
                'actors' => [
                    'requested_by' => $requestedEmployeeId,
                    'created_by' => $adminUserId,
                ],
                'items' => $this->makeTransferItems($products, false),
            ],
            [
                'transfer_number' => "ST-{$year}-900002",
                'status' => 'pending_approval',
                'transfer_date' => now()->subDays(9)->toDateString(),
                'expected_arrival_date' => now()->subDays(6)->toDateString(),
                'actors' => [
                    'requested_by' => $requestedEmployeeId,
                    'created_by' => $adminUserId,
                ],
                'items' => $this->makeTransferItems($products, false),
            ],
            [
                'transfer_number' => "ST-{$year}-900003",
                'status' => 'approved',
                'transfer_date' => now()->subDays(8)->toDateString(),
                'expected_arrival_date' => now()->subDays(5)->toDateString(),
                'actors' => [
                    'requested_by' => $requestedEmployeeId,
                    'approved_by' => $adminUserId,
                    'approved_at' => now()->subDays(7),
                    'created_by' => $adminUserId,
                ],
                'items' => $this->makeTransferItems($products, false),
            ],
            [
                'transfer_number' => "ST-{$year}-900004",
                'status' => 'in_transit',
                'transfer_date' => now()->subDays(6)->toDateString(),
                'expected_arrival_date' => now()->subDays(3)->toDateString(),
                'actors' => [
                    'requested_by' => $requestedEmployeeId,
                    'approved_by' => $adminUserId,
                    'approved_at' => now()->subDays(5),
                    'shipped_by' => $adminUserId,
                    'shipped_at' => now()->subDays(4),
                    'created_by' => $adminUserId,
                ],
                'items' => $this->makeTransferItems($products, false),
            ],
            [
                'transfer_number' => "ST-{$year}-900005",
                'status' => 'received',
                'transfer_date' => now()->subDays(5)->toDateString(),
                'expected_arrival_date' => now()->subDays(2)->toDateString(),
                'actors' => [
                    'requested_by' => $requestedEmployeeId,
                    'approved_by' => $adminUserId,
                    'approved_at' => now()->subDays(4),
                    'shipped_by' => $adminUserId,
                    'shipped_at' => now()->subDays(3),
                    'received_by' => $adminUserId,
                    'received_at' => now()->subDays(1),
                    'created_by' => $adminUserId,
                ],
                'items' => $this->makeTransferItems($products, true),
            ],
            [
                'transfer_number' => "ST-{$year}-900006",
                'status' => 'cancelled',
                'transfer_date' => now()->subDays(4)->toDateString(),
                'expected_arrival_date' => null,
                'actors' => [
                    'requested_by' => $requestedEmployeeId,
                    'created_by' => $adminUserId,
                ],
                'items' => $this->makeTransferItems($products, false),
            ],
        ];

        foreach ($samples as $sample) {
            $items = $sample['items'];
            unset($sample['items']);

            $transfer = StockTransfer::updateOrCreate(
                ['transfer_number' => $sample['transfer_number']],
                array_merge([
                    'from_warehouse_id' => $fromWarehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                    'notes' => 'Sample data',
                ], $sample['actors'], [
                    'transfer_date' => $sample['transfer_date'],
                    'expected_arrival_date' => $sample['expected_arrival_date'],
                    'status' => $sample['status'],
                ])
            );

            foreach ($items as $item) {
                StockTransferItem::updateOrCreate(
                    [
                        'stock_transfer_id' => $transfer->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'unit_id' => $item['unit_id'],
                        'quantity' => $item['quantity'],
                        'quantity_received' => $item['quantity_received'],
                        'unit_cost' => $item['unit_cost'],
                        'notes' => $item['notes'],
                    ]
                );
            }

            $productIds = array_map(fn ($item) => (int) $item['product_id'], $items);
            $transfer->items()
                ->whereNotIn('product_id', $productIds)
                ->delete();
        }
    }

    private function ensureProducts(int $branchId): Collection
    {
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

        return Product::query()->orderBy('id')->take(3)->get();
    }

    private function makeTransferItems(Collection $products, bool $partialReceive): array
    {
        $items = [];
        $index = 0;

        foreach ($products as $product) {
            $index++;
            $quantity = (float) (5 * $index);
            $quantityReceived = 0.0;

            if ($partialReceive) {
                $quantityReceived = $index === 2 ? max(0.0, $quantity - 2.0) : $quantity;
            }

            $items[] = [
                'product_id' => (int) $product->id,
                'unit_id' => (int) $product->unit_id,
                'quantity' => $quantity,
                'quantity_received' => $quantityReceived,
                'unit_cost' => (float) $product->cost,
                'notes' => 'Sample item',
            ];
        }

        return $items;
    }
}
