<?php

use App\Actions\AccountingPosting\PostStockTransferJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('stock-movements');

/**
 * GR confirmed (diposting langsung) dengan satu item $quantity @ $unitPrice.
 */
function issue495Receipt(Warehouse $warehouse, Product $product, float $quantity, float $unitPrice, string $date): void
{
    $purchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => $date,
        'status' => 'confirmed',
        'confirmed_by' => User::factory()->create()->id,
        'confirmed_at' => now(),
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'product_id' => $product->id,
        'quantity_received' => $quantity,
        'quantity_accepted' => $quantity,
        'quantity_rejected' => 0,
        'unit_price' => $unitPrice,
    ]);

    app(StockPostingService::class)->postGoodsReceipt($receipt->refresh());
}

/**
 * Penyesuaian stok approved bertanggal $date dengan selisih $quantity (negatif = keluar).
 */
function issue495Adjustment(Warehouse $warehouse, Product $product, float $quantity, CarbonInterface $date): void
{
    $adjustment = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => $date->toDateString(),
        'status' => 'approved',
        'inventory_stocktake_id' => null,
        'journal_entry_id' => null,
        'approved_by' => User::factory()->create()->id,
        'approved_at' => now(),
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity_adjusted' => $quantity,
    ]);

    app(StockPostingService::class)->postStockAdjustment($adjustment->refresh());
}

/**
 * Movement terakhir (moved_at, id) untuk scope produk+gudang.
 */
function issue495LastMovement(Product $product, Warehouse $warehouse): StockMovement
{
    return StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->orderByDesc('moved_at')
        ->orderByDesc('id')
        ->firstOrFail();
}

test('movement masuk menghitung rata-rata tertimbang bergerak dari dua GR beda harga', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495Receipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495Receipt($warehouse, $product, 10, 2000, '2026-01-11');

    $movements = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->orderBy('moved_at')
        ->orderBy('id')
        ->get();

    expect($movements)->toHaveCount(2)
        ->and((float) $movements[0]->average_cost_after)->toBe(1000.0)
        ->and((float) $movements[1]->average_cost_after)->toBe(1500.0)
        ->and((float) $movements[1]->balance_after)->toBe(20.0);
});

test('movement keluar dinilai dengan rata-rata berjalan dan rata-rata tidak berubah', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495Receipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495Receipt($warehouse, $product, 10, 2000, '2026-01-11');
    issue495Adjustment($warehouse, $product, -5, Carbon::parse('2026-01-12'));

    $outflow = issue495LastMovement($product, $warehouse);

    expect($outflow->movement_type)->toBe('adjustment_out')
        ->and((float) $outflow->quantity_out)->toBe(5.0)
        ->and((float) $outflow->unit_cost)->toBe(1500.0)
        ->and((float) $outflow->average_cost_after)->toBe(1500.0)
        ->and((float) $outflow->balance_after)->toBe(15.0);
});

test('nilai persediaan laporan valuasi sama dengan qty dikali rata-rata bergerak', function () {
    $user = createTestUserWithPermissions(['inventory_valuation_report']);
    $warehouse = Warehouse::factory()->create(['name' => 'Gudang Avg']);
    $product = Product::factory()->create(['name' => 'Produk Rata Rata 495', 'cost' => 5000]);

    issue495Receipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495Receipt($warehouse, $product, 10, 2000, '2026-01-11');
    issue495Adjustment($warehouse, $product, -5, Carbon::parse('2026-01-12'));

    Sanctum::actingAs($user, ['*']);

    $row = collect($this->getJson('/api/reports/inventory-valuation')->assertOk()->json('data'))
        ->firstWhere('product.name', 'Produk Rata Rata 495');

    expect($row)->not->toBeNull()
        ->and($row['quantity_on_hand'])->toBe('15.00')
        ->and($row['average_cost'])->toBe('1500.00')
        ->and($row['stock_value'])->toBe('22500.00');
});

test('dispatch transfer keluar memakai rata-rata gudang asal dan tidak mengubahnya', function () {
    $source = Warehouse::factory()->create();
    $destination = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495Receipt($source, $product, 10, 1000, '2026-01-10');
    issue495Receipt($source, $product, 10, 2000, '2026-01-11');

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $source->id,
        'to_warehouse_id' => $destination->id,
        'transfer_date' => '2026-01-13',
        'status' => 'in_transit',
    ]);
    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'quantity' => 4,
    ]);

    app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh());

    $dispatch = StockMovement::query()
        ->where('movement_type', 'transfer_out')
        ->where('reference_id', $transfer->id)
        ->firstOrFail();

    expect((float) $dispatch->unit_cost)->toBe(1500.0)
        ->and((float) $dispatch->average_cost_after)->toBe(1500.0)
        ->and((float) $dispatch->balance_after)->toBe(16.0);
});

/**
 * Invarian GL persediaan + in-transit harus sama dengan Σ nilai kartu (qty × avg)
 * per gudang, setelah tiap tahap. Akun dibaca dari setting in-transit dan kode 11300.
 */
/**
 * Σ saldo GL persediaan + in-transit (jurnal posted) dan Σ nilai kartu (qty × avg).
 *
 * @return array{gl: float, card: float, transit: float}
 */
function issue495Snapshot(Product $product, array $warehouses, Account $inventory): array
{
    $transitAccountId = (int) Setting::get('inventory_in_transit_account_id');

    // Saldo GL = jurnal berstatus posted saja, seperti laporan buku besar.
    $glBalance = fn (int $accountId): float => JournalEntryLine::query()
        ->where('account_id', $accountId)
        ->whereHas('journalEntry', fn ($query) => $query->where('status', 'posted'))
        ->get(['debit', 'credit'])
        ->sum(fn (JournalEntryLine $line): float => (float) $line->debit - (float) $line->credit);

    $card = collect($warehouses)->sum(function (Warehouse $warehouse) use ($product): float {
        $last = issue495LastMovement($product, $warehouse);

        return round((float) $last->balance_after * (float) $last->average_cost_after, 2);
    });

    $transit = $glBalance($transitAccountId);

    return [
        'gl' => round($glBalance($inventory->id) + $transit, 2),
        'card' => round($card, 2),
        'transit' => round($transit, 2),
    ];
}

/**
 * Invarian: GL persediaan + in-transit = Σ kartu (selisih terhadap snapshot awal,
 * karena saldo awal GR tidak dijurnal di test ini) dan saldo in-transit sesuai harapan.
 *
 * @param  array{gl: float, card: float, transit: float}  $before
 */
function issue495AssertInvariant(array $before, array $after, float $expectedTransit): void
{
    // Nilai kartu + nilai dalam perjalanan harus sama dengan GL persediaan + in-transit.
    expect(round($after['gl'] - $before['gl'], 2))
        ->toBe(round(($after['card'] + $after['transit']) - ($before['card'] + $before['transit']), 2));
    expect($after['transit'])->toBe(round($expectedTransit, 2));
}

test('transfer memakai biaya kirim avg asal, bukan item.unit_cost, lewat alur dispatch dan receive nyata', function () {
    $accounts = makeIssue495TransferAccounts();
    $source = Warehouse::factory()->create();
    $destination = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    // Asal: avg 1500 (dua GR beda harga). Tujuan: avg 1000.
    issue495Receipt($source, $product, 10, 1000, '2026-01-10');
    issue495Receipt($source, $product, 10, 2000, '2026-01-11');
    issue495Receipt($destination, $product, 5, 1000, '2026-01-10');

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $source->id,
        'to_warehouse_id' => $destination->id,
        'transfer_number' => 'ST-2026-495001',
        'transfer_date' => '2026-01-13',
        'status' => 'in_transit',
        'journal_entry_id' => null,
    ]);
    $item = StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'quantity' => 4,
        'quantity_received' => 0,
        'unit_cost' => 0,
    ]);

    $posting = app(StockPostingService::class);
    $journals = app(PostStockTransferJournalAction::class);
    $warehouses = [$source, $destination];
    $before = issue495Snapshot($product, $warehouses, $accounts['inventory']);

    // Dispatch: 4 keluar @1500 dari asal.
    $posting->postStockTransferDispatch($transfer->refresh());
    $journals->executeDispatch($transfer->refresh());

    $dispatch = StockMovement::query()
        ->where('movement_type', 'transfer_out')
        ->where('source_line_id', $item->id)
        ->firstOrFail();

    expect((float) $dispatch->unit_cost)->toBe(1500.0)
        ->and((float) issue495LastMovement($product, $source)->balance_after)->toBe(16.0);

    // Dispatch: in-transit 4 x 1500 = 6000; GL persediaan + in-transit mengikuti Σ kartu.
    $afterDispatch = issue495Snapshot($product, $warehouses, $accounts['inventory']);
    issue495AssertInvariant($before, $afterDispatch, 6000.0);

    // Receive sebagian: 3 dari 4 diterima, 1 selisih.
    $transfer->refresh()->forceFill([
        'status' => 'received',
        'received_at' => '2026-01-14 09:00:00',
    ])->save();
    $item->forceFill(['quantity_received' => 3])->saveQuietly();

    $posting->postStockTransferReceive($transfer->refresh());
    $receiveJournal = $journals->executeReceive($transfer->refresh());
    $shortageJournal = $journals->executeShortage($transfer->refresh());

    $transferIn = StockMovement::query()
        ->where('movement_type', 'transfer_in')
        ->where('source_line_id', $item->id)
        ->firstOrFail();

    // transfer_in memakai biaya kirim (avg asal saat dispatch), bukan item.unit_cost = 0.
    expect((float) $transferIn->unit_cost)->toBe((float) $dispatch->unit_cost)
        ->and((float) $transferIn->unit_cost)->toBe(1500.0)
        // Avg tujuan = (5 x 1000 + 3 x 1500) / 8 = 1187.5.
        ->and((float) $transferIn->average_cost_after)->toBe(1187.5)
        ->and((float) $transferIn->balance_after)->toBe(8.0)
        ->and((float) issue495LastMovement($product, $destination)->average_cost_after)->toBe(1187.5);

    expect((float) $receiveJournal->total_debit)->toBe(4500.0)
        ->and((float) $shortageJournal->total_debit)->toBe(1500.0);

    // Setelah receive: in-transit 0 (6000 - 4500 - 1500); GL tetap sama dengan Σ kartu.
    $afterReceive = issue495Snapshot($product, $warehouses, $accounts['inventory']);
    issue495AssertInvariant($before, $afterReceive, 0.0);
});

/**
 * Akun persediaan (11300), beban selisih, dan in-transit untuk jurnal transfer.
 *
 * @return array{inventory: Account, shortage: Account, transit: Account}
 */
function makeIssue495TransferAccounts(): array
{
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coaVersion = CoaVersion::factory()->create([
        'status' => 'active',
        'fiscal_year_id' => $fiscalYear->id,
    ]);

    $inventory = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11300',
        'name' => 'Inventory',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    $shortage = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '51000',
        'name' => 'Inventory Shortage Expense',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    $transit = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11310',
        'name' => 'Persediaan dalam Perjalanan',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    Setting::set('inventory_in_transit_account_id', (string) $transit->id);

    return ['inventory' => $inventory, 'shortage' => $shortage, 'transit' => $transit];
}
