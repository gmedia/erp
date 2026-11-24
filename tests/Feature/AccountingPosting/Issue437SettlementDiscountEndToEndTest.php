<?php

use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\CoaVersion;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-437');

/**
 * Issue #437 butir 6: uji ujung-ke-ujung diskon AR lewat HTTP.
 * Di setiap langkah: amount_due, status invoice, dan saldo GL akun piutang harus sama
 * dengan sisa subledger.
 */
beforeEach(function () {
    Sanctum::actingAs(createTestUserWithPermissions([
        'ar_receipt', 'ar_receipt.edit', 'ar_receipt.create',
        'account',
    ]), ['*']);

    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $coa = CoaVersion::factory()->create(['status' => 'active']);
    $make = fn (string $code, string $name, string $type, string $normal) => Account::factory()->create([
        'coa_version_id' => $coa->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    $make('11200', 'Accounts Receivable', 'asset', 'debit');
    $make('11110', 'Cash in Bank', 'asset', 'debit');
    $make('41000', 'Sales Revenue', 'revenue', 'credit');
    $make('41900', 'Sales Discount', 'revenue', 'debit');
});

/** Saldo akun piutang (11200) dari jurnal posted: debit - kredit. */
function issue437ArGlBalance(): float
{
    $arId = Account::query()->where('code', '11200')->value('id');

    $debit = (float) JournalEntryLine::query()
        ->where('account_id', $arId)
        ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
        ->sum('debit');
    $credit = (float) JournalEntryLine::query()
        ->where('account_id', $arId)
        ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
        ->sum('credit');

    return round($debit - $credit, 2);
}

function issue437E2eInvoice(float $grand): CustomerInvoice
{
    return CustomerInvoice::factory()->sent()->create([
        'invoice_date' => '2026-03-01',
        'grand_total' => $grand,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => $grand,
    ]);
}

function issue437E2eReceipt(CustomerInvoice $invoice, float $allocated, float $discount): ArReceipt
{
    $receipt = ArReceipt::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
        'customer_id' => $invoice->customer_id,
        'branch_id' => $invoice->branch_id,
        'receipt_number' => 'RCP-437-' . fake()->unique()->numerify('####'),
        'bank_account_id' => Account::query()->where('code', '11110')->value('id'),
        'receipt_date' => '2026-03-10',
        'total_amount' => $allocated,
    ]);

    ArReceiptAllocation::query()->create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => $allocated,
        'discount_given' => $discount,
    ]);

    return $receipt;
}

test('picker akun bank AR memakai has_children=0 sehingga akun kelompok tidak ditawarkan', function () {
    $coa = CoaVersion::query()->where('status', 'active')->firstOrFail();
    $parent = Account::factory()->create([
        'coa_version_id' => $coa->id,
        'code' => '11100',
        'name' => 'Kas Kelompok',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    Account::factory()->create([
        'coa_version_id' => $coa->id,
        'parent_id' => $parent->id,
        'code' => '11101',
        'name' => 'Kas Kecil',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $codes = collect(getJson('/api/accounts?is_active=1&has_children=0&per_page=100')
        ->assertOk()
        ->json('data'))->pluck('code');

    expect($codes)->toContain('11101')
        ->and($codes)->not->toContain('11100');
});

test('draft lalu confirmed: sisa, status, dan GL piutang selaras di tiap langkah', function () {
    $invoice = issue437E2eInvoice(100000);
    $glBefore = issue437ArGlBalance();

    $receipt = issue437E2eReceipt($invoice, 95000, 5000);

    // Draft belum dijurnal: sisa invoice dan GL belum berubah.
    expect((float) $invoice->fresh()->amount_due)->toBe(100000.0)
        ->and(issue437ArGlBalance())->toBe($glBefore);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'confirmed'])->assertOk();

    $invoice->refresh();
    expect((float) $invoice->amount_due)->toBe(0.0)
        ->and($invoice->status)->toBe('paid')
        ->and(round(issue437ArGlBalance() - $glBefore, 2))->toBe(-100000.0);
});

test('dua pembayaran pada satu invoice: diskon dijurnal dua kali dan sisa turun berurutan', function () {
    $invoice = issue437E2eInvoice(100000);
    $glBefore = issue437ArGlBalance();

    $first = issue437E2eReceipt($invoice, 40000, 2000);
    putJson("/api/ar-receipts/{$first->id}", ['status' => 'confirmed'])->assertOk();

    $invoice->refresh();
    expect((float) $invoice->amount_due)->toBe(58000.0)
        ->and($invoice->status)->not->toBe('paid')
        ->and(round(issue437ArGlBalance() - $glBefore, 2))->toBe(-42000.0);

    $second = issue437E2eReceipt($invoice, 58000, 0);
    putJson("/api/ar-receipts/{$second->id}", ['status' => 'confirmed'])->assertOk();

    $invoice->refresh();
    expect((float) $invoice->amount_due)->toBe(0.0)
        ->and($invoice->status)->toBe('paid')
        ->and(round(issue437ArGlBalance() - $glBefore, 2))->toBe(-100000.0);
});

test('batal setelah confirmed: sisa kembali dan GL piutang kembali ke saldo awal', function () {
    $invoice = issue437E2eInvoice(100000);
    $glBefore = issue437ArGlBalance();
    $receipt = issue437E2eReceipt($invoice, 95000, 5000);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'confirmed'])->assertOk();
    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'cancelled'])->assertOk();

    $invoice->refresh();
    expect((float) $invoice->amount_due)->toBe(100000.0)
        ->and($invoice->status)->toBe('sent')
        ->and(round(issue437ArGlBalance() - $glBefore, 2))->toBe(0.0);
});
