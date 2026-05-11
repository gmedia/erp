<?php

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('general-ledger-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['general_ledger_report']);
    Sanctum::actingAs($this->user, ['*']);
});

function postedLedgerLine(string $date = '2026-01-15', string $journalType = 'general'): array
{
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
    $account = Account::factory()->create(['code' => '1010', 'name' => 'Cash']);
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => $date,
        'status' => 'posted',
        'journal_type' => $journalType,
        'description' => 'Posted ledger entry',
    ]);
    JournalEntryLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $account->id, 'debit' => 1000, 'credit' => 0, 'memo' => 'Cash debit']);

    return [$fiscalYear, $account];
}

test('it can get general ledger report for an account', function () {
    [$fiscalYear, $account] = postedLedgerLine();

    getJson("/api/reports/general-ledger?fiscal_year_id={$fiscalYear->id}&account_id={$account->id}&start_date=2026-01-01&end_date=2026-01-31")
        ->assertOk()
        ->assertJsonPath('data.0.account_name', 'Cash')
        ->assertJsonPath('data.0.running_balance', 1000);
});

test('it filters by date range', function () {
    [$fiscalYear, $account] = postedLedgerLine('2026-01-15');
    $entry = JournalEntry::factory()->create(['fiscal_year_id' => $fiscalYear->id, 'entry_date' => '2026-02-15', 'status' => 'posted']);
    JournalEntryLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $account->id, 'debit' => 2000, 'credit' => 0]);

    getJson("/api/reports/general-ledger?fiscal_year_id={$fiscalYear->id}&account_id={$account->id}&start_date=2026-02-01&end_date=2026-02-28")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.debit', 2000);
});

test('it filters by journal type', function () {
    [$fiscalYear, $account] = postedLedgerLine('2026-01-15', 'recurring');

    getJson("/api/reports/general-ledger?fiscal_year_id={$fiscalYear->id}&account_id={$account->id}&start_date=2026-01-01&end_date=2026-01-31&journal_type=recurring")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('it can export general ledger report', function () {
    [$fiscalYear, $account] = postedLedgerLine();

    $response = postJson('/api/reports/general-ledger/export', [
        'fiscal_year_id' => $fiscalYear->id,
        'account_id' => $account->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])->assertOk()->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
});
