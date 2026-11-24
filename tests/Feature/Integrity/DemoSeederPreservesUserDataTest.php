<?php

use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\InventoryStocktake;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('demo-integrity');

/**
 * Bill yang diposting wajib punya item; factory item memakai akun COA dari
 * fiscal year yang sama agar jurnalnya valid.
 */
function userBillItem(SupplierBill $bill): void
{
    // Akun beban harus dari COA version aktif (#357): factory Account membuat akun di versi acak.
    $expenseAccount = Account::query()
        ->where('code', '51000')
        ->where('is_active', true)
        ->whereHas('coaVersion', fn ($query) => $query->where('status', 'active'))
        ->firstOrFail();

    SupplierBillItem::factory()->create([
        'supplier_bill_id' => $bill->id,
        'account_id' => $expenseAccount->id,
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

test('re-running demo seeders keeps user stocktakes whose number matches legacy demo pattern', function () {
    test()->seed();

    $warehouse = Warehouse::query()->firstOrFail();
    $adminId = User::query()->orderBy('id')->value('id');

    // Nomor pengguna yang kebetulan cocok pola lama SO-%-92% (tidak punya item demo).
    $userStocktake = InventoryStocktake::query()->create([
        'stocktake_number' => 'SO-2026-920777',
        'warehouse_id' => $warehouse->id,
        'stocktake_date' => now()->toDateString(),
        'status' => 'draft',
        'notes' => 'Stocktake buatan pengguna',
        'created_by' => $adminId,
    ]);

    test()->seed();

    expect(InventoryStocktake::query()->whereKey($userStocktake->id)->exists())->toBeTrue();
});

test('re-running demo seeders does not touch user-created assets or their depreciation runs', function () {
    test()->seed();

    $fiscalYear = FiscalYear::query()->where('status', 'open')->firstOrFail();
    $branch = Branch::query()->firstOrFail();

    // Aset buatan pengguna: kode tidak mengikuti pola demo FA-000001..FA-000012.
    $userAsset = Asset::factory()->create([
        'asset_code' => 'FA-USER-0001',
        'branch_id' => $branch->id,
        'status' => 'active',
        'depreciation_method' => 'straight_line',
        'depreciation_start_date' => $fiscalYear->start_date,
        'useful_life_months' => 60,
        'salvage_value' => 0,
        'purchase_cost' => 1000000,
        'accumulated_depreciation' => 123456,
        'book_value' => 876544,
    ]);

    // Run depresiasi buatan pengguna beserta baris dan jurnalnya.
    $userRun = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'period_start' => $fiscalYear->start_date,
        'period_end' => $fiscalYear->start_date,
        'status' => 'calculated',
    ]);
    $userLine = AssetDepreciationLine::factory()->create([
        'asset_depreciation_run_id' => $userRun->id,
        'asset_id' => $userAsset->id,
    ]);

    test()->seed();

    expect(Asset::query()->whereKey($userAsset->id)->value('accumulated_depreciation'))->toEqual(123456.0)
        ->and(Asset::query()->whereKey($userAsset->id)->value('book_value'))->toEqual(876544.0)
        ->and(AssetDepreciationRun::query()->whereKey($userRun->id)->exists())->toBeTrue()
        ->and(AssetDepreciationRun::query()->whereKey($userRun->id)->value('status'))->toBe('calculated')
        ->and(AssetDepreciationLine::query()->whereKey($userLine->id)->exists())->toBeTrue();
});
