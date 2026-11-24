<?php

use App\Actions\AccountingPosting\PostCreditNoteJournalAction;
use App\Actions\CreditNotes\ApplyCreditNoteAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('credit-note-journal-posting');

function makeCreditNotePostingFixture(): array
{
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $ar = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11200',
        'name' => 'Accounts Receivable',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $returns = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '41100',
        'name' => 'Sales Returns and Allowances',
        'type' => 'revenue',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $branch = Branch::factory()->create();
    $customer = Customer::factory()->create();

    $invoice = CustomerInvoice::factory()->sent()->create([
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-2026-000001',
        'invoice_date' => '2026-03-01',
        'subtotal' => 1000000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 1000000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 1000000,
    ]);

    $creditNote = CreditNote::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'customer_invoice_id' => $invoice->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'credit_note_number' => 'CN-2026-000001',
        'credit_note_date' => '2026-03-10',
        'reason' => 'return',
        'subtotal' => 300000,
        'tax_amount' => 0,
        'grand_total' => 300000,
    ]);

    CreditNoteItem::factory()->create([
        'credit_note_id' => $creditNote->id,
        'account_id' => $returns->id,
        'line_total' => 300000,
        'quantity' => 1,
        'unit_price' => 300000,
    ]);

    return [
        'invoice' => $invoice,
        'credit_note' => $creditNote,
        'ar' => $ar,
        'returns' => $returns,
    ];
}

test('ApplyCreditNoteAction posts a balanced journal: debit returns account, credit AR 11200', function () {
    $fixture = makeCreditNotePostingFixture();

    $applied = app(ApplyCreditNoteAction::class)->execute($fixture['credit_note']);

    expect($applied->status)->toBe('applied')
        ->and($applied->journal_entry_id)->not->toBeNull();

    /** @var JournalEntry $entry */
    $entry = JournalEntry::query()->findOrFail($applied->journal_entry_id);

    expect($entry->status)->toBe('posted')
        ->and($entry->source_type)->toBe(CreditNote::class)
        ->and($entry->source_id)->toBe($applied->id)
        ->and((float) $entry->total_debit)->toBe(300000.0)
        ->and((float) $entry->total_credit)->toBe(300000.0);

    $returnsLine = $entry->lines()->where('account_id', $fixture['returns']->id)->firstOrFail();
    expect((float) $returnsLine->debit)->toBe(300000.0)
        ->and((float) $returnsLine->credit)->toBe(0.0);

    $arLine = $entry->lines()->where('account_id', $fixture['ar']->id)->firstOrFail();
    expect((float) $arLine->credit)->toBe(300000.0)
        ->and((float) $arLine->debit)->toBe(0.0);
});

test('ApplyCreditNoteAction does not post a journal for a credit note that is not applied', function () {
    $fixture = makeCreditNotePostingFixture();
    $fixture['credit_note']->update(['status' => 'draft', 'confirmed_at' => null, 'confirmed_by' => null]);

    expect(fn () => app(ApplyCreditNoteAction::class)->execute($fixture['credit_note']->fresh()))
        ->toThrow(InvalidArgumentException::class);

    expect(JournalEntry::query()->where('source_type', CreditNote::class)->count())->toBe(0);
});

test('PostCreditNoteJournalAction is idempotent', function () {
    $fixture = makeCreditNotePostingFixture();

    $action = app(PostCreditNoteJournalAction::class);
    $first = $action->execute($fixture['credit_note']->fresh());
    $second = $action->execute($fixture['credit_note']->fresh());

    expect($first)->not->toBeNull()
        ->and($second->id)->toBe($first->id)
        ->and(JournalEntry::query()->where('source_type', CreditNote::class)->count())->toBe(1);
});

test('PostCreditNoteJournalAction throws when credit note has no items', function () {
    $fixture = makeCreditNotePostingFixture();
    $fixture['credit_note']->items()->delete();

    expect(fn () => app(PostCreditNoteJournalAction::class)->execute($fixture['credit_note']->fresh()))
        ->toThrow(ValidationException::class);
});

test('applied credit note journal is visible in AR subledger movement via amount_due', function () {
    $fixture = makeCreditNotePostingFixture();

    app(ApplyCreditNoteAction::class)->execute($fixture['credit_note']);

    assertDatabaseHas('customer_invoices', [
        'id' => $fixture['invoice']->id,
        'credit_note_amount' => 300000,
    ]);
});
