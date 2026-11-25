<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostStockTransferJournalAction;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\Concerns\ReopensDemoPeriodClosings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class StockTransferSampleDataSeeder extends Seeder
{
    use ReopensDemoPeriodClosings;

    protected function seedDemoData(): void
    {
        $adminUserId = User::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? User::query()->value('id');

        $requestedEmployeeId = Employee::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? Employee::query()->value('id');

        $fromWarehouse = Warehouse::query()->where('code', DemoMasterData::WAREHOUSE_MAIN_CODE)->first();
        $toWarehouse = Warehouse::query()->where('code', DemoMasterData::WAREHOUSE_RETURN_CODE)->first();

        if ($fromWarehouse === null || $toWarehouse === null) {
            $branchId = Branch::query()
                ->where('name', DemoMasterData::BRANCH_HEAD_OFFICE)
                ->value('id') ?? Branch::query()->value('id');

            $fromWarehouse = Warehouse::updateOrCreate(
                ['branch_id' => $branchId, 'code' => DemoMasterData::WAREHOUSE_MAIN_CODE],
                ['name' => DemoMasterData::WAREHOUSE_MAIN_NAME]
            );

            $toWarehouse = Warehouse::updateOrCreate(
                ['branch_id' => $branchId, 'code' => DemoMasterData::WAREHOUSE_RETURN_CODE],
                ['name' => DemoMasterData::WAREHOUSE_RETURN_NAME]
            );
        }

        $branchIdForStock = $fromWarehouse->branch_id ?? Branch::query()->value('id');
        $products = $this->ensureProducts($branchIdForStock);

        $year = now()->format('Y');

        // Format nomor berubah (ST-…-9000xx → ST-…-000001..): ganti nama HANYA
        // nomor demo lama, dengan update in-place agar item & movement tetap terhubung.
        foreach (range(1, 6) as $index) {
            $legacy = sprintf('ST-%s-9000%02d', $year, $index);
            $target = sprintf('ST-%s-%06d', $year, $index);

            if (StockTransfer::query()->where('transfer_number', $legacy)->exists()
                && ! StockTransfer::query()->where('transfer_number', $target)->exists()) {
                StockTransfer::query()->where('transfer_number', $legacy)->update(['transfer_number' => $target]);
            }
        }

        $samples = [
            [
                'transfer_number' => "ST-{$year}-000001",
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
                'transfer_number' => "ST-{$year}-000002",
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
                'transfer_number' => "ST-{$year}-000003",
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
                'transfer_number' => "ST-{$year}-000004",
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
                'transfer_number' => "ST-{$year}-000005",
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
                'transfer_number' => "ST-{$year}-000006",
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

        // Tujuan bervariasi (W7-17): sebagian ke Gudang Transit (TRN), sisanya ke
        // Gudang Retur (RTN), bukan semua transfer ke satu gudang.
        $transitWarehouse = Warehouse::query()->where('code', DemoMasterData::WAREHOUSE_TRANSIT_CODE)->first()
            ?? $toWarehouse;

        foreach ($samples as $index => $sample) {
            $items = $sample['items'];
            unset($sample['items']);

            $destination = $index % 2 === 0 ? $transitWarehouse : $toWarehouse;

            $transfer = StockTransfer::updateOrCreate(
                ['transfer_number' => $sample['transfer_number']],
                array_merge([
                    'from_warehouse_id' => $fromWarehouse->id,
                    'to_warehouse_id' => $destination->id,
                    'notes' => 'Pemindahan stok antar gudang',
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

            // Jurnal per tahap (issue #495) lewat jalur yang sama dengan controller.
            $journals = app(PostStockTransferJournalAction::class);
            $transfer->refresh();

            if (in_array($transfer->status, ['in_transit', 'received'], true)) {
                $journals->executeDispatch($transfer);
            }

            if ($transfer->status === 'received') {
                $journals->executeReceive($transfer);
                $journals->executeShortage($transfer);
            }
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

    private function ensureProducts(int $branchId): Collection
    {
        $this->renameLegacyProducts();
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

        return Product::query()->orderBy('id')->take(3)->get();
    }

    private function makeTransferItems(Collection $products, bool $partialReceive): array
    {
        $items = [];
        $index = 0;

        foreach ($products as $product) {
            $index++;
            $quantity = (float) (5 * $index);
            $quantityReceived = null;

            if ($partialReceive) {
                $quantityReceived = $index === 2 ? max(0.0, $quantity - 2.0) : $quantity;
            }

            $items[] = [
                'product_id' => (int) $product->id,
                'unit_id' => (int) $product->unit_id,
                'quantity' => $quantity,
                'quantity_received' => $quantityReceived,
                'unit_cost' => (float) $product->cost,
                'notes' => 'Transfer stok sesuai permintaan',
            ];
        }

        return $items;
    }
}
