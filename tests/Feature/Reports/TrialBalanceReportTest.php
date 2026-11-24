<?php

use App\Actions\PeriodClosings\ClosePeriodAction;
use App\Actions\PeriodClosings\ReopenPeriodAction;
use App\Exports\TrialBalanceReportExport;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PeriodClosing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('trial-balance-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['trial_balance_report']);
    Sanctum::actingAs($this->user, ['*']);
});

function tbFiscalYear(int $year, string $status = 'open'): FiscalYear
{
    return FiscalYear::factory()->create([
        'status' => $status,
        'start_date' => "{$year}-01-01",
        'end_date' => "{$year}-12-31",
    ]);
}

function tbAccount(string $code, string $name, string $type, string $normalBalance, ?int $coaVersionId = null): Account
{
    $attributes = [
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normalBalance,
    ];

    if ($coaVersionId !== null) {
        $attributes['coa_version_id'] = $coaVersionId;
        $attributes['is_active'] = true;
    }

    return Account::factory()->create($attributes);
}

/**
 * Buat jurnal dengan baris [account, debit, credit] yang diberikan.
 */
function tbJournal(FiscalYear $fiscalYear, string $date, array $lines, string $status = 'posted'): JournalEntry
{
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => $date,
        'status' => $status,
    ]);

    foreach ($lines as [$account, $debit, $credit]) {
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }

    return $entry;
}

function tbReport(FiscalYear $fiscalYear, string $query = ''): array
{
    return getJson("/api/reports/trial-balance-detailed?fiscal_year_id={$fiscalYear->id}{$query}")
        ->assertOk()
        ->json();
}

function tbRow(array $report, string $code): ?array
{
    return collect($report['data'])->firstWhere('account_code', $code);
}

test('it can get trial balance report', function () {
    $fiscalYear = tbFiscalYear(2026);
    $cash = tbAccount('1010', 'Cash', 'asset', 'debit');
    $revenue = tbAccount('4010', 'Sales', 'revenue', 'credit');
    tbJournal($fiscalYear, '2026-01-15', [[$cash, 1000, 0], [$revenue, 0, 1000]]);

    getJson("/api/reports/trial-balance-detailed?fiscal_year_id={$fiscalYear->id}&period_month=1&period_year=2026")
        ->assertOk()
        ->assertJsonPath('data.0.account_name', 'Cash')
        ->assertJsonPath('summary.total_debit', 1000)
        ->assertJsonPath('summary.total_credit', 1000);
});

test('jurnal posted di periode yang belum ditutup muncul di trial balance', function () {
    $fiscalYear = tbFiscalYear(2026);
    $cash = tbAccount('1010', 'Kas', 'asset', 'debit');
    $revenue = tbAccount('4010', 'Penjualan', 'revenue', 'credit');

    // Tidak ada account_balances dan periode tidak pernah ditutup.
    tbJournal($fiscalYear, '2026-03-10', [[$cash, 750, 0], [$revenue, 0, 750]]);

    $report = tbReport($fiscalYear, '&period_month=3&period_year=2026');

    expect((float) tbRow($report, '1010')['debit_balance'])->toBe(750.0)
        ->and((float) tbRow($report, '4010')['credit_balance'])->toBe(750.0)
        ->and((float) $report['summary']['total_debit'])->toBe((float) $report['summary']['total_credit']);
});

test('jurnal draft tidak ikut trial balance', function () {
    $fiscalYear = tbFiscalYear(2026);
    $cash = tbAccount('1010', 'Kas', 'asset', 'debit');
    $revenue = tbAccount('4010', 'Penjualan', 'revenue', 'credit');
    tbJournal($fiscalYear, '2026-03-10', [[$cash, 750, 0], [$revenue, 0, 750]], 'draft');

    $report = tbReport($fiscalYear);

    expect($report['data'])->toBeEmpty()
        ->and((float) $report['summary']['total_debit'])->toBe(0.0);
});

test('it aggregates journal movements per account when no period is selected', function () {
    $fiscalYear = tbFiscalYear(2026);
    $cash = tbAccount('1010', 'Cash', 'asset', 'debit');
    $bank = tbAccount('1020', 'Bank', 'asset', 'debit');

    tbJournal($fiscalYear, '2026-01-05', [[$cash, 400, 0], [$bank, 0, 400]]);
    tbJournal($fiscalYear, '2026-02-05', [[$bank, 100, 0], [$cash, 0, 100]]);

    $report = tbReport($fiscalYear);

    expect($report['data'])->toHaveCount(2)
        ->and((float) tbRow($report, '1010')['closing_balance'])->toBe(300.0)
        ->and((float) tbRow($report, '1010')['debit_total'])->toBe(400.0)
        ->and((float) tbRow($report, '1010')['credit_total'])->toBe(100.0)
        ->and((float) tbRow($report, '1020')['closing_balance'])->toBe(-300.0);
});

test('it filters by fiscal year and period', function () {
    $fiscalYear = tbFiscalYear(2026);
    $otherFiscalYear = tbFiscalYear(2027);
    $account = tbAccount('1010', 'Filtered Cash', 'asset', 'debit');
    $otherAccount = tbAccount('1020', 'Other Cash', 'asset', 'debit');
    $contra = tbAccount('3010', 'Modal', 'equity', 'credit');

    tbJournal($fiscalYear, '2026-02-10', [[$account, 1500, 0], [$contra, 0, 1500]]);
    tbJournal($otherFiscalYear, '2027-01-10', [[$otherAccount, 2500, 0], [$contra, 0, 2500]]);

    $report = tbReport($fiscalYear, '&period_month=2&period_year=2026');

    expect(collect($report['data'])->pluck('account_name')->all())
        ->toContain('Filtered Cash')
        ->not->toContain('Other Cash');
});

test('saldo awal neraca membawa jurnal posted dari tahun sebelumnya, laba rugi tidak', function () {
    $previous = tbFiscalYear(2025, 'closed');
    $fiscalYear = tbFiscalYear(2026);
    $cash = tbAccount('1010', 'Kas', 'asset', 'debit');
    $revenue = tbAccount('4010', 'Penjualan', 'revenue', 'credit');
    $equity = tbAccount('3010', 'Modal', 'equity', 'credit');

    tbJournal($previous, '2025-06-01', [[$cash, 300, 0], [$equity, 0, 300]]);
    tbJournal($fiscalYear, '2026-03-10', [[$cash, 200, 0], [$revenue, 0, 200]]);

    $report = tbReport($fiscalYear, '&period_month=3&period_year=2026');

    expect((float) tbRow($report, '1010')['opening_balance'])->toBe(300.0)
        ->and((float) tbRow($report, '1010')['closing_balance'])->toBe(500.0)
        ->and((float) tbRow($report, '4010')['opening_balance'])->toBe(0.0)
        ->and((float) tbRow($report, '4010')['closing_balance'])->toBe(200.0);
});

test('saldo awal laba rugi hanya dari tahun fiskal yang sama', function () {
    $fiscalYear = tbFiscalYear(2026);
    $previous = tbFiscalYear(2025, 'closed');
    $cash = tbAccount('1010', 'Kas', 'asset', 'debit');
    $revenue = tbAccount('4010', 'Penjualan', 'revenue', 'credit');

    tbJournal($previous, '2025-11-01', [[$cash, 900, 0], [$revenue, 0, 900]]);
    tbJournal($fiscalYear, '2026-01-10', [[$cash, 100, 0], [$revenue, 0, 100]]);
    tbJournal($fiscalYear, '2026-03-10', [[$cash, 50, 0], [$revenue, 0, 50]]);

    $report = tbReport($fiscalYear, '&period_month=3&period_year=2026');

    expect((float) tbRow($report, '4010')['opening_balance'])->toBe(100.0)
        ->and((float) tbRow($report, '4010')['closing_balance'])->toBe(150.0)
        ->and((float) tbRow($report, '1010')['opening_balance'])->toBe(1000.0);
});

test('tutup-buka-tutup tidak mengubah trial balance dan tetap seimbang', function () {
    $fiscalYear = tbFiscalYear(2026);
    $coa = CoaVersion::factory()->create(['status' => 'active']);
    $cash = tbAccount('1010', 'Kas', 'asset', 'debit', $coa->id);
    $revenue = tbAccount('4010', 'Penjualan', 'revenue', 'credit', $coa->id);
    $retained = tbAccount('3010', 'Laba Ditahan', 'equity', 'credit', $coa->id);

    tbJournal($fiscalYear, '2026-06-01', [[$cash, 4000, 0], [$revenue, 0, 4000]]);

    $closing = PeriodClosing::factory()->annual()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'period_year' => 2026,
        'period_month' => null,
        'status' => 'draft',
        'retained_earnings_account_id' => $retained->id,
    ]);

    $closing = app(ClosePeriodAction::class)->execute($closing);
    $afterFirstClose = tbReport($fiscalYear);

    app(ReopenPeriodAction::class)->execute($closing);
    app(ClosePeriodAction::class)->execute($closing->refresh());
    $afterCycle = tbReport($fiscalYear);

    expect($afterCycle['summary'])->toBe($afterFirstClose['summary'])
        ->and($afterCycle['summary']['is_balanced'])->toBeTrue()
        ->and(collect($afterCycle['data'])->pluck('closing_balance', 'account_code')->all())
        ->toBe(collect($afterFirstClose['data'])->pluck('closing_balance', 'account_code')->all());

    expect((float) tbRow($afterCycle, '4010')['closing_balance'])->toBe(0.0)
        ->and((float) tbRow($afterCycle, '3010')['credit_balance'])->toBe(4000.0);
});

test('ekspor trial balance memakai basis jurnal posted yang sama dengan laporan', function () {
    $fiscalYear = tbFiscalYear(2026);
    $cash = tbAccount('1010', 'Kas', 'asset', 'debit');
    $revenue = tbAccount('4010', 'Penjualan', 'revenue', 'credit');
    tbJournal($fiscalYear, '2026-03-10', [[$cash, 750, 0], [$revenue, 0, 750]]);

    $rows = (new TrialBalanceReportExport([
        'fiscal_year_id' => $fiscalYear->id,
    ]))->collection();

    $cashRow = $rows->firstWhere(0, '1010');
    expect($cashRow)->not->toBeNull()
        ->and((float) $cashRow[6])->toBe(750.0)
        ->and((float) $cashRow[7])->toBe(0.0);
});

test('it can export trial balance report', function () {
    $fiscalYear = FiscalYear::factory()->create();

    $response = postJson('/api/reports/trial-balance-detailed/export', [
        'fiscal_year_id' => $fiscalYear->id,
        'period_month' => 1,
        'period_year' => 2026,
    ])->assertOk()->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
});

test('it can export trial balance financial report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);

    $response = postJson('/api/reports/trial-balance/export', [
        'fiscal_year_id' => $fiscalYear->id,
    ])->assertOk()->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('trial_balance_financial_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it requires permission to export trial balance financial report', function () {
    $userWithoutPermission = createTestUserWithPermissions([]);
    Sanctum::actingAs($userWithoutPermission, ['*']);

    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);

    postJson('/api/reports/trial-balance/export', [
        'fiscal_year_id' => $fiscalYear->id,
    ])->assertStatus(403);
});

test('it validates fiscal_year_id when exporting trial balance financial report', function () {
    postJson('/api/reports/trial-balance/export', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['fiscal_year_id']);

    postJson('/api/reports/trial-balance/export', [
        'fiscal_year_id' => 99999,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['fiscal_year_id']);
});

test('it returns 403 without trial_balance_report permission', function () {
    $user = createTestUserWithPermissions([]);
    Sanctum::actingAs($user, ['*']);

    getJson('/api/reports/trial-balance-detailed')->assertForbidden();
});
