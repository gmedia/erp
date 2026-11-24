<?php

use App\Actions\StockAdjustments\PostStockAdjustmentMovementsAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\SupplierBill;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-375');

/** Jurnal posted seimbang dua baris pada akun postable (syarat ReverseDocumentJournalAction). */
function prReview426PostedJournal(): JournalEntry
{
    $coa = CoaVersion::query()->where('status', 'active')->first()
        ?? CoaVersion::factory()->create(['status' => 'active']);

    $account = fn (): Account => Account::factory()->create([
        'coa_version_id' => $coa->id,
        'is_active' => true,
    ]);

    $entry = JournalEntry::factory()->create(['status' => 'posted']);
    $entry->lines()->create(['account_id' => $account()->id, 'debit' => 100000, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $account()->id, 'debit' => 0, 'credit' => 100000]);

    return $entry;
}

beforeEach(function () {
    FiscalYear::factory()->create([
        'name' => (string) now()->year,
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'status' => 'open',
    ]);

    Sanctum::actingAs(createTestUserWithPermissions([
        'customer_invoice', 'customer_invoice.edit',
        'supplier_bill', 'supplier_bill.edit',
        'stock_adjustment', 'stock_adjustment.edit',
    ]), ['*']);
});

/*
 * Review PR #426, butir 5: status paid harus kembali saat settlement turun.
 */
test('invoice paid yang settlement-nya dihapus kembali ke sent', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'paid',
        'grand_total' => 100000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
    ]);

    $invoice->updatePaymentStatus();

    expect($invoice->fresh()->status)->toBe('sent');
});

test('invoice paid dengan settlement parsial turun ke partially_paid', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'paid',
        'grand_total' => 100000,
        'amount_received' => 40000,
        'credit_note_amount' => 0,
    ]);

    $invoice->updatePaymentStatus();

    expect($invoice->fresh()->status)->toBe('partially_paid');
});

test('bill paid yang pembayarannya dihapus kembali ke confirmed', function () {
    $bill = SupplierBill::factory()->create([
        'status' => 'paid',
        'grand_total' => 100000,
        'amount_paid' => 0,
    ]);

    $bill->updatePaymentStatus();

    expect($bill->fresh()->status)->toBe('confirmed');
});

test('bill paid dengan pembayaran parsial turun ke partially_paid', function () {
    $bill = SupplierBill::factory()->create([
        'status' => 'paid',
        'grand_total' => 100000,
        'amount_paid' => 40000,
    ]);

    $bill->updatePaymentStatus();

    expect($bill->fresh()->status)->toBe('partially_paid');
});

/*
 * Review PR #426, butir 6: invoice/bill overdue tanpa pembayaran harus bisa dibatalkan.
 */
test('invoice overdue tanpa pembayaran bisa di-void dan jurnalnya dibalik', function () {
    $entry = prReview426PostedJournal();
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'overdue',
        'invoice_number' => 'INV-2026-000601',
        'journal_entry_id' => $entry->id,
        'grand_total' => 100000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", ['status' => 'void'])->assertOk();

    expect($invoice->fresh()->status)->toBe('void')
        ->and(JournalEntry::query()
            ->where('source_type', CustomerInvoice::class)
            ->where('source_id', $invoice->id)
            ->where('id', '!=', $entry->id)
            ->exists())->toBeTrue();
});

test('bill overdue tanpa pembayaran bisa dibatalkan dan jurnalnya dibalik', function () {
    $entry = prReview426PostedJournal();
    $bill = SupplierBill::factory()->create([
        'status' => 'overdue',
        'bill_number' => 'BILL-2026-000601',
        'journal_entry_id' => $entry->id,
        'grand_total' => 100000,
        'amount_paid' => 0,
    ]);

    putJson("/api/supplier-bills/{$bill->id}", ['status' => 'cancelled'])->assertOk();

    expect($bill->fresh()->status)->toBe('cancelled')
        ->and(JournalEntry::query()
            ->where('source_type', SupplierBill::class)
            ->where('source_id', $bill->id)
            ->where('id', '!=', $entry->id)
            ->exists())->toBeTrue();
});

/*
 * Review PR #426, butir 7: stock adjustment approved punya transisi dan pembatalan.
 */
test('stock adjustment approved yang dibatalkan membalik stok dan jurnalnya', function () {
    $entry = prReview426PostedJournal();
    $warehouse = Warehouse::factory()->create();
    $adjustment = StockAdjustment::factory()->create([
        'status' => 'approved',
        'warehouse_id' => $warehouse->id,
        'adjustment_number' => 'SA-2026-000701',
        'journal_entry_id' => $entry->id,
    ]);
    $adjustment->items()->create([
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity_before' => 10,
        'quantity_adjusted' => 5,
        'unit_cost' => 1000,
    ]);

    // Movement asal dibentuk seperti alur approve sungguhan, agar ada yang dibalik.
    app(PostStockAdjustmentMovementsAction::class)->execute($adjustment->refresh());

    putJson("/api/stock-adjustments/{$adjustment->id}", ['status' => 'cancelled'])->assertOk();

    expect($adjustment->fresh()->status)->toBe('cancelled')
        ->and(StockMovement::query()
            ->where('reference_type', StockAdjustment::class)
            ->where('reference_id', $adjustment->id)
            ->where('movement_type', 'adjustment_reversal')
            ->exists())->toBeTrue()
        ->and(JournalEntry::query()
            ->where('source_type', StockAdjustment::class)
            ->where('source_id', $adjustment->id)
            ->where('id', '!=', $entry->id)
            ->exists())->toBeTrue();
});

test('stock adjustment approved tidak bisa kembali ke draft', function () {
    $adjustment = StockAdjustment::factory()->create([
        'status' => 'approved',
        'warehouse_id' => Warehouse::factory()->create()->id,
    ]);

    putJson("/api/stock-adjustments/{$adjustment->id}", ['status' => 'draft'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});
