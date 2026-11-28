<?php

use App\Actions\StockMonitor\IndexStockMonitorAction;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use App\Support\Demo\DemoCalendar;
use Database\Seeders\DemoMasterData;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\RefreshDatabaseSeededOnce;

uses(RefreshDatabaseSeededOnce::class)->group('demo-integrity');

// Seed is shared across this file's tests; reset migration state so the next file re-migrates.
afterAll(fn () => RefreshDatabaseState::$migrated = false);

/**
 * Seed the full demo dataset. Invariants below must hold for the data that
 * ships to every on-premise install, so they are asserted on the real
 * DatabaseSeeder output rather than on hand-built fixtures.
 */
function seedDemoData(): void
{
    // Seed exactly what ships. No seeder may write a journal computed from
    // the difference between GL and subledger (issue #357).
    test()->seed();
}

/**
 * Read-only invariants registered by invariant(), keyed by test name. The replay
 * test at the end of this file runs all of them against the state after a second `db:seed`.
 *
 * @return array<string, Closure>
 */
function demoInvariantChecks(?string $name = null, ?Closure $check = null): array
{
    static $checks = [];

    if ($name !== null && $check !== null) {
        $checks[$name] = $check;
    }

    return $checks;
}

/**
 * Registers a non-mutating invariant as a normal test and in the replay registry.
 * Checks that delete or change data must stay plain `test()` calls, since they
 * would corrupt the state the other checks read.
 */
function invariant(string $name, Closure $check): void
{
    demoInvariantChecks($name, $check);

    test($name, $check);
}

function activeCoaAccountId(string $code): ?int
{
    $fiscalYear = FiscalYear::query()->where('status', 'open')->first();

    if (! $fiscalYear) {
        return null;
    }

    $coaVersion = CoaVersion::query()
        ->where('fiscal_year_id', $fiscalYear->id)
        ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
        ->first();

    if (! $coaVersion) {
        return null;
    }

    return Account::query()
        ->where('coa_version_id', $coaVersion->id)
        ->where('code', $code)
        ->value('id');
}

function postedBalance(int $accountId, bool $debitNormal): float
{
    $row = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->where('journal_entry_lines.account_id', $accountId)
        ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as debit, COALESCE(SUM(journal_entry_lines.credit), 0) as credit')
        ->first();

    $debit = (float) ($row->debit ?? 0);
    $credit = (float) ($row->credit ?? 0);

    return $debitNormal ? $debit - $credit : $credit - $debit;
}

/**
 * Saldo kas bank (kode 11100 pada COA lama, 11110 pada COA aktif) sampai tanggal tersebut.
 *
 * Kedua kode dijumlahkan karena saldo FY lama dipindahkan ke COA aktif lewat jurnal
 * OPENING-FY: akun lama tetap menyimpan mutasi tahun lalu, akun aktif memuat saldo awal
 * hasil pemetaan. Tidak ada mutasi yang dihitung dua kali.
 */
function bankCashBalanceOn(string $date): float
{
    $row = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
        ->where('journal_entries.status', 'posted')
        ->whereIn('accounts.code', ['11100', '11110'])
        ->whereDate('journal_entries.entry_date', '<=', $date)
        ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as debit, COALESCE(SUM(journal_entry_lines.credit), 0) as credit')
        ->first();

    return (float) ($row->debit ?? 0) - (float) ($row->credit ?? 0);
}

/**
 * Saldo kartu stok produk+gudang setelah seluruh movement pada tanggal tersebut
 * (balance_after movement terakhir, urutan moved_at lalu id).
 */
function stockCardBalanceOn(int $productId, int $warehouseId, string $date): float
{
    $balance = DB::table('stock_movements')
        ->where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->whereDate('moved_at', '<=', $date)
        ->orderByDesc('moved_at')
        ->orderByDesc('id')
        ->value('balance_after');

    return (float) ($balance ?? 0.0);
}

// W7-01 — void/cancelled/draft documents must not carry an outstanding balance.
// Read the raw column, not the model accessor, so a corrupted DB value cannot hide behind it.
invariant('W7-01 void, cancelled and draft documents carry no amount due', function () {
    $inactiveStatuses = ['draft', 'cancelled', 'void'];

    $invoices = DB::table('customer_invoices')
        ->whereIn('status', $inactiveStatuses)
        ->get(['invoice_number', 'status', 'amount_due']);

    foreach ($invoices as $invoice) {
        expect((float) $invoice->amount_due)
            ->toBe(0.0, "Customer invoice {$invoice->invoice_number} ({$invoice->status}) must have amount due 0");
    }

    $bills = DB::table('supplier_bills')
        ->whereIn('status', $inactiveStatuses)
        ->get(['bill_number', 'status', 'amount_due']);

    foreach ($bills as $bill) {
        expect((float) $bill->amount_due)
            ->toBe(0.0, "Supplier bill {$bill->bill_number} ({$bill->status}) must have amount due 0");
    }
});

// W7-02 — AR/AP settlements reconcile with their allocation ledgers.
invariant('W7-02 receipt, credit note and payment allocations match settled amounts', function () {
    foreach (CustomerInvoice::query()->get() as $invoice) {
        $allocated = (float) DB::table('ar_receipt_allocations')
            ->where('customer_invoice_id', $invoice->id)
            ->sum('allocated_amount');
        $credited = (float) DB::table('credit_notes')
            ->where('customer_invoice_id', $invoice->id)
            ->where('status', 'applied')
            ->sum('grand_total');

        expect($allocated)
            ->toBe((float) $invoice->amount_received, "Allocations for {$invoice->invoice_number} must equal amount received");
        expect($credited)
            ->toBe((float) $invoice->credit_note_amount, "Applied credit notes for {$invoice->invoice_number} must equal credit note amount");
    }

    foreach (SupplierBill::query()->get() as $bill) {
        $allocated = (float) DB::table('ap_payment_allocations')
            ->where('supplier_bill_id', $bill->id)
            ->sum('allocated_amount');

        expect($allocated)
            ->toBe((float) $bill->amount_paid, "Payment allocations for {$bill->bill_number} must equal amount paid");
    }
});

// W7-02 — GL control accounts equal the open subledger balances.
invariant('W7-02 GL receivable and payable control accounts equal open subledger balances', function () {
    $receivableId = activeCoaAccountId('11200');
    $payableId = activeCoaAccountId('21100');

    expect($receivableId)->not->toBeNull('Active COA must expose account 11200');
    expect($payableId)->not->toBeNull('Active COA must expose account 21100');

    $openReceivable = (float) DB::table('customer_invoices')
        ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
        ->where('amount_due', '>', 0)
        ->sum('amount_due');
    $openPayable = (float) DB::table('supplier_bills')
        ->whereIn('status', ['confirmed', 'partially_paid', 'overdue'])
        ->where('amount_due', '>', 0)
        ->sum('amount_due');

    expect(round(postedBalance((int) $receivableId, true), 2))
        ->toBe(round($openReceivable, 2), 'GL 11200 must equal the sum of open customer invoice amounts due');
    expect(round(postedBalance((int) $payableId, false), 2))
        ->toBe(round($openPayable, 2), 'GL 21100 must equal the sum of open supplier bill amounts due');
});

// W7-03 — one source of received quantity: confirmed goods receipt lines.
invariant('W7-03 purchase order received quantities derive from confirmed goods receipts', function () {
    $confirmedByItem = DB::table('goods_receipt_items')
        ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
        ->where('goods_receipts.status', 'confirmed')
        ->groupBy('goods_receipt_items.purchase_order_item_id')
        ->selectRaw('goods_receipt_items.purchase_order_item_id as purchase_order_item_id, SUM(goods_receipt_items.quantity_received) as received')
        ->pluck('received', 'purchase_order_item_id');

    foreach (DB::table('purchase_order_items')->get() as $item) {
        $expected = (float) ($confirmedByItem[$item->id] ?? 0);
        expect((float) $item->quantity_received)
            ->toBe($expected, "Purchase order item {$item->id} received quantity must equal confirmed goods receipt quantity");
    }

    foreach (PurchaseOrder::query()->get() as $order) {
        $ordered = (float) DB::table('purchase_order_items')->where('purchase_order_id', $order->id)->sum('quantity');
        $received = (float) DB::table('purchase_order_items')->where('purchase_order_id', $order->id)->sum('quantity_received');
        $outstanding = $ordered - $received;

        if ($order->status === 'closed') {
            expect($outstanding)
                ->toBeLessThanOrEqual(0.0, "Closed purchase order {$order->po_number} must have no outstanding quantity");
        }

        if (in_array($order->status, ['cancelled', 'rejected'], true)) {
            expect($received)
                ->toBe(0.0, "Cancelled/rejected purchase order {$order->po_number} must not have received quantity");
        }
    }
});

// W7-03 (keputusan 10a) — PO-000108 sebagian diterima dan masih menyisakan 13 unit.
invariant('W7-03 demo purchase order PO-000108 is partially received with 13 units outstanding', function () {
    $poNumber = 'PO-' . Carbon::now()->format('Y') . '-000108';
    $order = PurchaseOrder::query()->where('po_number', $poNumber)->first();

    expect($order)->not->toBeNull("Demo data must include purchase order {$poNumber}");
    expect($order->status)->toBe('partially_received', "{$poNumber} must be partially received");

    $ordered = (float) DB::table('purchase_order_items')->where('purchase_order_id', $order->id)->sum('quantity');
    $received = (float) DB::table('purchase_order_items')->where('purchase_order_id', $order->id)->sum('quantity_received');

    expect($ordered - $received)->toBe(13.0, "{$poNumber} must have 13 units outstanding");
});

// W7-03 — a supplier return must belong to the purchase order's supplier.
invariant('W7-03 supplier returns belong to the purchase order supplier and receipt', function () {
    $returns = DB::table('supplier_returns')
        ->join('purchase_orders', 'purchase_orders.id', '=', 'supplier_returns.purchase_order_id')
        ->select('supplier_returns.id', 'supplier_returns.return_number', 'supplier_returns.supplier_id', 'purchase_orders.supplier_id as po_supplier_id')
        ->get();

    expect($returns)->not->toBeEmpty('Demo data must include supplier returns');

    foreach ($returns as $return) {
        expect((int) $return->supplier_id)
            ->toBe((int) $return->po_supplier_id, "Supplier return {$return->return_number} must use the purchase order supplier");
    }

    $receipts = DB::table('supplier_returns')
        ->join('goods_receipts', 'goods_receipts.id', '=', 'supplier_returns.goods_receipt_id')
        ->whereNotNull('supplier_returns.goods_receipt_id')
        ->select('supplier_returns.return_number', 'goods_receipts.purchase_order_id as gr_po_id', 'supplier_returns.purchase_order_id as sr_po_id', 'goods_receipts.status as gr_status')
        ->get();

    foreach ($receipts as $row) {
        expect((int) $row->gr_po_id)
            ->toBe((int) $row->sr_po_id, "Supplier return {$row->return_number} goods receipt must belong to the same purchase order");
        expect($row->gr_status)
            ->toBe('confirmed', "Supplier return {$row->return_number} must reference a confirmed goods receipt");
    }
});

// W7-04 — movement ledger chain is internally consistent per product+warehouse.
invariant('W7-04 stock movement balances form a correct chain per product and warehouse', function () {
    $movements = DB::table('stock_movements')
        ->orderBy('product_id')
        ->orderBy('warehouse_id')
        ->orderBy('moved_at')
        ->orderBy('id')
        ->get(['id', 'product_id', 'warehouse_id', 'quantity_in', 'quantity_out', 'balance_after', 'moved_at']);

    expect($movements)->not->toBeEmpty('Demo data must produce stock movements');

    $balance = [];
    $scope = null;

    foreach ($movements as $movement) {
        $key = $movement->product_id . ':' . $movement->warehouse_id;

        if ($key !== $scope) {
            $scope = $key;
            $balance[$key] = 0.0;
        }

        $balance[$key] += (float) $movement->quantity_in - (float) $movement->quantity_out;

        expect(round((float) $movement->balance_after, 2))
            ->toBe(round($balance[$key], 2), "Movement {$movement->id} balance_after must equal the running balance for {$key}");
    }

    expect(min($balance))->toBeGreaterThanOrEqual(0.0, 'Running stock balance must never go negative');
});

// W7-04 — the reverse direction: every eligible document line must have a movement with the same quantity.
invariant('W7-04 every eligible document line has a matching stock movement', function () {
    $movementSums = DB::table('stock_movements')
        ->whereNotNull('reference_type')
        ->groupBy('reference_type', 'reference_id', 'product_id')
        ->selectRaw('reference_type, reference_id, product_id, SUM(quantity_in) as qty_in, SUM(quantity_out) as qty_out')
        ->get()
        ->keyBy(fn ($row) => $row->reference_type . '#' . $row->reference_id . '#' . $row->product_id);

    $checks = [
        [
            // Only accepted quantity is posted to stock (StockPostingService::postGoodsReceipt).
            'type' => GoodsReceipt::class,
            'header' => 'goods_receipts',
            'lines' => 'goods_receipt_items',
            'fk' => 'goods_receipt_id',
            'status' => 'confirmed',
            'qty' => 'quantity_accepted',
            'direction' => 'in',
        ],
        [
            'type' => SupplierReturn::class,
            'header' => 'supplier_returns',
            'lines' => 'supplier_return_items',
            'fk' => 'supplier_return_id',
            'status' => 'confirmed',
            'qty' => 'quantity_returned',
            'direction' => 'out',
        ],
        [
            'type' => StockAdjustment::class,
            'header' => 'stock_adjustments',
            'lines' => 'stock_adjustment_items',
            'fk' => 'stock_adjustment_id',
            'status' => 'approved',
            'qty' => 'quantity_adjusted',
            'direction' => null,
        ],
    ];

    $checked = 0;
    $failures = [];

    foreach ($checks as $check) {
        $eligibleIds = DB::table($check['header'])->where('status', $check['status'])->pluck('id');

        foreach (DB::table($check['lines'])->whereIn($check['fk'], $eligibleIds)->get() as $line) {
            $key = $check['type'] . '#' . $line->{$check['fk']} . '#' . $line->product_id;
            $movement = $movementSums->get($key);

            // Collect every violation instead of stopping at the first, so one broken
            // document cannot hide a break in another document type.
            if ($movement === null) {
                $failures[] = "Eligible {$check['header']}#{$line->{$check['fk']}} product {$line->product_id} must have a stock movement";
                $checked++;

                continue;
            }

            // Penyesuaian bertanda (negatif = keluar). StockPostingService menyimpan besaran
            // absolut di quantity_in/quantity_out, jadi bandingkan besaran, bukan tanda.
            $expected = abs((float) $line->{$check['qty']});
            $actual = match ($check['direction']) {
                'in' => (float) $movement->qty_in,
                'out' => (float) $movement->qty_out,
                default => (float) $movement->qty_in + (float) $movement->qty_out,
            };

            if (round($actual, 2) !== round($expected, 2)) {
                $failures[] = "Movement quantity for {$key} must equal document line quantity";
            }

            $checked++;
        }
    }

    expect($failures)->toBeEmpty(implode(' | ', $failures));
    expect($checked)->toBeGreaterThan(0, 'Demo data must include eligible stock document lines');
});

// W7-04 — a received transfer is two legs: transfer_out = dispatched quantity,
// transfer_in = quantity_received. Every received line must have both movements.
invariant('W7-04 every received stock transfer line has matching transfer_out and transfer_in movements', function () {
    $legs = DB::table('stock_movements')
        ->where('reference_type', StockTransfer::class)
        ->whereIn('movement_type', ['transfer_out', 'transfer_in'])
        ->groupBy('reference_id', 'product_id', 'movement_type')
        ->selectRaw('reference_id, product_id, movement_type, SUM(quantity_in) as qty_in, SUM(quantity_out) as qty_out')
        ->get()
        ->keyBy(fn ($row) => $row->reference_id . '#' . $row->product_id . '#' . $row->movement_type);

    $receivedLines = DB::table('stock_transfer_items')
        ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
        ->where('stock_transfers.status', 'received')
        ->get(['stock_transfer_items.stock_transfer_id', 'stock_transfer_items.product_id', 'stock_transfer_items.quantity', 'stock_transfer_items.quantity_received']);

    expect($receivedLines)->not->toBeEmpty('Demo data must include received stock transfers');

    foreach ($receivedLines as $line) {
        $key = $line->stock_transfer_id . '#' . $line->product_id;

        $out = $legs->get($key . '#transfer_out');
        expect($out)->not->toBeNull("Received transfer {$key} must have a transfer_out movement");
        expect(round((float) $out->qty_out, 2))
            ->toBe(round((float) $line->quantity, 2), "transfer_out quantity for {$key} must equal dispatched quantity");

        $in = $legs->get($key . '#transfer_in');
        expect($in)->not->toBeNull("Received transfer {$key} must have a transfer_in movement");
        expect(round((float) $in->qty_in, 2))
            ->toBe(round((float) $line->quantity_received, 2), "transfer_in quantity for {$key} must equal quantity received");
    }
});

// W7-04 — running balance never goes negative at any movement, not only at the end.
invariant('W7-04 running stock balance is non-negative at every movement', function () {
    $movements = DB::table('stock_movements')
        ->orderBy('product_id')
        ->orderBy('warehouse_id')
        ->orderBy('moved_at')
        ->orderBy('id')
        ->get(['id', 'product_id', 'warehouse_id', 'quantity_in', 'quantity_out']);

    $balance = [];

    foreach ($movements as $movement) {
        $key = $movement->product_id . ':' . $movement->warehouse_id;
        $balance[$key] = ($balance[$key] ?? 0.0) + (float) $movement->quantity_in - (float) $movement->quantity_out;

        expect(round($balance[$key], 2))
            ->toBeGreaterThanOrEqual(0.0, "Movement {$movement->id} drives {$key} below zero");
    }
});

// W7-04 — snapshot quantity equals the latest movement balance (by moved_at, id).
invariant('W7-04 stock snapshot uses the latest movement balance', function () {
    $movements = DB::table('stock_movements')
        ->orderBy('product_id')
        ->orderBy('warehouse_id')
        ->orderBy('moved_at')
        ->orderBy('id')
        ->get(['product_id', 'warehouse_id', 'balance_after']);

    $latest = [];
    foreach ($movements as $movement) {
        $latest[$movement->product_id . ':' . $movement->warehouse_id] = (float) $movement->balance_after;
    }

    // Compare the set of product+warehouse pairs, not raw movement rows.
    $pairs = array_keys($latest);
    $pairCount = count($pairs);

    // Request a page size larger than the pair count so every row is checked, not just page 1.
    $request = IndexStockMonitorRequest::create('/api/stock-monitor', 'GET', ['per_page' => max(100, $pairCount)]);
    $result = app(IndexStockMonitorAction::class)->execute($request);
    $snapshot = collect($result['stocks']->items());

    expect($snapshot)->not->toBeEmpty('Stock monitor must show stock rows');

    $snapshotPairs = $snapshot->map(fn ($row) => $row->product_id . ':' . $row->warehouse_id)->sort()->values()->all();
    $expectedPairs = collect($pairs)->sort()->values()->all();

    expect($snapshotPairs)->toBe($expectedPairs, 'Stock snapshot must cover exactly the product and warehouse pairs that have movements');

    foreach ($snapshot as $row) {
        $key = $row->product_id . ':' . $row->warehouse_id;
        expect(round((float) $row->quantity_on_hand, 2))
            ->toBe(round($latest[$key], 2), "Stock snapshot for {$key} must equal the latest movement balance");
    }
});

// W7-04 — only stock-affecting document states produce movements.
invariant('W7-04 only eligible document states produce stock movements', function () {
    $eligible = DB::table('stock_movements')
        ->whereNotNull('reference_type')
        ->get(['reference_type', 'reference_id'])
        ->map(fn ($row) => $row->reference_type . '#' . $row->reference_id)
        ->unique();

    foreach ($eligible as $pair) {
        [$type, $id] = explode('#', $pair);

        $status = match ($type) {
            GoodsReceipt::class => DB::table('goods_receipts')->where('id', $id)->value('status'),
            SupplierReturn::class => DB::table('supplier_returns')->where('id', $id)->value('status'),
            StockAdjustment::class => DB::table('stock_adjustments')->where('id', $id)->value('status'),
            StockTransfer::class => DB::table('stock_transfers')->where('id', $id)->value('status'),
            default => null,
        };

        $allowed = match ($type) {
            GoodsReceipt::class, SupplierReturn::class => ['confirmed'],
            StockAdjustment::class => ['approved'],
            StockTransfer::class => ['in_transit', 'received'],
            default => [],
        };

        expect(in_array($status, $allowed, true))
            ->toBeTrue("Movement for {$type}#{$id} has ineligible status {$status}");
    }
});

// W7-04 / #495 — nilai kartu stok + barang dalam perjalanan = GL persediaan + GL in-transit.
invariant('W7-04 inventory valuation equals GL inventory account', function () {
    $inventoryAccountId = activeCoaAccountId('11300');
    $inTransitAccountId = activeCoaAccountId('11310');
    expect($inventoryAccountId)->not->toBeNull('Active COA must expose account 11300');
    expect($inTransitAccountId)->not->toBeNull('Active COA must expose account 11310');

    $movements = DB::table('stock_movements')
        ->orderBy('product_id')
        ->orderBy('warehouse_id')
        ->orderBy('moved_at')
        ->orderBy('id')
        ->get(['id', 'product_id', 'warehouse_id', 'balance_after', 'average_cost_after']);

    $latest = [];
    foreach ($movements as $movement) {
        $latest[$movement->product_id . ':' . $movement->warehouse_id] = $movement;
    }

    $valuation = 0.0;
    foreach ($latest as $movement) {
        $cost = $movement->average_cost_after !== null
            ? (float) $movement->average_cost_after
            : (float) DB::table('products')->where('id', $movement->product_id)->value('cost');

        $valuation += (float) $movement->balance_after * $cost;
    }

    // Barang dalam perjalanan tetap persediaan (keputusan #357). Nilainya dari
    // dokumen transfer in_transit, bukan dari selisih GL.
    $inTransit = (float) DB::table('stock_transfer_items')
        ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
        ->where('stock_transfers.status', 'in_transit')
        ->selectRaw('COALESCE(SUM(stock_transfer_items.quantity * stock_transfer_items.unit_cost), 0) as value')
        ->value('value');

    // #495: barang dalam perjalanan punya akun sendiri (11310), jadi invarian
    // membandingkan GL persediaan + in-transit dengan kartu + in-transit.
    $glTotal = postedBalance((int) $inventoryAccountId, true) + postedBalance((int) $inTransitAccountId, true);

    expect(round($glTotal, 2))
        ->toBe(round($valuation + $inTransit, 2), 'GL 11300 + 11310 must equal stock movement valuation plus goods in transit');
});

// W7-04 — invarian 11300 harus menangkap kehilangan jurnal selisih transfer dan
// barang in_transit yang tidak tercatat di GL (issue #357 review PR #384 butir 2).
test('W7-04 11300 invariant fails when a transfer shortage journal is removed', function () {
    $inventoryAccountId = activeCoaAccountId('11300');
    $inTransitAccountId = activeCoaAccountId('11310');

    expect($inTransitAccountId)->not->toBeNull('Active COA must expose account 11310');

    $shortageJournalIds = DB::table('journal_entries')
        ->where('source_type', StockTransfer::class)
        ->where('journal_type', 'st_shortage')
        ->pluck('id');

    expect($shortageJournalIds)->not->toBeEmpty('Demo data must post at least one transfer shortage journal');

    // Hapus sisi in-transit jurnal kekurangan: GL in-transit tidak lagi turun
    // sebesar selisih, sementara kartu stok tidak berubah, jadi invarian harus merah.
    DB::table('journal_entry_lines')
        ->whereIn('journal_entry_id', $shortageJournalIds)
        ->where('account_id', $inTransitAccountId)
        ->delete();

    $valuation = 0.0;
    $latest = [];
    $movements = DB::table('stock_movements')
        ->orderBy('moved_at')
        ->orderBy('id')
        ->get(['product_id', 'warehouse_id', 'balance_after', 'average_cost_after']);

    foreach ($movements as $movement) {
        $latest[$movement->product_id . ':' . $movement->warehouse_id] = $movement;
    }
    foreach ($latest as $movement) {
        $cost = $movement->average_cost_after !== null
            ? (float) $movement->average_cost_after
            : (float) DB::table('products')->where('id', $movement->product_id)->value('cost');
        $valuation += (float) $movement->balance_after * $cost;
    }

    $inTransit = (float) DB::table('stock_transfer_items')
        ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
        ->where('stock_transfers.status', 'in_transit')
        ->selectRaw('COALESCE(SUM(stock_transfer_items.quantity * stock_transfer_items.unit_cost), 0) as value')
        ->value('value');

    $glTotal = postedBalance((int) $inventoryAccountId, true) + postedBalance((int) $inTransitAccountId, true);

    expect(round($glTotal, 2))
        ->not->toBe(round($valuation + $inTransit, 2), 'Invarian 11300 harus merah saat jurnal kekurangan hilang');
});

test('W7-04 11300 invariant depends on in_transit transfer value from the document', function () {
    $inTransitItem = DB::table('stock_transfer_items')
        ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
        ->where('stock_transfers.status', 'in_transit')
        ->orderBy('stock_transfer_items.id')
        ->first(['stock_transfer_items.id', 'stock_transfer_items.quantity', 'stock_transfer_items.unit_cost']);

    expect($inTransitItem)->not->toBeNull('Demo data must include an in_transit transfer line');

    $before = (float) DB::table('stock_transfer_items')
        ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
        ->where('stock_transfers.status', 'in_transit')
        ->selectRaw('COALESCE(SUM(stock_transfer_items.quantity * stock_transfer_items.unit_cost), 0) as value')
        ->value('value');

    // Ubah satu baris in_transit: nilai dokumen berubah, jadi invarian yang
    // memasukkan barang dalam perjalanan juga berubah. Nilai GL tidak ikut berubah.
    DB::table('stock_transfer_items')
        ->where('id', $inTransitItem->id)
        ->update(['quantity' => (float) $inTransitItem->quantity + 1]);

    $after = (float) DB::table('stock_transfer_items')
        ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
        ->where('stock_transfers.status', 'in_transit')
        ->selectRaw('COALESCE(SUM(stock_transfer_items.quantity * stock_transfer_items.unit_cost), 0) as value')
        ->value('value');

    expect(round($after - $before, 2))
        ->toBe(
            round((float) $inTransitItem->unit_cost, 2),
            'Adding 1 unit to an in_transit line must add unit_cost to the in-transit value'
        );
});

// W7-15 (HR) — karyawan demo punya gaji realistis sesuai jabatan (8,5–25 jt).
invariant('W7-15 employee demo salaries are realistic for their position', function () {
    $rows = DB::table('employments')
        ->join('employees', 'employees.id', '=', 'employments.employee_id')
        ->join('positions', 'positions.id', '=', 'employments.position_id')
        ->where('employments.is_current', true)
        ->get(['employees.employee_id', 'employments.salary', 'positions.name as position']);

    expect($rows)->not->toBeEmpty('Demo data must include current employments');

    foreach ($rows as $row) {
        $label = "Employee {$row->employee_id} ({$row->position})";

        expect((float) $row->salary)
            ->toBeGreaterThanOrEqual(8500000.0, "{$label} salary must be at least Rp 8.500.000")
            ->toBeLessThanOrEqual(25000000.0, "{$label} salary must be at most Rp 25.000.000");
    }
});

// W7-17 (Butir 5 Review visual akhir PR #432) — urutan default karyawan (dibuat lebih dulu)
// dimulai dari EMP-000001, bukan EMP-000006.
invariant('W7-17 review visual akhir butir 5: default employee order starts at EMP-000001', function () {
    $firstCreated = DB::table('employees')->orderBy('id')->value('employee_id');

    expect($firstCreated)->toBe('EMP-000001', 'Urutan default karyawan harus dimulai dari EMP-000001');
});

// W7-15 (HR) — kode karyawan demo berurutan mulai dari EMP-000001 tanpa celah di awal.
invariant('W7-15 employee demo codes start at EMP-000001 and are sequential', function () {
    $codes = DB::table('employees')
        ->where('employee_id', 'like', 'EMP-______')
        ->orderBy('employee_id')
        ->pluck('employee_id')
        ->values()
        ->all();

    expect($codes)->not->toBeEmpty('Demo data must include employee codes');
    expect($codes[0])->toBe('EMP-000001', 'Employee code sequence must start at EMP-000001');

    foreach ($codes as $index => $code) {
        expect($code)->toBe(sprintf('EMP-%06d', $index + 1), "Employee code {$code} must follow the sequence");
    }
});

// PR432 — seed di atas data demo lama (email karyawan `main` masih `manager.hr@` dst.)
// tidak boleh membuat baris karyawan kedua: satu user_id = satu karyawan demo.
test('PR432 re-seeding over the main-branch employee emails does not duplicate employees', function () {
    seedDemoData();

    $before = DB::table('employees')->count();
    $legacyEmails = [
        'hendra.wijaya@example.com' => 'manager.hr@example.com',
        'dewi.anggraini@example.com' => 'director.finance@example.com',
        'agus.prasetyo@example.com' => 'staff.it@example.com',
    ];

    foreach ($legacyEmails as $newEmail => $userEmail) {
        DB::table('employees')
            ->where('email', $newEmail)
            ->update(['email' => $userEmail]);
    }

    seedDemoData();

    expect(DB::table('employees')->count())->toBe($before);

    $duplicateUsers = DB::table('employees')
        ->whereNotNull('user_id')
        ->groupBy('user_id')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('user_id');

    expect($duplicateUsers)->toBeEmpty('Each demo user must map to exactly one employee row');
});

// PR432 — supplier demo tidak boleh ganda saat seed ulang di atas data demo `main`
// (email supplier berubah antar versi seed; nama perusahaan tetap).
test('PR432 re-seeding does not duplicate demo supplier names', function () {
    seedDemoData();

    // Simulasikan supplier demo yang di-seed versi `main` (email lain, nama sama).
    DB::table('suppliers')
        ->where('name', 'PT Sumber Makmur Sejahtera')
        ->update(['email' => 'procurement@vendor.example']);

    seedDemoData();

    $duplicates = DB::table('suppliers')
        ->select('name')
        ->groupBy('name')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('name');

    expect($duplicates)->toBeEmpty('Each demo supplier name must map to exactly one row');
});

// W7-15 (HR) — Admin User adalah akun admin, bukan karyawan bergaji ganjil; gajinya wajar.
invariant('W7-15 admin account has a realistic salary', function () {
    $admin = DB::table('employees')
        ->join('employments', 'employments.employee_id', '=', 'employees.id')
        ->where('employments.is_current', true)
        ->where('employees.name', 'Admin User')
        ->first(['employees.id', 'employments.salary']);

    expect($admin)->not->toBeNull('Admin account must keep a matching employee row for CheckPermission');
    expect((float) $admin->salary)->toBeGreaterThanOrEqual(8500000.0);
});

// W7-15 (HR) — email karyawan memakai nama orang, bukan jabatan (mis. manager.hr@).
invariant('W7-15 employee demo emails use person names, not job titles', function () {
    $emails = DB::table('employees')->whereNotNull('email')->pluck('email');

    expect($emails)->not->toBeEmpty();

    foreach ($emails as $email) {
        expect($email)->not->toMatch(
            '/^(manager|director|staff|admin|hr|finance|it|lead|vp|ceo)[._-]/i',
            "Employee email {$email} must use a person name, not a job title"
        );
    }
});

// PR432 butir 5 — karyawan demo tidak menumpuk di satu departemen (maks. 50%).
invariant('PR432 employee demo is not concentrated in one department', function () {
    $demoEmployments = DB::table('employments')
        ->join('employees', 'employees.id', '=', 'employments.employee_id')
        ->where('employments.is_current', true)
        ->where('employees.employee_id', 'like', 'EMP-%');
    $total = (clone $demoEmployments)->count();
    $largest = (int) (clone $demoEmployments)
        ->select('employments.department_id', DB::raw('COUNT(*) as headcount'))
        ->groupBy('employments.department_id')
        ->orderByDesc('headcount')
        ->first()?->headcount;

    expect($total)->toBeGreaterThan(0);
    expect($largest / $total)->toBeLessThanOrEqual(
        0.5,
        'No single department may hold more than half of demo employees'
    );
});

// PR432 butir 5 — kode karyawan demo tidak hanya di awal sebaran:
// tiga kode pertama tidak boleh berada di satu departemen.
invariant('PR432 employee demo spreads codes across departments, not only the first ones', function () {
    $firstDepartments = DB::table('employments')
        ->join('employees', 'employees.id', '=', 'employments.employee_id')
        ->join('departments', 'departments.id', '=', 'employments.department_id')
        ->where('employments.is_current', true)
        ->orderBy('employees.employee_id')
        ->limit(3)
        ->pluck('departments.name')
        ->unique();

    expect($firstDepartments->count())->toBeGreaterThan(
        1,
        'The first three employee codes must not share a single department'
    );
});

// PR432 butir 3 — PO yang melewati approval punya transisi Pending Approval sebelum Confirmed.
invariant('PR432 approved purchase orders record a Pending Approval transition before Confirmed', function () {
    $entities = DB::table('pipeline_entity_states')
        ->join('purchase_orders', 'purchase_orders.id', '=', 'pipeline_entity_states.entity_id')
        ->where('pipeline_entity_states.entity_type', 'App\\Models\\PurchaseOrder')
        ->where('purchase_orders.po_number', 'like', 'PO-%-0001%')
        ->pluck('pipeline_entity_states.id', 'pipeline_entity_states.entity_id');

    expect($entities)->not->toBeEmpty();

    foreach ($entities as $purchaseOrderId => $entityStateId) {
        $states = DB::table('pipeline_state_logs')
            ->join('pipeline_states', 'pipeline_states.id', '=', 'pipeline_state_logs.to_state_id')
            ->where('pipeline_state_logs.pipeline_entity_state_id', $entityStateId)
            ->orderBy('pipeline_state_logs.id')
            ->pluck('pipeline_states.name')
            ->all();

        if (! in_array('Confirmed', $states, true)) {
            continue;
        }

        expect(in_array('Pending Approval', $states, true))->toBeTrue(
            "Purchase order {$purchaseOrderId} must pass Pending Approval before Confirmed"
        );
    }
});

// PR432 butir 3 — pelaku transisi PO dan aset bervariasi, tidak semuanya satu orang.
invariant('PR432 purchase order transitions are performed by more than one actor', function () {
    $performers = DB::table('pipeline_state_logs')
        ->join(
            'pipeline_entity_states',
            'pipeline_entity_states.id',
            '=',
            'pipeline_state_logs.pipeline_entity_state_id'
        )
        ->where('pipeline_entity_states.entity_type', 'App\\Models\\PurchaseOrder')
        ->whereNotNull('pipeline_state_logs.performed_by')
        ->distinct()
        ->count('pipeline_state_logs.performed_by');

    expect($performers)->toBeGreaterThan(1, 'Purchase order transitions must be performed by different users');
});

// W7-15 (HR) — departemen karyawan demo tersebar, tidak menumpuk di HR.
invariant('W7-15 employee demo departments are not concentrated in HR', function () {
    $total = DB::table('employments')->where('is_current', true)->count();
    $hr = DB::table('employments')
        ->join('departments', 'departments.id', '=', 'employments.department_id')
        ->where('employments.is_current', true)
        ->where('departments.name', DemoMasterData::DEPARTMENT_HR)
        ->count();

    expect($total)->toBeGreaterThan(0);
    expect($hr / $total)->toBeLessThanOrEqual(0.25, 'No more than a quarter of demo employees may sit in HR');
});

// W7-15 (HR) — jabatan hanya berisi jabatan, tidak mencampur grade (Junior/Mid-level/Senior).
invariant('W7-15 positions do not mix grade levels with job titles', function () {
    $names = DB::table('positions')->pluck('name')->all();
    $grades = array_values(array_intersect($names, ['Junior', 'Mid-level', 'Senior']));

    expect($grades)->toBeEmpty('Position list must not contain grade levels: ' . implode(', ', $grades));
});

// W7-15 (keuangan) — anggaran tiap periode tidak boleh melebihi anggaran tahunan.
invariant('W7-15 budget period lines do not exceed the annual budget total', function () {
    $budgets = DB::table('budgets')->get(['id', 'name', 'total_amount']);

    expect($budgets)->not->toBeEmpty('Demo data must include budgets');

    foreach ($budgets as $budget) {
        $lineTotal = (float) DB::table('budget_lines')->where('budget_id', $budget->id)->sum('allocated_amount');

        expect($lineTotal)->toBeLessThanOrEqual(
            (float) $budget->total_amount,
            "Budget {$budget->name} lines must not exceed its total",
        );
    }

    $annual = $budgets->firstWhere('name', 'Anggaran Operasional FY 2026')
        ?? $budgets->first(fn ($budget) => ! str_contains($budget->name, 'Triwulan'));
    $quarterly = $budgets->first(fn ($budget) => str_contains($budget->name, 'Triwulan I'));

    expect($annual)->not->toBeNull('Demo data must include the annual operational budget');
    expect($quarterly)->not->toBeNull('Demo data must include the Triwulan I budget');
    expect((float) $quarterly->total_amount)->toBeLessThanOrEqual(
        (float) $annual->total_amount,
        'Anggaran triwulan I tidak boleh melebihi anggaran tahunan',
    );
});

// W7-15 (keuangan) — rekonsiliasi Completed hanya untuk periode yang sudah berakhir, dan
// rekonsiliasi dengan selisih 0 tidak boleh In Progress.
invariant('W7-15 bank reconciliations are Completed only after the period ends', function () {
    $today = now()->startOfDay();

    $reconciliations = DB::table('bank_reconciliations')->get([
        'id', 'period_end', 'status', 'difference',
    ]);

    expect($reconciliations)->not->toBeEmpty('Demo data must include bank reconciliations');

    foreach ($reconciliations as $row) {
        $label = "Bank reconciliation {$row->id} ({$row->period_end})";

        if ($row->status === 'completed') {
            expect(Carbon::parse($row->period_end)->lt($today))
                ->toBeTrue("{$label} is Completed before its period ends");
        }

        if ((float) $row->difference === 0.0) {
            expect($row->status)->not->toBe('in_progress', "{$label} has zero difference and must not be In Progress");
        }
    }
});

// #459 butir 7 — penerimaan AR draft harus punya invoice terbuka milik pelanggan yang sama.
invariant('#459 draft AR receipts have an open invoice of the same customer', function () {
    $openStatuses = ['sent', 'partially_paid', 'overdue'];

    $drafts = DB::table('ar_receipts')->where('status', 'draft')->get(['id', 'receipt_number', 'customer_id']);

    expect($drafts)->not->toBeEmpty('Demo data must include a draft AR receipt');

    foreach ($drafts as $draft) {
        $hasOpenInvoice = DB::table('customer_invoices')
            ->where('customer_id', $draft->customer_id)
            ->whereIn('status', $openStatuses)
            ->where('amount_due', '>', 0)
            ->exists();

        expect($hasOpenInvoice)->toBeTrue(
            "Draft receipt {$draft->receipt_number} must have an open invoice of its customer",
        );
    }
});

// #459 butir 8 — pemasok demo memasok kategori barang yang sesuai dengan kategorinya.
invariant('#459 demo purchase orders match the supplier category to the product category', function () {
    $pairs = DB::table('purchase_order_items')
        ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
        ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
        ->join('supplier_categories', 'supplier_categories.id', '=', 'suppliers.category_id')
        ->join('products', 'products.id', '=', 'purchase_order_items.product_id')
        ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
        ->get([
            'purchase_orders.po_number',
            'suppliers.name as supplier',
            'supplier_categories.name as supplier_category',
            'products.name as product',
            'product_categories.name as product_category',
        ]);

    expect($pairs)->not->toBeEmpty('Demo data must include purchase order items');

    foreach ($pairs as $pair) {
        $allowed = DemoMasterData::SUPPLIER_PRODUCT_CATEGORY_MAP[$pair->supplier_category] ?? [];

        expect(in_array($pair->product_category, $allowed, true))->toBeTrue(
            "{$pair->po_number}: supplier {$pair->supplier} ({$pair->supplier_category}) "
            . "must not supply {$pair->product} ({$pair->product_category})",
        );
    }
});

// #459 butir 8 (lanjutan) — PO demo tidak boleh jatuh ke satu pemasok saja (rotasi vendor, W5-23).
invariant('#459 demo purchase orders are spread across at least three suppliers', function () {
    $suppliers = DB::table('purchase_orders')
        ->distinct()
        ->pluck('supplier_id');

    expect($suppliers->count())->toBeGreaterThanOrEqual(3, 'Demo POs must be spread across at least three suppliers');
});

// W7-15 (keuangan) — recurring journal Monthly berjarak tepat satu bulan, dan nilainya wajar.
invariant('W7-15 monthly recurring journals run one month apart with plausible amounts', function () {
    $journals = DB::table('recurring_journals')
        ->where('frequency', 'monthly')
        ->orderBy('next_run_date')
        ->get(['name', 'next_run_date', 'total_amount']);

    expect($journals)->not->toBeEmpty('Demo data must include monthly recurring journals');

    $dates = $journals->pluck('next_run_date')->map(fn ($date) => Carbon::parse($date)->startOfMonth()->toDateString())->unique()->values();
    expect($dates->count())->toBe(1, 'Monthly recurring journals must share a single schedule month');

    $amounts = $journals->keyBy('name');
    $rent = (float) ($amounts['Sewa Kantor Bulanan']->total_amount ?? 0);
    $cleaning = (float) ($amounts['Jasa Kebersihan']->total_amount ?? 0);

    expect($rent)->toBeGreaterThan(0.0);
    expect($rent)->toBeGreaterThan($cleaning, 'Sewa kantor harus lebih besar dari jasa kebersihan');
});

// W7-15 (keuangan) — periode closing Jan/Feb ditutup pada awal bulan berikutnya, bukan tanggal seed.
invariant('W7-15 monthly period closings are closed at the start of the following month', function () {
    $closings = DB::table('period_closings')
        ->where('status', 'closed')
        ->where('closing_type', 'monthly')
        ->get(['period_month', 'period_year', 'closed_at']);

    expect($closings)->not->toBeEmpty('Demo data must include closed monthly periods');

    foreach ($closings as $closing) {
        $expected = Carbon::create($closing->period_year, $closing->period_month, 1)->addMonth()->startOfDay();
        $actual = Carbon::parse($closing->closed_at)->startOfDay();

        expect($actual->toDateString())->toBe(
            $expected->toDateString(),
            "Period {$closing->period_month}/{$closing->period_year} must be closed on the start of the next month",
        );
    }
});

// Every posted document must reference a posted, balanced journal (issue #357 scope 4).
invariant('W7-02 posted documents reference a posted and balanced journal', function () {
    $documents = [
        ['customer_invoices', 'invoice_number', ['sent', 'partially_paid', 'paid', 'overdue']],
        ['supplier_bills', 'bill_number', ['confirmed', 'partially_paid', 'paid', 'overdue']],
        ['goods_receipts', 'gr_number', ['confirmed']],
        ['supplier_returns', 'return_number', ['confirmed']],
        ['stock_adjustments', 'adjustment_number', ['approved']],
    ];

    $checked = 0;

    foreach ($documents as [$table, $numberColumn, $postedStatuses]) {
        $rows = DB::table($table)
            ->whereIn('status', $postedStatuses)
            ->get(['id', 'status', 'journal_entry_id', $numberColumn]);

        foreach ($rows as $row) {
            $label = "{$table} {$row->{$numberColumn}} ({$row->status})";

            expect($row->journal_entry_id)->not->toBeNull("{$label} must reference a journal entry");

            $journal = DB::table('journal_entries')->where('id', $row->journal_entry_id)->first(['status']);
            expect($journal)->not->toBeNull("{$label} journal must exist");
            expect($journal->status)->toBe('posted', "{$label} journal must be posted");

            $totals = DB::table('journal_entry_lines')
                ->where('journal_entry_id', $row->journal_entry_id)
                ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
                ->first();

            expect(round((float) $totals->debit, 2))
                ->toBe(round((float) $totals->credit, 2), "{$label} journal must balance");

            $checked++;
        }
    }

    expect($checked)->toBeGreaterThan(0, 'Demo data must include posted documents with journals');
});

// W7-05 — book value is always cost minus accumulated depreciation.
invariant('W7-05 asset book value equals cost minus accumulated depreciation', function () {
    $assets = DB::table('assets')->get(['asset_code', 'purchase_cost', 'accumulated_depreciation', 'book_value', 'status']);

    expect($assets)->not->toBeEmpty('Demo data must include assets');

    foreach ($assets as $asset) {
        $expected = round((float) $asset->purchase_cost - (float) $asset->accumulated_depreciation, 2);

        expect(round((float) $asset->book_value, 2))
            ->toBe($expected, "Asset {$asset->asset_code} book value must equal cost minus accumulated depreciation");
        expect((float) $asset->accumulated_depreciation)
            ->toBeGreaterThanOrEqual(0.0, "Asset {$asset->asset_code} accumulated depreciation must be non-negative");
        expect((float) $asset->book_value)
            ->toBeGreaterThanOrEqual(0.0, "Asset {$asset->asset_code} book value must be non-negative");
    }

    foreach ($assets->whereIn('status', ['active', 'maintenance']) as $asset) {
        expect((float) $asset->accumulated_depreciation)
            ->toBeGreaterThan(0.0, "In-service asset {$asset->asset_code} must accrue depreciation");
    }
});

// W7-05 — GL equipment net of accumulated depreciation equals the in-service register.
invariant('W7-05 GL equipment minus accumulated depreciation equals in-service book value', function () {
    $equipmentId = activeCoaAccountId('12100');
    $accumulatedId = activeCoaAccountId('12200');

    expect($equipmentId)->not->toBeNull('Active COA must expose account 12100');
    expect($accumulatedId)->not->toBeNull('Active COA must expose account 12200');

    $glEquipment = postedBalance((int) $equipmentId, true);
    $glAccumulated = postedBalance((int) $accumulatedId, false);

    $registerBookValue = (float) DB::table('assets')
        ->whereIn('status', ['active', 'maintenance'])
        ->sum('book_value');

    expect(round($glEquipment - $glAccumulated, 2))
        ->toBe(round($registerBookValue, 2), 'GL equipment net of accumulated depreciation must equal in-service book value');
});

// #442 — an asset capitalised in the GL (has a posted ACQ journal) that is disposed
// or lost carries exactly one posted release journal whose lines zero its cost and
// accumulated depreciation. A disposed asset without an ACQ journal (acquired and
// released before the first open fiscal year) has no release journal at all.
// Every DISP/LOSS journal belongs to an asset with the same ACQ journal.
invariant('#442 disposed and lost assets carry one posted release journal', function () {
    $assets = DB::table('assets')
        ->whereIn('status', ['disposed', 'lost'])
        ->get(['asset_code', 'status', 'purchase_cost', 'accumulated_depreciation']);

    expect($assets)->not->toBeEmpty('Demo data must include disposed or lost assets');

    $equipmentId = activeCoaAccountId('12100');
    $accumulatedId = activeCoaAccountId('12200');

    foreach ($assets as $asset) {
        $prefix = $asset->status === 'disposed' ? 'DISP-' : 'LOSS-';
        $journals = DB::table('journal_entries')
            ->where('reference', $prefix . $asset->asset_code)
            ->where('journal_type', 'system')
            ->get(['id', 'status']);

        $hasAcquisition = DB::table('journal_entries')
            ->where('reference', 'ACQ-' . $asset->asset_code)
            ->where('status', 'posted')
            ->exists();

        if (! $hasAcquisition) {
            expect($journals)->toBeEmpty("Asset {$asset->asset_code} without ACQ journal must have no release journal");

            continue;
        }

        expect($journals)->toHaveCount(1, "Asset {$asset->asset_code} must have exactly one release journal");
        expect($journals[0]->status)->toBe('posted');

        $credit = (float) DB::table('journal_entry_lines')
            ->where('journal_entry_id', $journals[0]->id)
            ->where('account_id', $equipmentId)
            ->sum('credit');

        expect(round($credit, 2))
            ->toBe(round((float) $asset->purchase_cost, 2), "Asset {$asset->asset_code} release must credit its cost");

        $debitAccumulated = (float) DB::table('journal_entry_lines')
            ->where('journal_entry_id', $journals[0]->id)
            ->where('account_id', $accumulatedId)
            ->sum('debit');

        expect(round($debitAccumulated, 2))
            ->toBe(
                round((float) $asset->accumulated_depreciation, 2),
                "Asset {$asset->asset_code} release must debit its accumulated depreciation"
            );
    }

    $releaseReferences = DB::table('journal_entries')
        ->where('journal_type', 'system')
        ->where(fn ($query) => $query->where('reference', 'like', 'DISP-%')->orWhere('reference', 'like', 'LOSS-%'))
        ->pluck('reference');

    foreach ($releaseReferences as $reference) {
        $assetCode = substr((string) $reference, 5);

        expect(DB::table('journal_entries')
            ->where('reference', 'ACQ-' . $assetCode)
            ->where('status', 'posted')
            ->exists())
            ->toBeTrue("Release journal {$reference} must have a posted ACQ journal for the same asset");
    }
});

// W7-05 — every posted depreciation run has a posted journal.
invariant('W7-05 posted depreciation runs have posted journals', function () {
    $runs = DB::table('asset_depreciation_runs')->where('status', 'posted')->get();

    expect($runs)->not->toBeEmpty('Demo data must include a posted depreciation run');

    foreach ($runs as $run) {
        expect($run->journal_entry_id)->not->toBeNull("Depreciation run {$run->id} must reference a journal");

        $journalStatus = DB::table('journal_entries')->where('id', $run->journal_entry_id)->value('status');
        expect($journalStatus)->toBe('posted', "Depreciation run {$run->id} journal must be posted");
    }
});

// W7-05 — depreciation expense in the GL equals the posted run depreciation.
invariant('W7-05 depreciation expense equals posted depreciation run totals', function () {
    $expenseId = activeCoaAccountId('53000');
    expect($expenseId)->not->toBeNull('Active COA must expose account 53000');

    // Akun 53000 dan jurnalnya per CoA/tahun fiskal. Saldo GL dan total run dibatasi ke
    // akun tahun fiskal aktif; FY sebelumnya (CoA archived) diperiksa di invariant terpisah.
    $expenseAccountIds = DB::table('accounts')->where('code', '53000')->pluck('id');
    $glExpense = (float) DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->whereIn('journal_entry_lines.account_id', $expenseAccountIds)
        ->sum('journal_entry_lines.debit');

    $runTotal = (float) DB::table('asset_depreciation_lines')
        ->join('asset_depreciation_runs', 'asset_depreciation_runs.id', '=', 'asset_depreciation_lines.asset_depreciation_run_id')
        ->where('asset_depreciation_runs.status', 'posted')
        ->sum('asset_depreciation_lines.amount');

    expect(round($glExpense, 2))
        ->toBe(round($runTotal, 2), 'GL depreciation expense must equal the sum of posted depreciation run lines');
});

// W7-05 — trial balance (all posted journal lines) stays balanced.
invariant('W7-05 posted journals are balanced', function () {
    $totals = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as debit, COALESCE(SUM(journal_entry_lines.credit), 0) as credit')
        ->first();

    expect(round((float) $totals->debit, 2))
        ->toBe(round((float) $totals->credit, 2), 'Total debits must equal total credits across posted journals');
});

// W7-15 (aset) — stocktake demo hanya memakai kategori barang, bukan jasa.
invariant('W7-15 inventory stocktake demo uses goods categories, not services', function () {
    $categoryNames = DB::table('inventory_stocktakes')
        ->join('product_categories', 'product_categories.id', '=', 'inventory_stocktakes.product_category_id')
        ->pluck('product_categories.name');

    expect($categoryNames)->not->toBeEmpty('Demo data must include inventory stocktakes');

    foreach ($categoryNames as $name) {
        expect($name)->not->toBe(DemoMasterData::PRODUCT_CATEGORY_CONSULTING, 'Stocktake must not count service categories');
    }
});

// W7-15 (aset) — harga motor berbeda wajar dari mobil (motor jauh lebih murah).
invariant('W7-15 motorcycle assets cost far less than cars', function () {
    $motorcycle = DB::table('assets')->where('name', 'like', '%Vario%')->value('purchase_cost');
    $car = DB::table('assets')->where('name', 'like', '%Avanza%')->value('purchase_cost');

    expect($motorcycle)->not->toBeNull('Demo data must include a motorcycle asset');
    expect($car)->not->toBeNull('Demo data must include a car asset');
    expect((float) $motorcycle)->toBeLessThan((float) $car * 0.5, 'A motorcycle must cost well under half a car');
    expect((float) $motorcycle)->toBeGreaterThanOrEqual(15000000.0, 'Motorcycle price must be realistic');
});

// W7-15 (aset) — tanggal beli aset bervariasi, tidak semua tanggal yang sama.
invariant('W7-15 asset purchase dates vary across the demo register', function () {
    $dates = DB::table('assets')->distinct()->pluck('purchase_date');

    expect($dates->count())->toBeGreaterThanOrEqual(8, 'Purchase dates must vary, not repeat one day');
    expect($dates->map(fn ($d) => Carbon::parse($d)->day)->unique()->count())
        ->toBeGreaterThanOrEqual(4, 'Purchase dates must not all fall on the same day of month');
});

// PR #432 review butir 1 — kategori stocktake harus memuat produk yang ada di baris stocktake.
invariant('PR432 stocktake category contains the products counted in its lines', function () {
    $stocktakes = DB::table('inventory_stocktakes')->get(['id', 'stocktake_number', 'product_category_id']);

    expect($stocktakes)->not->toBeEmpty('Demo data must include inventory stocktakes');

    foreach ($stocktakes as $stocktake) {
        $lineCategories = DB::table('inventory_stocktake_items')
            ->join('products', 'products.id', '=', 'inventory_stocktake_items.product_id')
            ->where('inventory_stocktake_items.inventory_stocktake_id', $stocktake->id)
            ->pluck('products.product_category_id')
            ->unique()
            ->all();

        expect($lineCategories)->not->toBeEmpty("Stocktake {$stocktake->stocktake_number} must count products");

        foreach ($lineCategories as $categoryId) {
            expect((int) $categoryId)->toBe(
                (int) $stocktake->product_category_id,
                "Stocktake {$stocktake->stocktake_number} category must match the counted products",
            );
        }
    }
});

// PR #432 review butir 2 — timestamp master/konfigurasi sebelum transaksi pertama.
invariant('PR432 approval flows are created before the first approval request', function () {
    $firstRequest = DB::table('approval_requests')->min('submitted_at');

    expect($firstRequest)->not->toBeNull('Demo data must include approval requests');

    $flowsAfter = DB::table('approval_flows')
        ->where('created_at', '>', $firstRequest)
        ->count();

    expect($flowsAfter)->toBe(0, 'Approval flow created_at must not be later than the first approval request');
});

// PR #432 review butir 6 — alokasi pembayaran AR/AP hanya untuk pihak dokumen yang sama.
invariant('PR432 payment allocations belong to the same party as the document', function () {
    $arMismatch = DB::table('ar_receipt_allocations')
        ->join('ar_receipts', 'ar_receipts.id', '=', 'ar_receipt_allocations.ar_receipt_id')
        ->join('customer_invoices', 'customer_invoices.id', '=', 'ar_receipt_allocations.customer_invoice_id')
        ->whereColumn('ar_receipts.customer_id', '!=', 'customer_invoices.customer_id')
        ->count();

    $apMismatch = DB::table('ap_payment_allocations')
        ->join('ap_payments', 'ap_payments.id', '=', 'ap_payment_allocations.ap_payment_id')
        ->join('supplier_bills', 'supplier_bills.id', '=', 'ap_payment_allocations.supplier_bill_id')
        ->whereColumn('ap_payments.supplier_id', '!=', 'supplier_bills.supplier_id')
        ->count();

    expect($arMismatch)->toBe(0, 'AR receipt allocations must match the invoice customer');
    expect($apMismatch)->toBe(0, 'AP payment allocations must match the bill supplier');
});

// PR #432 review butir 6 — produk Service tidak punya pergerakan stok.
invariant('PR432 service products have no stock movements', function () {
    $serviceMovements = DB::table('stock_movements')
        ->join('products', 'products.id', '=', 'stock_movements.product_id')
        ->where('products.type', 'service')
        ->count();

    expect($serviceMovements)->toBe(0, 'Service products must not carry stock movements');
});

// PR #432 review butir 6 — kuantitas GR yang diterima (accepted) sama dengan stok masuk.
invariant('PR432 goods receipt accepted quantity equals stock movement quantity', function () {
    $receipts = DB::table('goods_receipt_items')
        ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
        ->where('goods_receipts.status', 'confirmed')
        ->get([
            'goods_receipt_items.goods_receipt_id',
            'goods_receipt_items.product_id',
            'goods_receipt_items.quantity_received',
            'goods_receipt_items.quantity_accepted',
        ]);

    expect($receipts)->not->toBeEmpty('Demo data must include confirmed goods receipts');

    foreach ($receipts as $item) {
        $movementIn = (float) DB::table('stock_movements')
            ->where('reference_type', GoodsReceipt::class)
            ->where('reference_id', $item->goods_receipt_id)
            ->where('product_id', $item->product_id)
            ->sum('quantity_in');

        expect(round($movementIn, 2))->toBe(
            round((float) $item->quantity_accepted, 2),
            "GR #{$item->goods_receipt_id} product {$item->product_id} stock in must equal accepted quantity",
        );
        expect((float) $item->quantity_accepted)->toBeLessThanOrEqual((float) $item->quantity_received);
    }
});

// W7-17 (aset) — nama aset demo bukan label placeholder.
invariant('W7-17 asset demo names avoid placeholder labels', function () {
    $placeholders = DB::table('assets')
        ->whereIn('name', ['Laptop Admin', 'Laptop Lama'])
        ->pluck('name')
        ->all();

    expect($placeholders)->toBeEmpty('Asset demo names must not be placeholder labels');
});

// W7-17 (aset) — kode lokasi aset (OFFICE, IT-ROOM, WAREHOUSE) tidak berulang lintas cabang.
invariant('W7-17 asset location codes are not repeated across branches', function () {
    $repeated = DB::table('asset_locations')
        ->select('code', DB::raw('COUNT(*) as total'))
        ->groupBy('code')
        ->having('total', '>', 1)
        ->pluck('code')
        ->all();

    expect($repeated)->toBeEmpty('Asset location codes must not be repeated across branches');
});

// #437 keputusan 2 — posting ke akun kelompok (punya anak) ditolak; data demo hanya memakai akun daun.
invariant('W7-06 no journal line posts to a group account', function () {
    $groupIds = Account::query()
        ->whereIn('id', Account::query()->whereNotNull('parent_id')->select('parent_id'))
        ->pluck('id');

    expect($groupIds)->not->toBeEmpty('COA must contain group accounts');

    $offenders = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->whereIn('journal_entry_lines.account_id', $groupIds)
        ->get(['journal_entries.entry_number', 'journal_entries.description', 'journal_entry_lines.account_id']);

    $details = $offenders
        ->map(fn ($row) => "{$row->entry_number} ({$row->description}) acct {$row->account_id}")
        ->implode('; ');

    expect($offenders->all())->toBeEmpty('Journal lines must not post to group accounts: ' . $details);
});

// Issue #357 (PR #384): every invariant above must also hold after a second `db:seed`.
// One test seeds twice and runs the registered read-only checks against that state.
// It seeds once more on top of the shared run seed, so the extra cost is one seed run.
test('all demo invariants also hold after a second db:seed', function () {
    seedDemoData();

    foreach (demoInvariantChecks() as $name => $check) {
        $check();
    }
})->group('demo-integrity');

// W7-16 — rantai transisi pipeline lengkap dari state awal, dengan timestamp mengikuti tanggal dokumen.
invariant('W7-16 pipeline transition chains start at the initial state and link end to end', function () {
    $entities = DB::table('pipeline_entity_states')->get(['id', 'entity_type', 'entity_id', 'current_state_id']);

    expect($entities)->not->toBeEmpty('Demo data must include pipeline entity states');

    foreach ($entities as $entity) {
        $logs = DB::table('pipeline_state_logs')
            ->where('pipeline_entity_state_id', $entity->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'from_state_id', 'to_state_id', 'created_at']);

        $label = "{$entity->entity_type}#{$entity->entity_id}";

        expect($logs)->not->toBeEmpty("{$label} must have a transition history");
        expect($logs->first()->from_state_id)->toBeNull("{$label} chain must start from the initial state");

        foreach ($logs->values() as $index => $log) {
            expect($log->created_at)->not->toBeNull("{$label} log {$log->id} created_at must not be null");

            if ($index === 0) {
                continue;
            }

            $previous = $logs[$index - 1];

            expect((int) $log->from_state_id)
                ->toBe((int) $previous->to_state_id, "{$label} log {$log->id} must continue from the previous state");
        }

        expect((int) $logs->last()->to_state_id)
            ->toBe((int) $entity->current_state_id, "{$label} chain must end at the current state");
    }
});

// W7-16 — timestamp transisi mengikuti tanggal dokumen dan tidak lebih awal dari master.
invariant('W7-16 pipeline transitions follow the document date', function () {
    $purchaseRequests = DB::table('purchase_requests')->pluck('request_date', 'id');
    $purchaseOrders = DB::table('purchase_orders')->pluck('order_date', 'id');

    $documentDates = [
        'App\\Models\\PurchaseRequest' => $purchaseRequests,
        'App\\Models\\PurchaseOrder' => $purchaseOrders,
    ];

    $checked = 0;

    $entityStates = DB::table('pipeline_entity_states')
        ->whereIn('entity_type', array_keys($documentDates))
        ->get();

    foreach ($entityStates as $entity) {
        $documentDate = $documentDates[$entity->entity_type]->get($entity->entity_id);
        $label = "{$entity->entity_type}#{$entity->entity_id}";

        expect($documentDate)->not->toBeNull("{$label} must reference an existing document");

        $first = DB::table('pipeline_state_logs')
            ->where('pipeline_entity_state_id', $entity->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->value('created_at');

        expect($first)->not->toBeNull("{$label} first transition must have created_at");

        // Transisi pertama harus terjadi dalam beberapa hari setelah tanggal dokumen,
        // bukan di hari seeder dijalankan (now()) dan tidak sebelum dokumen.
        $gapDays = Carbon::parse($documentDate)->startOfDay()->diffInDays(Carbon::parse($first), false);
        $dateNote = "document date {$documentDate}";

        expect($gapDays)
            ->toBeGreaterThanOrEqual(0, "{$label} first transition must not be before the {$dateNote}")
            ->toBeLessThanOrEqual(7, "{$label} first transition must follow the {$dateNote} within a week");

        $checked++;
    }

    expect($checked)->toBeGreaterThan(0, 'Demo data must include purchasing pipeline entities');
});

// W7-16 — pelaku transisi berbeda dari pengaju/pemohon, dan approval tidak dilakukan pengaju sendiri.
invariant('W7-16 approval actors differ from the requester', function () {
    $requests = DB::table('approval_requests')->get(['id', 'submitted_by', 'status', 'submitted_at', 'completed_at']);

    expect($requests)->not->toBeEmpty('Demo data must include approval requests');

    foreach ($requests as $request) {
        expect($request->submitted_at)->not->toBeNull("Approval request {$request->id} must have submitted_at");

        $steps = DB::table('approval_request_steps')
            ->where('approval_request_id', $request->id)
            ->whereNotNull('acted_by')
            ->get(['acted_by']);

        foreach ($steps as $step) {
            $message = "Approval request {$request->id} must not be acted on by its requester";

            expect((int) $step->acted_by)->not->toBe((int) $request->submitted_by, $message);
        }
    }

    $auditCreated = DB::table('approval_audit_logs')->whereNull('created_at')->count();
    expect($auditCreated)->toBe(0, 'Approval audit logs must have created_at');
});

// W7-16 — pelaku transisi pipeline pada dokumen pembelian berbeda dari pengajunya.
invariant('W7-16 purchasing pipeline transitions are performed by someone other than the requester', function () {
    // requested_by menyimpan employee id; pelaku transisi menyimpan user id. Petakan lewat user_id.
    $requestedBy = DB::table('purchase_requests')
        ->join('employees', 'employees.id', '=', 'purchase_requests.requested_by')
        ->pluck('employees.user_id', 'purchase_requests.id');

    foreach (DB::table('pipeline_entity_states')->where('entity_type', 'App\\Models\\PurchaseRequest')->get(['id', 'entity_id']) as $entity) {
        $performers = DB::table('pipeline_state_logs')
            ->where('pipeline_entity_state_id', $entity->id)
            ->whereNotNull('performed_by')
            ->pluck('performed_by');

        foreach ($performers as $performer) {
            expect((int) $performer)
                ->not->toBe((int) $requestedBy->get($entity->entity_id), "Purchase request {$entity->entity_id} transition must not be performed by its requester");
        }
    }
});

// W7-17 (Butir 3 Review visual akhir PR #432) — pelaku transisi demo bervariasi
// (bukan satu orang untuk semua dokumen), dan PO yang lolos approval melewati Pending Approval.
invariant('W7-17 review visual akhir butir 3: demo pipeline actors vary across documents', function () {
    $distinctActors = DB::table('pipeline_state_logs')
        ->whereNotNull('performed_by')
        ->distinct()
        ->count('performed_by');

    expect($distinctActors)->toBeGreaterThanOrEqual(3, 'Pelaku transisi demo harus lebih dari satu orang');
});

// W7-16 — riwayat transisi dan approval tidak berisi created_at kosong.
invariant('W7-16 pipeline and approval history rows have timestamps', function () {
    expect(DB::table('pipeline_state_logs')->whereNull('created_at')->count())->toBe(0);
    expect(DB::table('approval_request_steps')->where('status', '!=', 'pending')->whereNull('acted_at')->count())->toBe(0);
});

// W7-17 — nomor dokumen demo memakai sequence -000001 dengan jumlah digit seragam (tanpa rentang sintetis).
invariant('W7-17 demo document numbers start at -000001 with uniform digit width', function () {
    $prefixes = [
        'stock_adjustments' => ['adjustment_number', 'SA'],
        'stock_transfers' => ['transfer_number', 'ST'],
        'inventory_stocktakes' => ['stocktake_number', 'STO'],
    ];

    foreach ($prefixes as $table => [$column, $prefix]) {
        $numbers = DB::table($table)->pluck($column);

        foreach ($numbers as $number) {
            expect($number)->toMatch(
                "/^{$prefix}-\\d{4}-\\d{6}$/",
                "Nomor {$table} {$number} harus memakai 6 digit"
            );
            expect((int) substr($number, -6))
                ->toBeLessThan(900000, "Nomor {$table} {$number} tidak boleh memakai rentang sintetis");
        }

        if ($numbers->isNotEmpty()) {
            expect($numbers->map(fn ($n) => (int) substr($n, -6))->min())
                ->toBeLessThanOrEqual(1, "Sequence {$table} harus dimulai dari -000001");
        }
    }

    $journalNumbers = DB::table('journal_entries')->pluck('entry_number');

    foreach ($journalNumbers as $number) {
        expect($number)->toMatch('/^JV-\d{4}-\d{5}$/', "Nomor jurnal {$number} harus memakai format JV-YYYY-NNNNN");
    }
});

// W7-17 — kode akun demo berskema 5 digit (1999-IBC dikecualikan hanya bila masih dirujuk sistem, lihat catatan upgrade).
invariant('W7-17 demo account codes follow the five-digit scheme', function () {
    // 1999-IBC dirujuk langsung oleh InterBranchClearingService::CLEARING_CODE dan
    // migrasi 2026_06_21. Mengubah kodenya butuh migrasi data + catatan upgrade,
    // jadi di luar cakupan langkah ini (dilaporkan, tidak diubah).
    $codes = DB::table('accounts')->where('code', '!=', '1999-IBC')->pluck('code')->unique();

    foreach ($codes as $code) {
        expect($code)->toMatch('/^\d{5}$/', "Kode akun {$code} harus 5 digit");
    }
});

// #503 butir 1 — tidak ada dua produk aktif dengan nama sama (opname demo memakai katalog).
invariant('#503 no two active demo products share the same name', function () {
    $duplicates = DB::table('products')
        ->where('status', 'active')
        ->select('name')
        ->groupBy('name')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('name');

    expect($duplicates)->toBeEmpty('Produk aktif tidak boleh ganda nama: ' . $duplicates->implode(', '));
});

// #503 butir 2 — nama akun default seed tidak campur bahasa (daftar eksplisit).
invariant('#503 default seeded account names are not mixed-language', function () {
    $expected = [
        '11300' => 'Inventory',
        '11310' => 'Inventory in Transit',
    ];

    foreach ($expected as $code => $name) {
        expect(DB::table('accounts')->where('code', $code)->value('name'))
            ->toBe($name, "Nama akun {$code} harus \"{$name}\"");
    }

    expect(DB::table('accounts')->where('name', 'Persediaan dalam Perjalanan')->exists())->toBeFalse();
});

// #503 butir 3 — nomor JV demo naik sesuai tanggal jurnal (urut kronologis per tahun fiskal).
invariant('#503 demo journal numbers ascend with entry date per fiscal year', function () {
    $entries = DB::table('journal_entries')
        ->orderBy('fiscal_year_id')
        ->orderBy('entry_date')
        ->orderBy('entry_number')
        ->get(['fiscal_year_id', 'entry_number', 'entry_date']);

    $previousByYear = [];
    foreach ($entries as $entry) {
        $sequence = (int) substr($entry->entry_number, -5);
        $previous = $previousByYear[$entry->fiscal_year_id] ?? null;

        if ($previous !== null) {
            expect($sequence)->toBeGreaterThanOrEqual(
                $previous,
                "Nomor {$entry->entry_number} tanggal {$entry->entry_date} lebih awal dari nomor sebelumnya",
            );
        }

        $previousByYear[$entry->fiscal_year_id] = $sequence;
    }
});

// W7-17 — kode lokasi aset unik lintas cabang.
invariant('W7-17 asset location codes are unique across branches', function () {
    $duplicates = DB::table('asset_locations')
        ->select('code')
        ->groupBy('code')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('code');

    expect($duplicates)->toBeEmpty('Kode lokasi aset tidak boleh berulang: ' . $duplicates->implode(', '));
});

// W7-17 — produk demo tidak memakai kode placeholder SaaS-CLOUD-001.
invariant('W7-17 demo product codes avoid placeholder SaaS-CLOUD-001', function () {
    expect(DB::table('products')->where('code', 'SaaS-CLOUD-001')->exists())->toBeFalse();
});

// W7-17 — email dan telepon demo memakai domain netral dan nomor wajar.
invariant('W7-17 demo contact emails use neutral example domains and realistic phones', function () {
    $emails = DB::table('suppliers')->whereNotNull('email')->pluck('email');

    foreach ($emails as $email) {
        expect($email)->not->toContain('vendor.example', "Email supplier {$email} harus wajar");
        expect($email)->toMatch('/@[a-z0-9.-]+\.example$/', "Email {$email} harus memakai domain example");
    }

    expect(DB::table('suppliers')->where('phone', '0215551234')->exists())->toBeFalse();
    expect(DB::table('settings')->where('value', '+62 21 0000 0000')->exists())->toBeFalse();
});

// W7-17 (bahasa, cabang & gudang) — nama cabang dan gudang demo berbahasa Indonesia,
// tanpa sisa nama Inggris dan tanpa ganda akibat rename.
invariant('W7-17 demo branches and warehouses use Indonesian names without English leftovers', function () {
    $legacyBranches = ['Head Office', 'Branch 1', 'Branch 2', 'Branch 3'];
    $legacyWarehouses = ['Main Warehouse', 'Transit Warehouse', 'Return Warehouse', 'Production Warehouse'];

    $legacyBranchCount = DB::table('branches')->whereIn('name', $legacyBranches)->count();
    $legacyWarehouseCount = DB::table('warehouses')->whereIn('name', $legacyWarehouses)->count();
    $mainWarehouseName = DB::table('warehouses')
        ->where('code', DemoMasterData::WAREHOUSE_MAIN_CODE)
        ->value('name');

    expect($legacyBranchCount)->toBe(0, 'cabang demo masih memakai nama Inggris');
    expect($legacyWarehouseCount)->toBe(0, 'gudang demo masih memakai nama Inggris');
    expect(DB::table('branches')->where('name', DemoMasterData::BRANCH_HEAD_OFFICE)->count())->toBe(1);
    expect($mainWarehouseName)->toBe(DemoMasterData::WAREHOUSE_MAIN_NAME);
});

// W7-17 — setiap supplier menjual produk pada kategori yang cocok dengan kategori supplier.
invariant('W7-17 supplier and product categories are mapped consistently', function () {
    $mismatch = DB::table('purchase_order_items')
        ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
        ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
        ->join('supplier_categories', 'supplier_categories.id', '=', 'suppliers.category_id')
        ->join('products', 'products.id', '=', 'purchase_order_items.product_id')
        ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
        ->where('supplier_categories.name', 'Furnitur')
        ->where('products.code', 'SaaS-CLOUD-001')
        ->count();

    expect($mismatch)->toBe(0, 'Supplier Furniture tidak boleh menjual produk SaaS');
});

// W7-17 — transfer antar gudang bervariasi (tidak semua menuju Gudang Retur).
invariant('W7-17 stock transfers vary their destination warehouse', function () {
    $destinations = DB::table('stock_transfers')->distinct()->pluck('to_warehouse_id');

    expect($destinations->count())->toBeGreaterThanOrEqual(2, 'Transfer harus menuju lebih dari satu gudang');
});

// W7-17 — customer demo bervariasi cabang, kategori, dan status.
invariant('W7-17 customer demo varies branch, category and status', function () {
    expect(DB::table('customers')->distinct()->count('branch_id'))->toBeGreaterThanOrEqual(2);
    expect(DB::table('customers')->distinct()->count('category_id'))->toBeGreaterThanOrEqual(2);
    expect(DB::table('customers')->distinct()->count('status'))->toBeGreaterThanOrEqual(2);
});

// W7-17 (Butir 2 Review visual akhir PR #432) — master (cabang, kategori, approval flow) dibuat
// sebelum transaksi pertama, sehingga tanggal dibuat tidak lebih baru dari transaksi demo.
invariant('W7-17 review visual akhir butir 2: demo master created_at precedes first transaction', function () {
    $firstTransaction = DB::table('journal_entries')->min('created_at');
    expect($firstTransaction)->not->toBeNull();

    foreach (['branches', 'product_categories', 'approval_flows'] as $table) {
        $late = DB::table($table)->where('created_at', '>', $firstTransaction)->count();
        expect($late)->toBe(0, "{$table}.created_at tidak boleh lebih baru dari transaksi pertama");
    }
});

// W7-17 — tanggal dibuat (created_at) tidak kosong pada master kategori dan satuan.
invariant('W7-17 product categories and units have created_at', function () {
    expect(DB::table('product_categories')->whereNull('created_at')->count())->toBe(0);
    expect(DB::table('units')->whereNull('created_at')->count())->toBe(0);
});

// W7-17 (bahasa, master) — master demo kategori, departemen, jabatan, satuan, dan lokasi
// memakai Bahasa Indonesia; nama Inggris lama tidak boleh tersisa.
invariant('W7-17 demo master categories, departments, positions, units and locations are Indonesian', function () {
    $legacy = [
        'product_categories' => ['Electronics', 'Office Supplies', 'Furniture', 'Consulting Services', 'Software & SaaS'],
        'supplier_categories' => ['Electronics', 'Office Supplies', 'Furniture', 'IT Services', 'Logistics'],
        'customer_categories' => ['Retail', 'Wholesale', 'Government', 'Corporate', 'Walk-in'],
        'departments' => ['HR', 'Engineering', 'Sales', 'Marketing', 'Finance', 'Operations', 'Legal'],
        'positions' => ['Staff', 'Driver', 'Senior Developer', 'Manager', 'Director', 'Lead', 'VP', 'C-level'],
        'units' => ['Piece', 'Box', 'Hour', 'Day', 'Week', 'Month', 'Year', 'Set', 'Pair', 'Sheet', 'Service'],
        'asset_locations' => ['Office', 'IT Room', 'Warehouse'],
    ];

    foreach ($legacy as $table => $names) {
        expect(DB::table($table)->whereIn('name', $names)->count())
            ->toBe(0, "master demo {$table} masih memakai nama Inggris");
    }
});

// W7-17 (kode) — kode lokasi aset demo unik lintas cabang dan tidak memakai kode lama tanpa akhiran.
invariant('W7-17 demo asset location codes are unique and suffixed', function () {
    $legacyCodes = DB::table('asset_locations')
        ->whereIn('code', ['OFFICE', 'IT-ROOM', 'WAREHOUSE'])
        ->count();
    $duplicates = DB::table('asset_locations')
        ->select('code')
        ->groupBy('code')
        ->havingRaw('COUNT(*) > 1')
        ->count();

    expect($legacyCodes)->toBe(0, 'kode lokasi demo lama tanpa akhiran cabang masih ada');
    expect($duplicates)->toBe(0, 'kode lokasi aset tidak unik');
});

// W7-17 (i18n) — teks jurnal yang dibuat aplikasi lewat i18n, bukan hardcode Inggris.
// Kunci harus ada di lang/en.json dan lang/id.json agar mengikuti bahasa UI.
invariant('W7-17 depreciation journal text is generated through i18n in en and id', function () {
    $source = file_get_contents(base_path('app/Actions/AssetDepreciationRuns/PostDepreciationToJournalAction.php'));

    expect($source)
        ->not->toContain("'Depreciation Run for '")
        ->not->toContain("'Accumulated Depreciation'")
        ->not->toContain("'Depreciation Expense'")
        ->toContain("__('asset_depreciation_run.");

    foreach (['en', 'id'] as $locale) {
        $strings = json_decode(file_get_contents(lang_path("{$locale}.json")), true, 512, JSON_THROW_ON_ERROR);

        foreach (['asset_depreciation_run.journal_description', 'asset_depreciation_run.memo_expense', 'asset_depreciation_run.memo_accumulated'] as $key) {
            expect(array_key_exists($key, $strings))->toBeTrue("lang/{$locale}.json must define {$key}");
        }
    }
});

// W7-17 (6c) — label demo yang terlihat pengguna berbahasa Indonesia: alasan delegasi,
// alur approval, nama produk, dan nama pipeline tidak boleh memakai frasa Inggris lama.
invariant('W7-17 demo label text is Indonesian (delegation reasons, approval flows, products, pipelines)', function () {
    $legacy = [
        'approval_delegations' => ['reason', ['Annual Leave', 'Overseas Duty']],
        'approval_flows' => ['name', [
            'High Value Asset Registration', 'HR Manager Review', 'Finance Director Approval',
            'Purchase Request Default Approval', 'Admin Review', 'Purchase Order Default Approval',
            'Admin Approval', 'Vehicle Loan Default Approval', 'Vehicle Loan Approval',
        ]],
        'products' => ['name', ['Executive Office Desk', 'Ergonomic Office Chair']],
        'pipelines' => ['name', [
            'Supplier Bill Lifecycle', 'Customer Invoice Lifecycle', 'Asset Lifecycle',
            'Purchase Request Lifecycle', 'Purchase Order Lifecycle',
        ]],
    ];

    foreach ($legacy as $table => [$column, $names]) {
        expect(DB::table($table)->whereIn($column, $names)->count())
            ->toBe(0, "label demo {$table}.{$column} masih berbahasa Inggris");
    }
});

// W7-17 (6c, sisa) — deskripsi/catatan jurnal, recurring, maintenance, movement, dan
// alur pipeline demo tidak boleh memakai frasa Inggris lama.
invariant('W7-17 demo journal, recurring, maintenance and pipeline text is Indonesian', function () {
    $legacy = [
        'journal_entries' => ['description', ['Credit note item']],
        'asset_movements' => ['notes', ['Disposal initiated via seeder']],
        'products' => ['description', [
            'Medium Density Fiberboard for furniture manufacturing',
            'Quality wood screws for furniture assembly',
        ]],
        'pipelines' => ['name', ['AP Payment Lifecycle']],
    ];

    foreach ($legacy as $table => [$column, $names]) {
        expect(DB::table($table)->whereIn($column, $names)->count())
            ->toBe(0, "label demo {$table}.{$column} masih berbahasa Inggris");
    }
});

// W7-17 — nama aset demo bahasa Indonesia; kategori aset tidak berbahasa campur.
invariant('W7-17 demo master names are Indonesian, not mixed with English labels', function () {
    $englishLabels = ['Mesin Production', 'Vehicles', 'Office Equipment', 'Operating Budget'];

    expect(DB::table('asset_categories')->whereIn('name', $englishLabels)->count())->toBe(0);
});

// W7-17 (Butir 1 Review visual akhir PR #432) — produk jual adalah barang dagang dengan harga
// wajar; produk barang fisik stocktake tidak berada di kategori yang tidak punya produk.
invariant('W7-17 review visual akhir butir 1: demo goods are sellable with a positive price', function () {
    // #503 butir 1: barang stok demo = produk katalog (bukan INV-2026-* duplikat).
    $codes = DemoMasterData::STOCK_DEMO_PRODUCT_CODES;

    $products = DB::table('products')->whereIn('code', $codes)->get();
    expect($products)->toHaveCount(count($codes));

    foreach ($products as $product) {
        expect($product->type)->toBe('finished_good', "{$product->code} harus barang dagang");
        expect((float) $product->selling_price)->toBeGreaterThan(0, "{$product->code} harga jual 0");
        expect((float) $product->selling_price)->toBeGreaterThanOrEqual((float) $product->cost);
    }
});

invariant('W7-17 review visual akhir butir 1: stocktake category holds the counted products', function () {
    $stocktakeCategoryIds = DB::table('inventory_stocktake_items')
        ->join('products', 'products.id', '=', 'inventory_stocktake_items.product_id')
        ->distinct()
        ->pluck('products.product_category_id');

    foreach ($stocktakeCategoryIds as $categoryId) {
        expect(DB::table('products')->where('product_category_id', $categoryId)->exists())
            ->toBeTrue("kategori stocktake {$categoryId} tidak punya produk");
    }
});

// W7-17 (Butir 4 Review visual PR #432) — nama produk, aset, dan stok demo berbahasa Indonesia.
invariant('W7-17 review visual butir 4: demo product, asset and stock names are Indonesian', function () {
    $legacy = [
        'products' => ['name', [
            'MDF Wood Panel 120x240cm', 'Wooden Table Legs (Set of 4)', 'Wood Screws 4x40mm (Box of 100)',
        ]],
        'assets' => ['name', ['High-End Server Rack', 'Filing Cabinet', 'Printer Office Lt. 2']],
        'asset_models' => ['model_name', ['Filing Cabinet 4 Drawer']],
    ];

    foreach ($legacy as $table => [$column, $names]) {
        expect(DB::table($table)->whereIn($column, $names)->count())
            ->toBe(0, "nama demo {$table}.{$column} masih berbahasa Inggris");
    }
});

// PR432 — user demo (lewat email karyawan) harus tetap punya izin penuh, baik pada seed segar
// maupun seed di atas data demo lama (email karyawan `main` masih `manager.hr@` dst.).
test('PR432 demo users keep their permissions after seeding over legacy employee emails', function () {
    seedDemoData();

    $legacyEmails = [
        'hendra.wijaya@example.com' => 'manager.hr@example.com',
        'dewi.anggraini@example.com' => 'director.finance@example.com',
        'agus.prasetyo@example.com' => 'staff.it@example.com',
    ];

    foreach ($legacyEmails as $newEmail => $userEmail) {
        DB::table('employees')->where('email', $newEmail)->update(['email' => $userEmail]);
    }

    seedDemoData();

    $permissionCount = DB::table('permissions')->count();
    expect($permissionCount)->toBeGreaterThan(0);

    foreach (array_merge(array_values($legacyEmails), ['rina.kartika@example.com', config('app.admin')]) as $email) {
        $userId = DB::table('users')->where('email', $email)->value('id');
        $employee = $userId
            ? DB::table('employees')->where('user_id', $userId)->first()
            : null;
        expect($employee)->not->toBeNull("Karyawan demo untuk user {$email} harus ada");

        $granted = DB::table('employee_permission')->where('employee_id', $employee->id)->count();
        expect($granted)->toBe($permissionCount, "Karyawan demo {$email} harus punya seluruh izin");
    }
});

// #459 butir 1 — qty sistem stok opname = saldo kartu stok produk+gudang pada tanggal opname.
invariant('W8-459 stocktake system quantity equals the stock card balance on the stocktake date', function () {
    $items = DB::table('inventory_stocktake_items')
        ->join(
            'inventory_stocktakes',
            'inventory_stocktakes.id',
            '=',
            'inventory_stocktake_items.inventory_stocktake_id',
        )
        ->get([
            'inventory_stocktakes.stocktake_number',
            'inventory_stocktakes.warehouse_id',
            'inventory_stocktakes.stocktake_date',
            'inventory_stocktake_items.product_id',
            'inventory_stocktake_items.system_quantity',
            'inventory_stocktake_items.counted_quantity',
            'inventory_stocktake_items.variance',
        ]);

    expect($items)->not->toBeEmpty('Demo data must include stocktake lines');

    foreach ($items as $item) {
        $label = "Stocktake {$item->stocktake_number} product {$item->product_id}";
        $expected = round(stockCardBalanceOn(
            (int) $item->product_id,
            (int) $item->warehouse_id,
            (string) $item->stocktake_date,
        ), 2);

        expect(round((float) $item->system_quantity, 2))
            ->toBe($expected, "{$label} system quantity must equal the stock card balance");

        if ($item->counted_quantity !== null) {
            $countedVariance = (float) $item->counted_quantity - (float) $item->system_quantity;

            expect(round((float) $item->variance, 2))
                ->toBe(round($countedVariance, 2), "{$label} variance must equal counted minus system");
        }
    }
});

// #459 butir 2 — stock opname completed yang berselisih punya draft SA stocktake_result terhubung.
invariant('W8-459 completed stocktakes with variance have a linked stocktake_result adjustment', function () {
    $completed = DB::table('inventory_stocktakes')->where('status', 'completed')->get(['id', 'stocktake_number']);

    expect($completed)->not->toBeEmpty('Demo data must include a completed stocktake');

    foreach ($completed as $stocktake) {
        $hasVariance = DB::table('inventory_stocktake_items')
            ->where('inventory_stocktake_id', $stocktake->id)
            ->whereNotNull('variance')
            ->where('variance', '!=', 0)
            ->exists();

        if (! $hasVariance) {
            continue;
        }

        $adjustment = DB::table('stock_adjustments')
            ->where('inventory_stocktake_id', $stocktake->id)
            ->where('adjustment_type', 'stocktake_result')
            ->first(['id', 'status']);

        expect($adjustment)->not->toBeNull(
            "Completed stocktake {$stocktake->stocktake_number} must have a stocktake_result adjustment",
        );
    }
});

// #459 butir 2 — SA stocktake_result: quantity_before sama dengan saldo kartu stok, dan
// quantity_adjusted sama dengan selisih stok opname.
invariant('W8-459 stocktake_result adjustments match the stock card and the stocktake variance', function () {
    $adjustments = DB::table('stock_adjustments')
        ->join('inventory_stocktakes', 'inventory_stocktakes.id', '=', 'stock_adjustments.inventory_stocktake_id')
        ->where('stock_adjustments.adjustment_type', 'stocktake_result')
        ->whereNotNull('stock_adjustments.inventory_stocktake_id')
        ->get([
            'stock_adjustments.id',
            'stock_adjustments.adjustment_number',
            'stock_adjustments.warehouse_id',
            'stock_adjustments.inventory_stocktake_id',
            'inventory_stocktakes.stocktake_date',
        ]);

    expect($adjustments)->not->toBeEmpty('Demo data must include a stocktake_result adjustment');

    foreach ($adjustments as $adjustment) {
        $items = DB::table('stock_adjustment_items')
            ->where('stock_adjustment_id', $adjustment->id)
            ->get(['product_id', 'quantity_before', 'quantity_adjusted']);

        foreach ($items as $item) {
            $label = "Adjustment {$adjustment->adjustment_number} product {$item->product_id}";

            $line = DB::table('inventory_stocktake_items')
                ->where('inventory_stocktake_id', $adjustment->inventory_stocktake_id)
                ->where('product_id', $item->product_id)
                ->first(['system_quantity', 'variance']);

            expect($line)->not->toBeNull("{$label} must come from a stocktake line");
            expect(round((float) $item->quantity_adjusted, 2))
                ->toBe(round((float) $line->variance, 2), "{$label} adjusted quantity must equal stocktake variance");
            expect(round((float) $item->quantity_before, 2))->toBe(
                round((float) $line->system_quantity, 2),
                "{$label} quantity before must equal stocktake system quantity",
            );

            $cardBalance = stockCardBalanceOn(
                (int) $item->product_id,
                (int) $adjustment->warehouse_id,
                (string) $adjustment->stocktake_date,
            );

            expect(round((float) $item->quantity_before, 2))->toBe(
                round($cardBalance, 2),
                "{$label} quantity before must equal the stock card balance on the stocktake date",
            );
        }
    }
});

// #459 butir 3 — transfer_in memakai quantity_received (bukan quantity kirim).
invariant('W8-459 partially received transfers post transfer_in equal to quantity received', function () {
    $partial = DB::table('stock_transfer_items')
        ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
        ->where('stock_transfers.status', 'received')
        ->whereColumn('stock_transfer_items.quantity_received', '<', 'stock_transfer_items.quantity')
        ->get([
            'stock_transfer_items.stock_transfer_id',
            'stock_transfer_items.product_id',
            'stock_transfers.transfer_number',
            'stock_transfer_items.quantity_received',
        ]);

    expect($partial)->not->toBeEmpty('Demo data must include a partially received transfer');

    foreach ($partial as $line) {
        $in = (float) DB::table('stock_movements')
            ->where('reference_type', StockTransfer::class)
            ->where('reference_id', $line->stock_transfer_id)
            ->where('product_id', $line->product_id)
            ->where('movement_type', 'transfer_in')
            ->sum('quantity_in');

        expect(round($in, 2))->toBe(
            round((float) $line->quantity_received, 2),
            "Transfer {$line->transfer_number} transfer_in must equal quantity received",
        );
    }
});

// #459 butir 4 — setiap log Pending Approval -> Confirmed pada PO punya approval request approved + audit.
invariant('W8-459 purchase orders passing Pending Approval have an approved request and audit', function () {
    $entityStates = DB::table('pipeline_entity_states')
        ->where('entity_type', PurchaseOrder::class)
        ->get(['id', 'entity_id']);

    $passed = 0;

    foreach ($entityStates as $entityState) {
        $transitions = DB::table('pipeline_state_logs')
            ->join('pipeline_states as to_state', 'to_state.id', '=', 'pipeline_state_logs.to_state_id')
            ->join('pipeline_states as from_state', 'from_state.id', '=', 'pipeline_state_logs.from_state_id')
            ->where('pipeline_state_logs.pipeline_entity_state_id', $entityState->id)
            ->where('from_state.name', 'Pending Approval')
            ->where('to_state.name', 'Confirmed')
            ->count();

        if ($transitions === 0) {
            continue;
        }

        $passed++;

        $request = DB::table('approval_requests')
            ->where('approvable_type', PurchaseOrder::class)
            ->where('approvable_id', $entityState->entity_id)
            ->first(['id', 'status']);

        expect($request)->not->toBeNull(
            "Purchase order {$entityState->entity_id} passed approval without an approval request",
        );
        expect($request->status)->toBe('approved', "Purchase order {$entityState->entity_id} request must be approved");
        expect(
            DB::table('approval_audit_logs')->where('approval_request_id', $request->id)->count()
        )->toBeGreaterThan(0, "Purchase order {$entityState->entity_id} approval must have audit rows");
    }

    expect($passed)->toBeGreaterThan(0, 'Demo data must include a purchase order passing Pending Approval');
});

// #459 butir 5 — pengaju dokumen (submitted_by) bukan pelaku transisi lain pada dokumen yang sama.
invariant('W8-459 purchase order requester never performs its own pipeline transitions', function () {
    $requests = DB::table('approval_requests')
        ->where('approvable_type', PurchaseOrder::class)
        ->get(['approvable_id', 'submitted_by']);

    expect($requests)->not->toBeEmpty('Demo data must include purchase order approval requests');

    foreach ($requests as $request) {
        $performers = DB::table('pipeline_state_logs')
            ->join(
                'pipeline_entity_states',
                'pipeline_entity_states.id',
                '=',
                'pipeline_state_logs.pipeline_entity_state_id'
            )
            ->where('pipeline_entity_states.entity_type', PurchaseOrder::class)
            ->where('pipeline_entity_states.entity_id', $request->approvable_id)
            ->whereNotNull('pipeline_state_logs.performed_by')
            ->pluck('pipeline_state_logs.performed_by');

        foreach ($performers as $performer) {
            expect((int) $performer)->not->toBe(
                (int) $request->submitted_by,
                "Purchase order {$request->approvable_id} requester must not perform its own transitions",
            );
        }
    }
});

// #494 butir 1 — JE posted tidak boleh dicatat setelah periode tempatnya ditutup.
invariant('W9-494 posted journal entries are not posted after their period was closed', function () {
    $violations = DB::table('journal_entries')
        ->join('period_closings', function ($join): void {
            $join->on('period_closings.fiscal_year_id', '=', 'journal_entries.fiscal_year_id')
                ->whereColumn(
                    DB::raw('period_closings.period_year'),
                    '=',
                    DB::raw('YEAR(journal_entries.entry_date)'),
                )
                ->whereColumn(
                    DB::raw('period_closings.period_month'),
                    '=',
                    DB::raw('MONTH(journal_entries.entry_date)'),
                );
        })
        ->where('period_closings.closing_type', 'monthly')
        ->where('period_closings.status', 'closed')
        ->where('journal_entries.status', 'posted')
        ->whereColumn('journal_entries.posted_at', '>', 'period_closings.closed_at')
        ->get(['journal_entries.entry_number', 'period_closings.closed_at']);

    expect($violations)->toBeEmpty(
        'Posted journal entries must not be posted after their period closed: '
        . $violations->pluck('entry_number')->implode(', '),
    );
});

// #494 butir 2 — JE transfer demo sama dengan rumus aplikasi: (qty - qty_received) x unit_cost.
invariant('W9-494 demo transfer journals equal the shortage formula (quantity - quantity_received) x unit_cost', function () {
    $transfers = StockTransfer::query()
        ->whereNotNull('journal_entry_id')
        ->with('items')
        ->get();

    expect($transfers)->not->toBeEmpty('Demo data must post at least one transfer journal');

    foreach ($transfers as $transfer) {
        $expected = 0.0;

        foreach ($transfer->items as $item) {
            $missing = max(0.0, (float) $item->quantity - (float) $item->quantity_received);
            $expected += $missing * (float) $item->unit_cost;
        }

        $debit = (float) DB::table('journal_entry_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entry_lines.journal_entry_id', $transfer->journal_entry_id)
            ->where('accounts.code', '51000')
            ->sum('journal_entry_lines.debit');

        expect(round($debit, 2))->toBe(
            round($expected, 2),
            "Transfer {$transfer->transfer_number} journal debit must equal its shortage formula",
        );
    }
});

// #459 butir 6 — submitted_at aset tidak lebih awal dari log Draft (Initial -> Draft) pada tanggal dokumen.
invariant('W8-459 asset approval submitted_at is not earlier than its Draft pipeline log', function () {
    $requests = DB::table('approval_requests')
        ->where('approvable_type', 'App\\Models\\Asset')
        ->get(['approvable_id', 'submitted_at']);

    expect($requests)->not->toBeEmpty('Demo data must include asset approval requests');

    foreach ($requests as $request) {
        $draftAt = DB::table('pipeline_state_logs')
            ->join(
                'pipeline_entity_states',
                'pipeline_entity_states.id',
                '=',
                'pipeline_state_logs.pipeline_entity_state_id'
            )
            ->join('pipeline_states as to_state', 'to_state.id', '=', 'pipeline_state_logs.to_state_id')
            ->where('pipeline_entity_states.entity_type', 'App\\Models\\Asset')
            ->where('pipeline_entity_states.entity_id', $request->approvable_id)
            ->where('to_state.name', 'Draft')
            ->value('pipeline_state_logs.created_at');

        if ($draftAt === null) {
            continue;
        }

        expect(Carbon::parse($request->submitted_at)->gte(Carbon::parse($draftAt)))->toBeTrue(
            "Asset {$request->approvable_id} submitted_at must not precede its Draft log",
        );
    }
});

// #571 review host C7 — saldo kas bank demo tidak boleh negatif di akhir bulan mana pun
// dalam jendela 13 bulan. Saldo awal FY berjalan berasal dari jurnal OPENING-FY (pemetaan
// akun COA lama ke COA aktif) dan modal awal harus cukup menutup perolehan aset tunai.
invariant('W7-18 demo bank cash balance is non-negative at every month end of the window', function () {
    foreach (DemoCalendar::months() as $month) {
        $monthEnd = $month->copy()->endOfMonth()->toDateString();
        $balance = bankCashBalanceOn($monthEnd);

        expect($balance)->toBeGreaterThanOrEqual(
            0.0,
            "Saldo kas bank negatif pada akhir {$monthEnd}: {$balance}",
        );
    }
});

// #571 review host C7 — perusahaan demo harus sehat: laba bersih FY berjalan positif,
// dengan pendapatan menutup beban (termasuk penyusutan dari register aset) di hampir semua bulan.
invariant('W7-18 current fiscal year demo net profit is positive', function () {
    $fiscalYear = FiscalYear::query()->where('status', 'open')->first();

    expect($fiscalYear)->not->toBeNull('Demo data must leave the current fiscal year open');

    $totals = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
        ->where('journal_entries.status', 'posted')
        ->where('journal_entries.fiscal_year_id', $fiscalYear->id)
        ->where('journal_entries.journal_type', '!=', 'closing')
        ->whereIn('accounts.type', ['revenue', 'expense'])
        ->selectRaw(
            "COALESCE(SUM(CASE WHEN accounts.type = 'revenue' "
            . 'THEN journal_entry_lines.credit - journal_entry_lines.debit ELSE 0 END), 0) as revenue',
        )
        ->selectRaw(
            "COALESCE(SUM(CASE WHEN accounts.type = 'expense' "
            . 'THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE 0 END), 0) as expense',
        )
        ->first();

    $revenue = (float) $totals->revenue;
    $expense = (float) $totals->expense;

    expect($revenue)->toBeGreaterThan(0.0, 'Pendapatan demo FY berjalan harus terisi')
        ->and($revenue - $expense)->toBeGreaterThan(
            0.0,
            "Laba bersih demo FY berjalan harus positif: pendapatan {$revenue} vs beban {$expense}",
        );
});

// #571 C9 — review host menemukan beban > pendapatan di 8 dari 11 bulan berjalan.
// Kini laba FY berjalan harus positif di setiap bulan yang sudah berjalan, bukan hanya total.
invariant('W7-18 current fiscal year demo net profit is positive every month', function () {
    $fiscalYear = FiscalYear::query()->where('status', 'open')->first();

    expect($fiscalYear)->not->toBeNull('Demo data must leave the current fiscal year open');

    $startYear = $fiscalYear->start_date->year;
    $endYear = $fiscalYear->end_date->year;
    $months = collect(DemoCalendar::months())
        ->filter(fn ($month) => $month->year >= $startYear && $month->year <= $endYear)
        ->values();

    expect($months)->not->toBeEmpty('Jendela demo harus memuat bulan pada FY berjalan');

    foreach ($months as $month) {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $totals = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.fiscal_year_id', $fiscalYear->id)
            ->where('journal_entries.journal_type', '!=', 'closing')
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN accounts.type = 'revenue' "
                . 'THEN journal_entry_lines.credit - journal_entry_lines.debit ELSE 0 END), 0) as revenue',
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN accounts.type = 'expense' "
                . 'THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE 0 END), 0) as expense',
            )
            ->first();

        $revenue = (float) $totals->revenue;
        $expense = (float) $totals->expense;
        $label = $month->format('Y-m');

        expect($revenue)->toBeGreaterThan(0.0, "Pendapatan {$label} harus terisi")
            ->and($revenue - $expense)->toBeGreaterThan(
                0.0,
                "Laba bersih {$label} harus positif: pendapatan {$revenue} vs beban {$expense}",
            );
    }
});

// #571 C8 — FY2025 (Nov–Des 2025 di jendela demo) harus punya aktivitas nyata:
// penjualan, beban, dan penyusutan di kedua bulan tersebut.
invariant('W7-18 fiscal year 2025 has sales, expenses and depreciation in November and December', function () {
    $fiscalYear = FiscalYear::query()->where('name', '2025')->first();

    expect($fiscalYear)->not->toBeNull('Demo data must include fiscal year 2025');

    foreach (['2025-11', '2025-12'] as $month) {
        $start = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        $totals = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYear->id)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.journal_type', '!=', 'closing')
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN accounts.type = 'revenue' "
                . 'THEN journal_entry_lines.credit - journal_entry_lines.debit ELSE 0 END), 0) as revenue',
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN accounts.type = 'expense' "
                . 'THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE 0 END), 0) as expense',
            )
            ->first();

        $depreciation = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYear->id)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.journal_type', '!=', 'closing')
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->where('accounts.code', '53000')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit), 0) as amount')
            ->value('amount');

        expect((float) $totals->revenue)->toBeGreaterThan(0.0, "Pendapatan {$month} harus terisi")
            ->and((float) $totals->expense)->toBeGreaterThan(0.0, "Beban {$month} harus terisi")
            ->and((float) $depreciation)->toBeGreaterThan(0.0, "Penyusutan {$month} harus terisi");
    }
});

// #571 C8 — laba/rugi penutupan tahunan harus masuk ke Retained Earnings (32000),
// bukan mengurangi Owner Capital (31000). Sebelumnya seeder memilih akun equity daun
// pertama (31000), sehingga modal disetor tampak tergerus di laporan komparatif.
invariant('W7-18 annual closing entry posts the fiscal year result to retained earnings', function () {
    $fiscalYear = FiscalYear::query()->where('name', '2025')->first();

    expect($fiscalYear)->not->toBeNull('Demo data must include fiscal year 2025');

    $closing = DB::table('journal_entries')
        ->where('fiscal_year_id', $fiscalYear->id)
        ->where('journal_type', 'closing')
        ->first(['id']);

    expect($closing)->not->toBeNull('FY2025 must have an annual closing entry');

    $equityCodes = DB::table('journal_entry_lines')
        ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
        ->where('journal_entry_lines.journal_entry_id', $closing->id)
        ->where('accounts.type', 'equity')
        ->pluck('accounts.code')
        ->all();

    expect($equityCodes)->toContain('32000')
        ->and($equityCodes)->not->toContain('31000');
});

// #571 C8 — akumulasi penyusutan bersaldo kredit dan modal disetor positif pada
// akhir FY2025 dan FY2026 (saldo mentah jurnal, bukan tampilan laporan komparatif).
invariant('W7-18 accumulated depreciation is credit and paid-in capital is positive at fiscal year end', function () {
    foreach (['2025', '2026'] as $year) {
        $fiscalYear = FiscalYear::query()->where('name', $year)->first();

        expect($fiscalYear)->not->toBeNull("Demo data must include fiscal year {$year}");

        foreach (['12200' => 'Akumulasi penyusutan', '31000' => 'Modal disetor'] as $code => $label) {
            $row = DB::table('journal_entry_lines')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
                ->where('journal_entries.fiscal_year_id', $fiscalYear->id)
                ->where('journal_entries.status', 'posted')
                ->where('accounts.code', $code)
                ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as debit')
                ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) as credit')
                ->first();

            $creditBalance = (float) $row->credit - (float) $row->debit;

            expect($creditBalance)->toBeGreaterThan(
                0.0,
                "{$label} ({$code}) harus bersaldo kredit positif di akhir FY{$year}",
            );
        }
    }
});

// #571 C8 — piutang dan utang terbuka tersebar di lima bucket aging (current, 1–30,
// 31–60, 61–90, >90). Jumlah dokumen terbuka dijaga pada rentang demo yang wajar.
invariant('W7-18 open receivables and payables fill all five aging buckets', function () {
    $today = Carbon::today()->startOfDay();

    $bucketize = function ($rows) use ($today): array {
        $buckets = ['current' => 0.0, '1_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, 'over_90' => 0.0];

        foreach ($rows as $row) {
            $dueDate = Carbon::parse($row->due_date)->startOfDay();
            $daysPastDue = (int) $dueDate->diffInDays($today, false);
            $amount = (float) $row->amount_due;

            if ($daysPastDue <= 0) {
                $buckets['current'] += $amount;
            } elseif ($daysPastDue <= 30) {
                $buckets['1_30'] += $amount;
            } elseif ($daysPastDue <= 60) {
                $buckets['31_60'] += $amount;
            } elseif ($daysPastDue <= 90) {
                $buckets['61_90'] += $amount;
            } else {
                $buckets['over_90'] += $amount;
            }
        }

        return $buckets;
    };

    $receivables = DB::table('customer_invoices')
        ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
        ->where('amount_due', '>', 0)
        ->get(['due_date', 'amount_due']);
    $payables = DB::table('supplier_bills')
        ->whereIn('status', ['confirmed', 'partially_paid', 'overdue'])
        ->where('amount_due', '>', 0)
        ->get(['due_date', 'amount_due']);

    expect($receivables->count())->toBeGreaterThanOrEqual(8)->toBeLessThanOrEqual(15)
        ->and($payables->count())->toBeGreaterThanOrEqual(6)->toBeLessThanOrEqual(10);

    foreach (['piutang' => $receivables, 'utang' => $payables] as $label => $rows) {
        $buckets = $bucketize($rows);

        foreach ($buckets as $bucket => $amount) {
            expect($amount)->toBeGreaterThan(
                0.0,
                "Bucket aging {$bucket} untuk {$label} harus terisi",
            );
        }
    }
});
