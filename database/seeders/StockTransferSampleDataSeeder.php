<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\Concerns\ReopensDemoPeriodClosings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

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

        $products = $this->ensureProducts();

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

            // Jurnal transfer (issue #495) diposting di StockMovementSampleDataSeeder:
            // biaya kirim diambil dari movement transfer_out yang baru ada setelah
            // kartu stok diposting, jadi posting di sini akan menghasilkan nol.
        }
    }

    /**
     * Barang transfer = produk katalog yang sudah ada (#503 butir 1), bukan `INV-2026-*` duplikat.
     */
    private function ensureProducts(): Collection
    {
        $products = DemoMasterData::stockDemoProducts();

        if ($products->count() < count(DemoMasterData::STOCK_DEMO_PRODUCT_CODES)) {
            throw new RuntimeException('Katalog demo belum lengkap; jalankan ProductSampleDataSeeder dulu.');
        }

        return $products;
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
