<?php

use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('demo-integrity');

/**
 * Bill yang diposting wajib punya item; factory item memakai akun COA dari
 * fiscal year yang sama agar jurnalnya valid.
 */
function userBillItem(SupplierBill $bill): void
{
    SupplierBillItem::factory()->create([
        'supplier_bill_id' => $bill->id,
        'quantity' => 2,
        'unit_price' => 1000000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 2000000,
    ]);
}

/**
 * Re-running the demo seeders (`db:seed`) on an install that already has user
 * data must only rebuild demo documents (identified by the demo document
 * numbers the seeders generate). User-created documents, their stock
 * movements and their journals must survive untouched (issue #357).
 */
test('re-running demo seeders keeps user-created bill, journal and stock movements intact', function () {
    test()->seed();

    $fiscalYear = FiscalYear::query()->where('status', 'open')->firstOrFail();
    $branch = Branch::query()->firstOrFail();
    $supplier = Supplier::query()->firstOrFail();
    $warehouse = Warehouse::query()->firstOrFail();
    $product = Product::query()->firstOrFail();

    // Dokumen buatan pengguna: nomor tidak mengikuti pola demo (BILL-{year}-00000N).
    $userBill = SupplierBill::factory()->confirmed()->create([
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'fiscal_year_id' => $fiscalYear->id,
        'bill_number' => 'BILL-USER-0001',
        'bill_date' => $fiscalYear->start_date,
    ]);
    userBillItem($userBill);

    // Jurnal buatan pengguna (manual, tidak punya source dokumen demo).
    $userJournal = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_number' => 'JRN-USER-0001',
        'status' => 'posted',
    ]);

    // Movement buatan pengguna: referensi ke dokumen bukan demo.
    $userMovement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'reference_type' => SupplierBill::class,
        'reference_id' => $userBill->id,
    ]);

    test()->seed();

    expect(SupplierBill::query()->whereKey($userBill->id)->exists())->toBeTrue()
        ->and(SupplierBill::query()->whereKey($userBill->id)->value('bill_number'))->toBe('BILL-USER-0001')
        ->and(JournalEntry::query()->whereKey($userJournal->id)->exists())->toBeTrue()
        ->and(StockMovement::query()->whereKey($userMovement->id)->exists())->toBeTrue();
});

test('re-running demo seeders does not delete journals of user-created documents', function () {
    test()->seed();

    $fiscalYear = FiscalYear::query()->where('status', 'open')->firstOrFail();
    $bill = SupplierBill::factory()->confirmed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'bill_number' => 'BILL-USER-0002',
        'bill_date' => $fiscalYear->start_date,
    ]);
    userBillItem($bill);

    $journal = app(PostSupplierBillJournalAction::class)->execute($bill->refresh());
    expect($journal)->not->toBeNull();

    test()->seed();

    expect(JournalEntry::query()->whereKey($journal->id)->exists())->toBeTrue()
        ->and(SupplierBill::query()->whereKey($bill->id)->value('journal_entry_id'))->toBe($journal->id);
});
