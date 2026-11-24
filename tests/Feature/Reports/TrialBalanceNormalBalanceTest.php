<?php

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('trial-balance-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['trial_balance_report']);
    Sanctum::actingAs($this->user, ['*']);
});

function tbnFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
}

function tbnAccount(string $code, string $name, string $type, string $normalBalance): Account
{
    return Account::factory()->create([
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normalBalance,
    ]);
}

function tbnJournal(FiscalYear $fiscalYear, string $date, array $lines): void
{
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => $date,
        'status' => 'posted',
    ]);

    foreach ($lines as [$account, $debit, $credit]) {
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }
}

function tbnRow(FiscalYear $fiscalYear, string $code): ?array
{
    $url = "/api/reports/trial-balance-detailed?fiscal_year_id={$fiscalYear->id}&period_month=1&period_year=2026";

    return collect(getJson($url)->assertOk()->json('data'))->firstWhere('account_code', $code);
}

test('trial balance places a credit-normal account balance in the credit column', function () {
    $fiscalYear = tbnFiscalYear();
    $liability = tbnAccount('2010', 'Utang', 'liability', 'credit');
    $cash = tbnAccount('1010', 'Kas', 'asset', 'debit');

    tbnJournal($fiscalYear, '2026-01-10', [[$cash, 500, 0], [$liability, 0, 500]]);

    $row = tbnRow($fiscalYear, '2010');

    expect((float) $row['credit_balance'])->toBe(500.0);
    expect((float) $row['debit_balance'])->toBe(0.0);
});

test('trial balance keeps a debit-normal account balance in the debit column', function () {
    $fiscalYear = tbnFiscalYear();
    $cash = tbnAccount('1010', 'Kas', 'asset', 'debit');
    $equity = tbnAccount('3010', 'Modal', 'equity', 'credit');

    tbnJournal($fiscalYear, '2026-01-10', [[$cash, 800, 0], [$equity, 0, 800]]);

    $row = tbnRow($fiscalYear, '1010');

    expect((float) $row['debit_balance'])->toBe(800.0);
    expect((float) $row['credit_balance'])->toBe(0.0);
});
