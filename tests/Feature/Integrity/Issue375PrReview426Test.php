<?php

use App\Actions\StockAdjustments\PostStockAdjustmentMovementsAction;
use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
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
        'ar_receipt', 'ar_receipt.edit',
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

/*
 * Review PR #426, butir 1: draft dan pending_approval tetap bisa dibatalkan tanpa pembalik;
 * pending_approval boleh kembali ke draft.
 */
test('stock adjustment draft bisa dibatalkan tanpa jurnal pembalik', function () {
    $adjustment = StockAdjustment::factory()->create([
        'status' => 'draft',
        'warehouse_id' => Warehouse::factory()->create()->id,
        'journal_entry_id' => null,
    ]);

    putJson("/api/stock-adjustments/{$adjustment->id}", ['status' => 'cancelled'])->assertOk();

    expect($adjustment->fresh()->status)->toBe('cancelled');
});

test('stock adjustment pending_approval bisa dibatalkan dan bisa kembali ke draft', function () {
    $pending = StockAdjustment::factory()->create([
        'status' => 'pending_approval',
        'warehouse_id' => Warehouse::factory()->create()->id,
    ]);
    $back = StockAdjustment::factory()->create([
        'status' => 'pending_approval',
        'warehouse_id' => Warehouse::factory()->create()->id,
    ]);

    putJson("/api/stock-adjustments/{$pending->id}", ['status' => 'cancelled'])->assertOk();
    putJson("/api/stock-adjustments/{$back->id}", ['status' => 'draft'])->assertOk();

    expect($pending->fresh()->status)->toBe('cancelled')
        ->and($back->fresh()->status)->toBe('draft');
});

/*
 * Review PR #426, butir 2: approved tanpa jurnal (data lama) tetap bisa dibatalkan.
 */
test('stock adjustment approved tanpa jurnal bisa dibatalkan', function () {
    $adjustment = StockAdjustment::factory()->create([
        'status' => 'approved',
        'warehouse_id' => Warehouse::factory()->create()->id,
        'journal_entry_id' => null,
    ]);

    putJson("/api/stock-adjustments/{$adjustment->id}", ['status' => 'cancelled'])->assertOk();

    expect($adjustment->fresh()->status)->toBe('cancelled');
});

/*
 * Review PR #426, butir 3: stock adjustment tidak lagi mengunci nilai dokumen approved.
 */
test('stock adjustment approved boleh diubah catatannya walau sudah berjurnal', function () {
    $entry = prReview426PostedJournal();
    $adjustment = StockAdjustment::factory()->create([
        'status' => 'approved',
        'warehouse_id' => Warehouse::factory()->create()->id,
        'journal_entry_id' => $entry->id,
        'notes' => 'awal',
    ]);

    putJson("/api/stock-adjustments/{$adjustment->id}", [
        'status' => 'approved',
        'notes' => 'revisi',
    ])->assertOk();

    expect($adjustment->fresh()->notes)->toBe('revisi');
});

/*
 * Review PR #426, butir 4: nama field alokasi dan header yang dikunci sesuai model.
 */
test('header posted yang dikunci dengan nilai berbeda ditolak', function (string $field, string $value) {
    $entry = prReview426PostedJournal();
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'journal_entry_id' => $entry->id,
        'grand_total' => 100000,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'sent',
        $field => $value,
    ])->assertStatus(422)->assertJsonValidationErrors([$field]);
})->with([
    'invoice_date' => ['invoice_date', '2000-01-01'],
    'customer_id' => ['customer_id', '999999'],
]);

test('alokasi posted memakai discount_given dan tidak bisa diubah', function () {
    $entry = prReview426PostedJournal();
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'paid',
        'journal_entry_id' => $entry->id,
        'grand_total' => 100000,
        'amount_received' => 100000,
    ]);
    $receipt = ArReceipt::factory()->create(['status' => 'confirmed', 'journal_entry_id' => $entry->id]);
    ArReceiptAllocation::query()->create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 100000,
        'discount_given' => 0,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", [
        'status' => 'confirmed',
        'allocations' => [[
            'customer_invoice_id' => $invoice->id,
            'allocated_amount' => 100000,
            'discount_given' => 5000,
        ]],
    ])->assertStatus(422)->assertJsonValidationErrors(['allocations']);
});

/*
 * Review PR #426, butir 5: receipt terposting menurut status tanpa jurnal tetap dilepas dan dihitung ulang.
 */
test('receipt confirmed tanpa jurnal dibatalkan dan alokasinya dilepas', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'grand_total' => 100000,
        'amount_received' => 40000,
        'credit_note_amount' => 0,
    ]);
    $receipt = ArReceipt::factory()->create([
        'receipt_number' => 'AR-2026-000901',
        'status' => 'confirmed',
        'journal_entry_id' => null,
        'total_amount' => 40000,
    ]);
    ArReceiptAllocation::query()->create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 40000,
        'discount_given' => 0,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'cancelled'])->assertOk();

    expect($receipt->fresh()->status)->toBe('cancelled')
        ->and(ArReceiptAllocation::query()->where('ar_receipt_id', $receipt->id)->exists())->toBeFalse()
        ->and((float) $invoice->fresh()->amount_received)->toBe(0.0);
});
