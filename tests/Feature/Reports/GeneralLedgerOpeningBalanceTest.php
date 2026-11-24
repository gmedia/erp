<?php

use App\Exports\GeneralLedgerReportExport;
use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('general-ledger-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['general_ledger_report']);
    Sanctum::actingAs($this->user, ['*']);
});

/**
 * Membuat periode tahun berjalan, satu akun kas, dan dua jurnal posted:
 * saldo awal debit 1000 (sebelum periode laporan) dan transaksi debit 500 (dalam periode).
 *
 * @return array{fiscalYear: FiscalYear, account: Account}
 */
function createGeneralLedgerOpeningFixture(): array
{
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $account = Account::factory()->create(['code' => '1010', 'name' => 'Cash']);

    $before = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => '2026-01-10',
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $before->id,
        'account_id' => $account->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    $inside = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => '2026-02-15',
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $inside->id,
        'account_id' => $account->id,
        'debit' => 500,
        'credit' => 0,
    ]);

    return ['fiscalYear' => $fiscalYear, 'account' => $account];
}

test('general ledger with start_date starts running balance from the opening balance before that date', function () {
    ['fiscalYear' => $fiscalYear, 'account' => $account] = createGeneralLedgerOpeningFixture();

    $query = http_build_query([
        'fiscal_year_id' => $fiscalYear->id,
        'account_id' => $account->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-28',
    ]);

    $response = getJson("/api/reports/general-ledger?{$query}")->assertOk();

    // Saldo berjalan harus mulai dari saldo awal 1000, bukan 0.
    $response->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.running_balance', 1500);
});

test('general ledger without start_date carries prior-year balance for balance sheet accounts only', function () {
    $prevYear = FiscalYear::factory()->create([
        'status' => 'closed',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $cash = Account::factory()->create(['code' => '1010', 'name' => 'Cash', 'type' => 'asset']);
    $revenue = Account::factory()->create([
        'code' => '4010',
        'name' => 'Sales',
        'type' => 'revenue',
        'normal_balance' => 'credit',
    ]);

    $prevEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $prevYear->id,
        'entry_date' => '2025-06-01',
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $prevEntry->id,
        'account_id' => $cash->id,
        'debit' => 1000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $prevEntry->id,
        'account_id' => $revenue->id,
        'debit' => 0,
        'credit' => 1000,
    ]);

    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => '2026-02-15',
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $cash->id,
        'debit' => 500,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $revenue->id,
        'debit' => 0,
        'credit' => 500,
    ]);

    // Akun neraca: saldo tahun sebelumnya (1000) ikut sebagai saldo awal, konsisten dengan trial balance.
    getJson('/api/reports/general-ledger?' . http_build_query([
        'fiscal_year_id' => $fiscalYear->id,
        'account_id' => $cash->id,
    ]))->assertOk()
        ->assertJsonPath('data.0.running_balance', 1500);

    // Akun laba rugi: saldo tahun sebelumnya tidak dibawa (running balance mulai dari 0).
    getJson('/api/reports/general-ledger?' . http_build_query([
        'fiscal_year_id' => $fiscalYear->id,
        'account_id' => $revenue->id,
    ]))->assertOk()
        ->assertJsonPath('data.0.running_balance', -500);
});

test('general ledger with start_date keeps prior-year balance for balance sheet accounts', function () {
    $prevYear = FiscalYear::factory()->create([
        'status' => 'closed',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $cash = Account::factory()->create(['code' => '1010', 'name' => 'Cash', 'type' => 'asset']);

    $prevEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $prevYear->id,
        'entry_date' => '2025-06-01',
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $prevEntry->id,
        'account_id' => $cash->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    $janEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => '2026-01-10',
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $janEntry->id,
        'account_id' => $cash->id,
        'debit' => 200,
        'credit' => 0,
    ]);

    $febEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => '2026-02-15',
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $febEntry->id,
        'account_id' => $cash->id,
        'debit' => 500,
        'credit' => 0,
    ]);

    // Mulai 1 Februari: saldo awal = saldo tahun lalu (1000) + jurnal 10 Januari (200) = 1200.
    getJson('/api/reports/general-ledger?' . http_build_query([
        'fiscal_year_id' => $fiscalYear->id,
        'account_id' => $cash->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-28',
    ]))->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.running_balance', 1700);

    // Mulai 1 Januari: saldo awal = saldo tahun lalu (1000), lalu jurnal Januari dan Februari.
    getJson('/api/reports/general-ledger?' . http_build_query([
        'fiscal_year_id' => $fiscalYear->id,
        'account_id' => $cash->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]))->assertOk()
        ->assertJsonPath('data.0.running_balance', 1200)
        ->assertJsonPath('data.1.running_balance', 1700);
});

test('general ledger export starts running balance from the opening balance', function () {
    ['fiscalYear' => $fiscalYear, 'account' => $account] = createGeneralLedgerOpeningFixture();

    $rows = (new GeneralLedgerReportExport([
        'fiscal_year_id' => $fiscalYear->id,
        'account_id' => $account->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-28',
    ]))->collection();

    expect($rows)->toHaveCount(1);
    expect($rows->first()[8])->toBe(1500.0);
});
