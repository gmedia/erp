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
use Database\Seeders\DemoMasterData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('demo-integrity');

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

beforeEach(function () {
    seedDemoData();
});

// W7-01 — void/cancelled/draft documents must not carry an outstanding balance.
// Read the raw column, not the model accessor, so a corrupted DB value cannot hide behind it.
invariant('W7-01 void, cancelled and draft documents carry no amount due', function () {
    $inactiveStatuses = ['draft', 'cancelled', 'void'];

    foreach (DB::table('customer_invoices')->whereIn('status', $inactiveStatuses)->get(['invoice_number', 'status', 'amount_due']) as $invoice) {
        expect((float) $invoice->amount_due)
            ->toBe(0.0, "Customer invoice {$invoice->invoice_number} ({$invoice->status}) must have amount due 0");
    }

    foreach (DB::table('supplier_bills')->whereIn('status', $inactiveStatuses)->get(['bill_number', 'status', 'amount_due']) as $bill) {
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

            $expected = (float) $line->{$check['qty']};
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

// W7-04 — inventory valuation equals GL 11300.
invariant('W7-04 inventory valuation equals GL inventory account', function () {
    $inventoryAccountId = activeCoaAccountId('11300');
    expect($inventoryAccountId)->not->toBeNull('Active COA must expose account 11300');

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

    expect(round(postedBalance((int) $inventoryAccountId, true), 2))
        ->toBe(round($valuation + $inTransit, 2), 'GL 11300 must equal stock movement valuation plus goods in transit');
});

// W7-04 — invarian 11300 harus menangkap kehilangan jurnal selisih transfer dan
// barang in_transit yang tidak tercatat di GL (issue #357 review PR #384 butir 2).
test('W7-04 11300 invariant fails when a transfer shortage journal is removed', function () {
    $inventoryAccountId = activeCoaAccountId('11300');

    $shortageJournalIds = DB::table('stock_transfers')
        ->whereNotNull('journal_entry_id')
        ->pluck('journal_entry_id');

    expect($shortageJournalIds)->not->toBeEmpty('Demo data must post at least one transfer shortage journal');

    // Hapus jurnal kekurangan: GL 11300 kehilangan kreditnya, invarian harus merah.
    DB::table('journal_entry_lines')
        ->whereIn('journal_entry_id', $shortageJournalIds)
        ->where('account_id', $inventoryAccountId)
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

    expect(round(postedBalance((int) $inventoryAccountId, true), 2))
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

    $runTotal = (float) DB::table('asset_depreciation_lines')
        ->join('asset_depreciation_runs', 'asset_depreciation_runs.id', '=', 'asset_depreciation_lines.asset_depreciation_run_id')
        ->where('asset_depreciation_runs.status', 'posted')
        ->sum('asset_depreciation_lines.amount');

    expect(round(postedBalance((int) $expenseId, true), 2))
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

// Issue #357 (PR #384): every invariant above must also hold after a second `db:seed`.
// One test seeds twice and runs the registered read-only checks against that state.
// It seeds only once more than the beforeEach, so the extra cost is one seed run.
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
    $requestedBy = DB::table('purchase_requests')->pluck('requested_by', 'id');

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
