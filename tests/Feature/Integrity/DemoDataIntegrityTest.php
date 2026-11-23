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
use Database\Seeders\SubledgerGlReconciliationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('demo-integrity');

/**
 * Seed the full demo dataset. Invariants below must hold for the data that
 * ships to every on-premise install, so they are asserted on the real
 * DatabaseSeeder output rather than on hand-built fixtures.
 */
function seedDemoData(): void
{
    test()->seed();

    // Production demo installs reconcile GL to the subledger, but
    // DatabaseSeeder skips that step under the testing environment so that
    // branch-isolation tests start from a zero clearing balance. Run it here
    // so the invariants validate the dataset that actually ships.
    test()->seed(SubledgerGlReconciliationSeeder::class);
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
test('W7-01 void, cancelled and draft documents carry no amount due', function () {
    $inactiveStatuses = ['draft', 'cancelled', 'void'];

    foreach (CustomerInvoice::query()->whereIn('status', $inactiveStatuses)->get() as $invoice) {
        expect((float) $invoice->amount_due)
            ->toBe(0.0, "Customer invoice {$invoice->invoice_number} ({$invoice->status}) must have amount due 0");
    }

    foreach (SupplierBill::query()->whereIn('status', $inactiveStatuses)->get() as $bill) {
        expect((float) $bill->amount_due)
            ->toBe(0.0, "Supplier bill {$bill->bill_number} ({$bill->status}) must have amount due 0");
    }
});

// W7-02 — AR/AP settlements reconcile with their allocation ledgers.
test('W7-02 receipt, credit note and payment allocations match settled amounts', function () {
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
test('W7-02 GL receivable and payable control accounts equal open subledger balances', function () {
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
test('W7-03 purchase order received quantities derive from confirmed goods receipts', function () {
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
test('W7-03 supplier returns belong to the purchase order supplier and receipt', function () {
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
test('W7-04 stock movement balances form a correct chain per product and warehouse', function () {
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

// W7-04 — snapshot quantity equals the latest movement balance (by moved_at, id).
test('W7-04 stock snapshot uses the latest movement balance', function () {
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

    $request = IndexStockMonitorRequest::create('/api/stock-monitor', 'GET');
    $result = app(IndexStockMonitorAction::class)->execute($request);
    $snapshot = collect($result['stocks']->items());

    expect($snapshot)->not->toBeEmpty('Stock monitor must show stock rows');

    foreach ($snapshot as $row) {
        $key = $row->product_id . ':' . $row->warehouse_id;
        expect(round((float) $row->quantity_on_hand, 2))
            ->toBe(round($latest[$key] ?? 0.0, 2), "Stock snapshot for {$key} must equal the latest movement balance");
    }
});

// W7-04 — only stock-affecting document states produce movements.
test('W7-04 only eligible document states produce stock movements', function () {
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
test('W7-04 inventory valuation equals GL inventory account', function () {
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

    expect(round(postedBalance((int) $inventoryAccountId, true), 2))
        ->toBe(round($valuation, 2), 'GL 11300 must equal the sum of stock movement valuation');
});

// W7-05 — book value is always cost minus accumulated depreciation.
test('W7-05 asset book value equals cost minus accumulated depreciation', function () {
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
test('W7-05 GL equipment minus accumulated depreciation equals in-service book value', function () {
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
test('W7-05 posted depreciation runs have posted journals', function () {
    $runs = DB::table('asset_depreciation_runs')->where('status', 'posted')->get();

    expect($runs)->not->toBeEmpty('Demo data must include a posted depreciation run');

    foreach ($runs as $run) {
        expect($run->journal_entry_id)->not->toBeNull("Depreciation run {$run->id} must reference a journal");

        $journalStatus = DB::table('journal_entries')->where('id', $run->journal_entry_id)->value('status');
        expect($journalStatus)->toBe('posted', "Depreciation run {$run->id} journal must be posted");
    }
});

// W7-05 — depreciation expense in the GL equals the posted run depreciation.
test('W7-05 depreciation expense equals posted depreciation run totals', function () {
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
test('W7-05 posted journals are balanced', function () {
    $totals = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as debit, COALESCE(SUM(journal_entry_lines.credit), 0) as credit')
        ->first();

    expect(round((float) $totals->debit, 2))
        ->toBe(round((float) $totals->credit, 2), 'Total debits must equal total credits across posted journals');
});
