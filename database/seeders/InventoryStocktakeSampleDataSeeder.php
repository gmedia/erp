<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

        $products = $this->ensureProductsWithStock();
        // Kategori stocktake = kategori barang yang dihitung, bukan kategori pertama di master (PR #432 butir 1).
        $categoryId = (int) $products->first()->product_category_id;
        $branchIdForStock = $warehouse->branch_id ?? Branch::query()->value('id');

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

    /**
     * Barang opname = produk katalog yang sudah ada (#503 butir 1). Produk `INV-2026-*` lama
     * yang menduplikasi katalog dipindahkan relasinya ke katalog lalu dihapus (idempoten).
     */
    private function ensureProductsWithStock(): Collection
    {
        $this->retireLegacyDuplicateProducts();

        $products = DemoMasterData::stockDemoProducts();

        if ($products->count() < count(DemoMasterData::STOCK_DEMO_PRODUCT_CODES)) {
            throw new RuntimeException('Katalog demo belum lengkap; jalankan ProductSampleDataSeeder dulu.');
        }

        return $products;
    }

    /**
     * Membereskan produk `INV-2026-*` lama yang menduplikasi katalog (#503 butir 1).
     *
     * Relasi TIDAK dialihkan: pengalihan tidak aman karena produk punya banyak tabel transaksi
     * (FK product_id) dan mengubah stock_movements membuat saldo/avg cost kartu stok tidak
     * konsisten. Aturannya:
     * - tidak ada referensi sama sekali -> dihapus;
     * - ada referensi -> dinonaktifkan (status inactive) dan diberi nama arsip yang jelas,
     *   sehingga tidak lagi tampil sebagai produk aktif duplikat.
     * Idempoten: produk yang sudah diarsipkan tidak diproses ulang.
     */
    private function retireLegacyDuplicateProducts(): void
    {
        $legacy = Product::query()
            ->whereIn('code', DemoMasterData::LEGACY_STOCK_DEMO_PRODUCT_CODES)
            ->get();

        foreach ($legacy as $duplicate) {
            if ($duplicate->status === 'inactive' && str_starts_with((string) $duplicate->name, 'Arsip duplikat')) {
                continue;
            }

            if (! $this->isProductReferenced($duplicate->id)) {
                $duplicate->delete();

                continue;
            }

            $duplicate->update([
                'status' => 'inactive',
                'name' => 'Arsip duplikat ' . $duplicate->code . ' - ' . $duplicate->name,
            ]);
        }
    }

    /**
     * Apakah produk dirujuk tabel mana pun (semua FK product_id di migrasi).
     */
    private function isProductReferenced(int $productId): bool
    {
        $referencingTables = [
            'stock_movements', 'stock_transfer_items', 'stock_adjustment_items',
            'inventory_stocktake_items', 'purchase_request_items', 'purchase_order_items',
            'goods_receipt_items', 'supplier_return_items', 'supplier_bill_items',
            'customer_invoice_items', 'credit_note_items', 'production_orders',
            'production_order_items', 'customer_subscriptions',
        ];

        foreach ($referencingTables as $table) {
            if (DB::table($table)->where('product_id', $productId)->exists()) {
                return true;
            }
        }

        return false;
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
