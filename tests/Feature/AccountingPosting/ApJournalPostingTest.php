<?php

use App\Actions\AccountingPosting\PostApPaymentJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Models\Account;
use App\Models\ApPayment;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('ap-journal-posting');

function makePostingFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

function makePostingChartOfAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $apAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '21100',
        'name' => 'Accounts Payable',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    $expenseAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '52000',
        'name' => 'Operating Expense',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $bankAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'name' => 'Cash in Bank',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    return [
        'coa_version' => $coaVersion,
        'ap' => $apAccount,
        'expense' => $expenseAccount,
        'bank' => $bankAccount,
    ];
}

test('PostSupplierBillJournalAction creates a balanced posted journal entry', function () {
    $fiscalYear = makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $supplierBill = SupplierBill::factory()->confirmed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'bill_number' => 'BILL-2026-000001',
        'bill_date' => '2026-03-15',
        'subtotal' => 1000000,
        'tax_amount' => 110000,
        'discount_amount' => 0,
        'grand_total' => 1110000,
        'amount_paid' => 0,
        'amount_due' => 1110000,
    ]);

    $supplierBill->items()->create([
        'account_id' => $accounts['expense']->id,
        'description' => 'Service fee',
        'quantity' => 1,
        'unit_price' => 1000000,
        'discount_percent' => 0,
        'tax_percent' => 11,
        'line_total' => 1110000,
    ]);

    $action = app(PostSupplierBillJournalAction::class);

    $journalEntry = $action->execute($supplierBill->fresh());

    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->status)->toBe('posted')
        ->and($journalEntry->journal_type)->toBe('system')
        ->and($journalEntry->source_type)->toBe(SupplierBill::class)
        ->and($journalEntry->source_id)->toBe($supplierBill->id)
        ->and((float) $journalEntry->total_debit)->toBe(1110000.0)
        ->and((float) $journalEntry->total_credit)->toBe(1110000.0);

    assertDatabaseHas('supplier_bills', [
        'id' => $supplierBill->id,
        'journal_entry_id' => $journalEntry->id,
    ]);

    $apLine = $journalEntry->lines()->where('account_id', $accounts['ap']->id)->first();
    expect((float) $apLine->credit)->toBe(1110000.0)
        ->and((float) $apLine->debit)->toBe(0.0);

    $expenseLine = $journalEntry->lines()->where('account_id', $accounts['expense']->id)->first();
    expect((float) $expenseLine->debit)->toBe(1110000.0)
        ->and((float) $expenseLine->credit)->toBe(0.0);
});

test('PostSupplierBillJournalAction is idempotent and returns existing entry', function () {
    makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $supplierBill = SupplierBill::factory()->confirmed()->create([
        'bill_date' => '2026-03-15',
        'grand_total' => 500000,
        'amount_due' => 500000,
    ]);
    $supplierBill->items()->create([
        'account_id' => $accounts['expense']->id,
        'description' => 'Item',
        'quantity' => 1,
        'unit_price' => 500000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 500000,
    ]);

    $action = app(PostSupplierBillJournalAction::class);

    $first = $action->execute($supplierBill->fresh());
    $second = $action->execute($supplierBill->fresh());

    expect($second->id)->toBe($first->id);
    expect(JournalEntry::count())->toBe(1);
});

test('PostSupplierBillJournalAction returns null when bill is not confirmed', function () {
    makePostingFiscalYear();
    makePostingChartOfAccounts();

    $supplierBill = SupplierBill::factory()->create(['status' => 'draft']);

    $action = app(PostSupplierBillJournalAction::class);

    expect($action->execute($supplierBill))->toBeNull();
    expect(JournalEntry::count())->toBe(0);
});

test('PostSupplierBillJournalAction throws when no items exist', function () {
    makePostingFiscalYear();
    makePostingChartOfAccounts();

    $supplierBill = SupplierBill::factory()->confirmed()->create(['bill_date' => '2026-03-15']);

    $action = app(PostSupplierBillJournalAction::class);

    expect(fn () => $action->execute($supplierBill->fresh()))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('PostSupplierBillJournalAction throws when no active COA version exists', function () {
    makePostingFiscalYear();

    $supplierBill = SupplierBill::factory()->confirmed()->create(['bill_date' => '2026-03-15']);
    $orphanAccount = Account::factory()->create();
    $supplierBill->items()->create([
        'account_id' => $orphanAccount->id,
        'description' => 'Item',
        'quantity' => 1,
        'unit_price' => 100000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 100000,
    ]);

    $action = app(PostSupplierBillJournalAction::class);

    expect(fn () => $action->execute($supplierBill->fresh()))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('PostApPaymentJournalAction creates a balanced posted journal entry', function () {
    $fiscalYear = makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $apPayment = ApPayment::factory()->confirmed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'payment_number' => 'PAY-2026-000001',
        'payment_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 1110000,
    ]);

    $action = app(PostApPaymentJournalAction::class);

    $journalEntry = $action->execute($apPayment->fresh());

    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->status)->toBe('posted')
        ->and($journalEntry->journal_type)->toBe('system')
        ->and($journalEntry->source_type)->toBe(ApPayment::class)
        ->and($journalEntry->source_id)->toBe($apPayment->id);

    assertDatabaseHas('ap_payments', [
        'id' => $apPayment->id,
        'journal_entry_id' => $journalEntry->id,
    ]);

    $apLine = $journalEntry->lines()->where('account_id', $accounts['ap']->id)->first();
    expect((float) $apLine->debit)->toBe(1110000.0)
        ->and((float) $apLine->credit)->toBe(0.0);

    $bankLine = $journalEntry->lines()->where('account_id', $accounts['bank']->id)->first();
    expect((float) $bankLine->credit)->toBe(1110000.0)
        ->and((float) $bankLine->debit)->toBe(0.0);
});

test('PostApPaymentJournalAction is idempotent', function () {
    makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $apPayment = ApPayment::factory()->confirmed()->create([
        'payment_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 500000,
    ]);

    $action = app(PostApPaymentJournalAction::class);

    $first = $action->execute($apPayment->fresh());
    $second = $action->execute($apPayment->fresh());

    expect($second->id)->toBe($first->id);
    expect(JournalEntry::count())->toBe(1);
});

test('PostApPaymentJournalAction returns null when payment is not confirmed', function () {
    makePostingFiscalYear();
    makePostingChartOfAccounts();

    $apPayment = ApPayment::factory()->create(['status' => 'draft']);

    $action = app(PostApPaymentJournalAction::class);

    expect($action->execute($apPayment))->toBeNull();
    expect(JournalEntry::count())->toBe(0);
});

test('PostApPaymentJournalAction throws on non-positive total amount', function () {
    makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $apPayment = ApPayment::factory()->confirmed()->create([
        'payment_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 0,
    ]);

    $action = app(PostApPaymentJournalAction::class);

    expect(fn () => $action->execute($apPayment->fresh()))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('SupplierBillController update triggers journal posting on confirm transition', function () {
    $user = createTestUserWithPermissions([
        'supplier_bill',
        'supplier_bill.create',
        'supplier_bill.edit',
        'supplier_bill.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $supplierBill = SupplierBill::factory()->create([
        'status' => 'draft',
        'bill_date' => '2026-03-15',
        'subtotal' => 800000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 800000,
        'amount_paid' => 0,
        'amount_due' => 800000,
    ]);

    $payload = [
        'status' => 'confirmed',
        'items' => [
            [
                'account_id' => $accounts['expense']->id,
                'description' => 'Service',
                'quantity' => 1,
                'unit_price' => 800000,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ],
        ],
    ];

    putJson("/api/supplier-bills/{$supplierBill->id}", $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');

    $supplierBill->refresh();
    expect($supplierBill->journal_entry_id)->not->toBeNull();

    assertDatabaseHas('journal_entries', [
        'id' => $supplierBill->journal_entry_id,
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => SupplierBill::class,
        'source_id' => $supplierBill->id,
    ]);
});

test('SupplierBillController update does not double-post on subsequent saves', function () {
    $user = createTestUserWithPermissions([
        'supplier_bill',
        'supplier_bill.create',
        'supplier_bill.edit',
        'supplier_bill.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $supplierBill = SupplierBill::factory()->create([
        'status' => 'draft',
        'bill_date' => '2026-03-15',
        'grand_total' => 500000,
        'amount_due' => 500000,
    ]);

    $items = [[
        'account_id' => $accounts['expense']->id,
        'description' => 'Service',
        'quantity' => 1,
        'unit_price' => 500000,
        'discount_percent' => 0,
        'tax_percent' => 0,
    ]];

    putJson("/api/supplier-bills/{$supplierBill->id}", [
        'status' => 'confirmed',
        'items' => $items,
    ])->assertOk();

    putJson("/api/supplier-bills/{$supplierBill->id}", [
        'notes' => 'edited after confirm',
    ])->assertOk();

    expect(JournalEntry::where('source_type', SupplierBill::class)->count())->toBe(1);
});

test('ApPaymentController update triggers journal posting on confirm transition', function () {
    $user = createTestUserWithPermissions([
        'ap_payment',
        'ap_payment.create',
        'ap_payment.edit',
        'ap_payment.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $apPayment = ApPayment::factory()->create([
        'status' => 'draft',
        'payment_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 750000,
    ]);

    putJson("/api/ap-payments/{$apPayment->id}", [
        'status' => 'confirmed',
    ])->assertOk()->assertJsonPath('data.status', 'confirmed');

    $apPayment->refresh();
    expect($apPayment->journal_entry_id)->not->toBeNull();

    assertDatabaseHas('journal_entries', [
        'id' => $apPayment->journal_entry_id,
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => ApPayment::class,
        'source_id' => $apPayment->id,
    ]);
});

test('ApPaymentController update does not double-post on subsequent saves', function () {
    $user = createTestUserWithPermissions([
        'ap_payment',
        'ap_payment.create',
        'ap_payment.edit',
        'ap_payment.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makePostingFiscalYear();
    $accounts = makePostingChartOfAccounts();

    $apPayment = ApPayment::factory()->create([
        'status' => 'draft',
        'payment_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 250000,
    ]);

    putJson("/api/ap-payments/{$apPayment->id}", ['status' => 'confirmed'])->assertOk();
    putJson("/api/ap-payments/{$apPayment->id}", ['notes' => 'reconciled note'])->assertOk();

    expect(JournalEntry::where('source_type', ApPayment::class)->count())->toBe(1);
});
