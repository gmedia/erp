<?php

use App\Actions\AccountingPosting\PostBankReconciliationJournalAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('bank-reconciliations');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['bank_reconciliation', 'bank_reconciliation.create', 'bank_reconciliation.edit', 'bank_reconciliation.delete']);
    Sanctum::actingAs($this->user, ['*']);
});

function bankReconciliationPayload(): array
{
    return [
        'account_id' => Account::factory()->create()->id,
        'fiscal_year_id' => FiscalYear::factory()->create(['status' => 'open'])->id,
        'reconciliation_date' => '2026-01-31',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'statement_balance' => 5000,
        'book_balance' => 5000,
        'reconciled_balance' => 5000,
        'difference' => 0,
        'status' => 'in_progress',
        'notes' => 'January bank rec',
        'items' => [
            ['transaction_date' => '2026-01-15', 'description' => 'Deposit', 'debit' => 500, 'credit' => 0, 'type' => 'deposit', 'is_reconciled' => true],
        ],
    ];
}

test('it can list bank reconciliations', function () {
    BankReconciliation::factory()->count(3)->create();

    getJson('/api/bank-reconciliations')->assertOk()->assertJsonStructure(['data' => [['id', 'account', 'status', 'difference']], 'meta']);
});

test('it can create a bank reconciliation', function () {
    postJson('/api/bank-reconciliations', bankReconciliationPayload())
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'items']]);

    assertDatabaseHas('bank_reconciliations', ['notes' => 'January bank rec']);
    expect(BankReconciliation::first()->items)->toHaveCount(1);
});

test('it can show a bank reconciliation with items', function () {
    $reconciliation = BankReconciliation::factory()->create();
    BankReconciliationItem::factory()->create(['bank_reconciliation_id' => $reconciliation->id]);

    getJson("/api/bank-reconciliations/{$reconciliation->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $reconciliation->id)
        ->assertJsonStructure(['data' => ['items']]);
});

test('it can update a bank reconciliation', function () {
    $reconciliation = BankReconciliation::factory()->create(['notes' => 'Old notes']);
    $payload = bankReconciliationPayload();
    $payload['notes'] = 'Updated notes';

    putJson("/api/bank-reconciliations/{$reconciliation->id}", $payload)->assertOk();

    assertDatabaseHas('bank_reconciliations', ['id' => $reconciliation->id, 'notes' => 'Updated notes']);
});

test('it can delete a bank reconciliation', function () {
    $reconciliation = BankReconciliation::factory()->create();

    deleteJson("/api/bank-reconciliations/{$reconciliation->id}")->assertNoContent();

    assertDatabaseMissing('bank_reconciliations', ['id' => $reconciliation->id]);
});

test('it can export bank reconciliations', function () {
    Excel::fake();
    BankReconciliation::factory()->create();

    postJson('/api/bank-reconciliations/export', [])->assertOk()->assertJsonStructure(['url', 'filename']);
});

test('it can complete a bank reconciliation when difference is zero', function () {
    $reconciliation = BankReconciliation::factory()->create(['difference' => 0, 'status' => 'in_progress']);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

test('it cannot complete when difference is not zero', function () {
    $reconciliation = BankReconciliation::factory()->create(['difference' => 10, 'status' => 'in_progress']);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('difference');
});

test('completing a bank reconciliation keeps state completed when journal posting fails (best-effort)', function () {
    $reconciliation = BankReconciliation::factory()->create([
        'difference' => 0,
        'status' => 'in_progress',
        'completed_at' => null,
        'completed_by' => null,
    ]);

    $this->app->bind(PostBankReconciliationJournalAction::class, function ($app) {
        return new class($app->make(CreateJournalEntryAction::class)) extends PostBankReconciliationJournalAction
        {
            public function execute(BankReconciliation $bankReconciliation): ?JournalEntry
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $reconciliation->refresh();
    expect($reconciliation->status)->toBe('completed');
    expect($reconciliation->completed_at)->not->toBeNull();
});

test('it can add an item to a bank reconciliation', function () {
    $reconciliation = BankReconciliation::factory()->create();

    postJson("/api/bank-reconciliations/{$reconciliation->id}/items", [
        'transaction_date' => '2026-01-20',
        'description' => 'Adjustment',
        'debit' => 100,
        'credit' => 0,
        'type' => 'adjustment',
        'is_reconciled' => true,
    ])->assertCreated()->assertJsonStructure(['data' => ['id', 'description']]);

    assertDatabaseHas('bank_reconciliation_items', ['bank_reconciliation_id' => $reconciliation->id, 'description' => 'Adjustment']);
});

test('it can remove an item from a bank reconciliation', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $item = BankReconciliationItem::factory()->create(['bank_reconciliation_id' => $reconciliation->id]);

    deleteJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}")->assertNoContent();

    assertDatabaseMissing('bank_reconciliation_items', ['id' => $item->id]);
});

test('removing a reconciled item recalculates the reconciliation difference', function () {
    $reconciliation = BankReconciliation::factory()->create([
        'book_balance' => 1000,
        'statement_balance' => 1500,
    ]);

    BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => true,
        'debit' => 0,
        'credit' => 500,
    ]);

    $reconciliation->recalculateBalances();
    $reconciliation->refresh();
    expect((float) $reconciliation->difference)->toBe(0.0);

    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => true,
        'debit' => 0,
        'credit' => 200,
    ]);

    $reconciliation->recalculateBalances();
    $reconciliation->refresh();
    expect((float) $reconciliation->difference)->toBe(-200.0);

    deleteJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}")->assertNoContent();

    $reconciliation->refresh();
    expect((float) $reconciliation->difference)->toBe(0.0);
    expect((float) $reconciliation->reconciled_balance)->toBe(1500.0);
});

// ─── Import Preview ───────────────────────────────────────────────────────────

test('it can preview a bank statement file', function () {
    $csvContent = "date,description,amount\n2026-01-15,Deposit from client,5000\n2026-01-16,Bank charge,-50\n";
    $file = UploadedFile::fake()->createWithContent('statement.csv', $csvContent);

    postJson('/api/bank-reconciliations/import-preview', ['file' => $file])
        ->assertOk()
        ->assertJsonStructure(['headers', 'preview_rows'])
        ->assertJsonPath('headers.0', 'date')
        ->assertJsonPath('headers.1', 'description')
        ->assertJsonPath('headers.2', 'amount');
});

test('import preview requires a file', function () {
    postJson('/api/bank-reconciliations/import-preview', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

// ─── Import Statement ─────────────────────────────────────────────────────────

test('it can import a bank statement with column mapping', function () {
    $reconciliation = BankReconciliation::factory()->create();

    $csvContent = "date,description,amount,reference\n2026-01-15,Deposit from client,5000,REF-001\n2026-01-16,Bank charge,-50,REF-002\n";
    $file = UploadedFile::fake()->createWithContent('statement.csv', $csvContent);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/import-statement", [
        'file' => $file,
        'mapping' => [
            'date' => 'date',
            'description' => 'description',
            'amount' => 'amount',
            'reference' => 'reference',
        ],
    ])->assertOk()->assertJsonStructure(['imported', 'skipped', 'errors']);

    assertDatabaseHas('bank_reconciliation_items', [
        'bank_reconciliation_id' => $reconciliation->id,
        'description' => 'Deposit from client',
        'reference' => 'REF-001',
    ]);
});

test('it validates mapping requires date and description columns', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $file = UploadedFile::fake()->createWithContent('statement.csv', "col1,col2\nval1,val2\n");

    postJson("/api/bank-reconciliations/{$reconciliation->id}/import-statement", [
        'file' => $file,
        'mapping' => [
            'amount' => 'col1',
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors(['mapping.date', 'mapping.description']);
});

test('it validates mapping requires amount or debit+credit columns', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $file = UploadedFile::fake()->createWithContent('statement.csv', "date,description\n2026-01-01,Test\n");

    postJson("/api/bank-reconciliations/{$reconciliation->id}/import-statement", [
        'file' => $file,
        'mapping' => [
            'date' => 'date',
            'description' => 'description',
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors('mapping.amount');
});

// ─── Auto Match ───────────────────────────────────────────────────────────────

test('it can auto-match bank items to journal entry lines', function () {
    $account = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $reconciliation = BankReconciliation::factory()->create([
        'account_id' => $account->id,
        'fiscal_year_id' => $fiscalYear->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ]);

    // Create unmatched bank item with debit=500
    BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'debit' => 500,
        'credit' => 0,
        'is_reconciled' => false,
        'journal_entry_line_id' => null,
        'transaction_date' => '2026-01-15',
    ]);

    // Create posted JE with matching line
    $journalEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'debit' => 500,
        'credit' => 0,
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/auto-match")
        ->assertOk()
        ->assertJsonPath('matched', 1)
        ->assertJsonPath('unmatched', 0);

    assertDatabaseHas('bank_reconciliation_items', [
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => true,
    ]);
});

test('auto-match returns zero when no candidates exist', function () {
    $reconciliation = BankReconciliation::factory()->create([
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ]);

    BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => false,
        'journal_entry_line_id' => null,
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/auto-match")
        ->assertOk()
        ->assertJsonPath('matched', 0);
});

// ─── Manual Match ─────────────────────────────────────────────────────────────

test('it can manually match a bank item to a journal entry line', function () {
    $account = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $reconciliation = BankReconciliation::factory()->create([
        'account_id' => $account->id,
        'fiscal_year_id' => $fiscalYear->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ]);

    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => false,
        'journal_entry_line_id' => null,
    ]);

    $journalEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    $line = JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}/match", [
        'journal_entry_line_id' => $line->id,
    ])->assertOk()->assertJsonPath('data.is_reconciled', true);

    assertDatabaseHas('bank_reconciliation_items', [
        'id' => $item->id,
        'journal_entry_line_id' => $line->id,
        'is_reconciled' => true,
    ]);
});

test('it cannot match to a JE line from wrong account', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $reconciliation = BankReconciliation::factory()->create([
        'account_id' => $account->id,
        'fiscal_year_id' => $fiscalYear->id,
    ]);

    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => false,
        'journal_entry_line_id' => null,
    ]);

    $journalEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    $line = JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $otherAccount->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}/match", [
        'journal_entry_line_id' => $line->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('journal_entry_line_id');
});

test('it cannot match to an already-matched JE line', function () {
    $account = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $reconciliation = BankReconciliation::factory()->create([
        'account_id' => $account->id,
        'fiscal_year_id' => $fiscalYear->id,
    ]);

    $journalEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    $line = JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    // First item already matched to this line
    BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'journal_entry_line_id' => $line->id,
        'is_reconciled' => true,
    ]);

    // Second item tries to match same line
    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => false,
        'journal_entry_line_id' => null,
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}/match", [
        'journal_entry_line_id' => $line->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('journal_entry_line_id');
});

// ─── Unmatch ──────────────────────────────────────────────────────────────────

test('it can unmatch a reconciled bank item', function () {
    $account = Account::factory()->create();
    $reconciliation = BankReconciliation::factory()->create(['account_id' => $account->id]);

    $journalEntry = JournalEntry::factory()->create(['status' => 'posted']);
    $line = JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
    ]);

    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'journal_entry_line_id' => $line->id,
        'is_reconciled' => true,
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}/unmatch")
        ->assertOk()
        ->assertJsonPath('data.is_reconciled', false)
        ->assertJsonPath('data.journal_entry_line_id', null);

    assertDatabaseHas('bank_reconciliation_items', [
        'id' => $item->id,
        'journal_entry_line_id' => null,
        'is_reconciled' => false,
    ]);
});

// ─── Unmatched Journal Lines ──────────────────────────────────────────────────

test('it can list unmatched journal entry lines', function () {
    $account = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $reconciliation = BankReconciliation::factory()->create([
        'account_id' => $account->id,
        'fiscal_year_id' => $fiscalYear->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ]);

    $journalEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-10',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    getJson("/api/bank-reconciliations/{$reconciliation->id}/unmatched-journal-lines")
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'debit', 'credit', 'memo']]]);
});

test('it filters unmatched journal lines by search', function () {
    $account = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $reconciliation = BankReconciliation::factory()->create([
        'account_id' => $account->id,
        'fiscal_year_id' => $fiscalYear->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ]);

    $journalEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-10',
        'reference' => 'UNIQUE-REF-XYZ',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'debit' => 500,
        'credit' => 0,
    ]);

    // Another JE that should NOT match
    $otherJe = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-12',
        'reference' => 'OTHER-REF',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $otherJe->id,
        'account_id' => $account->id,
        'debit' => 300,
        'credit' => 0,
    ]);

    $response = getJson("/api/bank-reconciliations/{$reconciliation->id}/unmatched-journal-lines?search=UNIQUE-REF-XYZ")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

// ─── Assign Account ───────────────────────────────────────────────────────────

test('it can assign an account to an unmatched bank item', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => false,
        'account_id' => null,
    ]);
    $expenseAccount = Account::factory()->create();

    putJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}/assign-account", [
        'account_id' => $expenseAccount->id,
    ])->assertOk()->assertJsonPath('data.account_id', $expenseAccount->id);

    assertDatabaseHas('bank_reconciliation_items', [
        'id' => $item->id,
        'account_id' => $expenseAccount->id,
    ]);
});

test('assign account validates account_id exists', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
    ]);

    putJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}/assign-account", [
        'account_id' => 99999,
    ])->assertUnprocessable()->assertJsonValidationErrors('account_id');
});

// ─── Complete with Journal Posting ────────────────────────────────────────────

test('completing a reconciliation posts journal for unmatched items with accounts', function () {
    $bankAccount = Account::factory()->create(['code' => 'BANK-001']);
    $expenseAccount = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'name' => '2026',
    ]);

    $reconciliation = BankReconciliation::factory()->create([
        'account_id' => $bankAccount->id,
        'fiscal_year_id' => $fiscalYear->id,
        'difference' => 0,
        'status' => 'in_progress',
        'reconciliation_date' => '2026-01-31',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ]);

    // Unmatched item with account assigned (should trigger journal posting)
    BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => false,
        'journal_entry_line_id' => null,
        'account_id' => $expenseAccount->id,
        'debit' => 200,
        'credit' => 0,
        'description' => 'Bank fee',
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $reconciliation->refresh();
    expect($reconciliation->journal_entry_id)->not->toBeNull();

    assertDatabaseHas('journal_entries', [
        'id' => $reconciliation->journal_entry_id,
        'status' => 'posted',
        'reference' => "RECON-{$reconciliation->id}",
    ]);
});

test('completing without unmatched items does not create journal entry', function () {
    $account = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'name' => '2026',
    ]);

    $reconciliation = BankReconciliation::factory()->create([
        'account_id' => $account->id,
        'fiscal_year_id' => $fiscalYear->id,
        'difference' => 0,
        'status' => 'in_progress',
        'reconciliation_date' => '2026-01-31',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ]);

    // All items are matched (no unmatched items with account_id)
    $journalEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    $line = JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
    ]);
    BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => true,
        'journal_entry_line_id' => $line->id,
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $reconciliation->refresh();
    expect($reconciliation->journal_entry_id)->toBeNull();
});

test('it returns 403 without bank_reconciliation permission', function () {
    $user = createTestUserWithPermissions([]);
    Sanctum::actingAs($user, ['*']);

    getJson('/api/bank-reconciliations')->assertForbidden();
});
