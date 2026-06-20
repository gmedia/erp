<?php

namespace Tests\Feature\Reports;

use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    /** @var CoaVersion $coaVersion */
    $coaVersion = $this->fiscalYear->coaVersions()->where('status', 'active')->firstOrFail();
    $this->coaVersion = $coaVersion;
    $this->accountMap = Account::where('coa_version_id', $coaVersion->id)
        ->pluck('id', 'code')
        ->toArray();

    $this->user = User::firstOrFail();
    $this->branchA = Branch::factory()->create(['name' => 'Branch A']);
    $this->branchB = Branch::factory()->create(['name' => 'Branch B']);

    $this->service = app(FinancialReportService::class);
});

/**
 * @param  list<array{0: string, 1: int, 2: int}>  $lines
 */
function seedBranchJournal(FiscalYear $fiscalYear, array $accountMap, User $user, ?int $branchId, string $entryNumber, array $lines): void
{
    $journal = JournalEntry::create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_number' => $entryNumber,
        'entry_date' => '2026-02-01',
        'reference' => $entryNumber,
        'description' => 'Per-branch invariant fixture',
        'status' => 'posted',
        'branch_id' => $branchId,
        'created_by' => $user->id,
        'posted_by' => $user->id,
        'posted_at' => now(),
    ]);

    foreach ($lines as [$code, $debit, $credit]) {
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $accountMap[$code],
            'debit' => $debit,
            'credit' => $credit,
            'memo' => 'fixture',
        ]);
    }
}

function seedTwoBranchFixtures(object $ctx): void
{
    // Branch A: assets 1,400,000 = liabilities 400,000 + net income 1,000,000
    seedBranchJournal($ctx->fiscalYear, $ctx->accountMap, $ctx->user, $ctx->branchA->id, 'JV-A-1', [
        ['11110', 1000000, 0],
        ['41000', 0, 1000000],
    ]);
    seedBranchJournal($ctx->fiscalYear, $ctx->accountMap, $ctx->user, $ctx->branchA->id, 'JV-A-2', [
        ['11300', 400000, 0],
        ['21100', 0, 400000],
    ]);

    // Branch B: assets 400,000 = liabilities 0 + net income 400,000
    seedBranchJournal($ctx->fiscalYear, $ctx->accountMap, $ctx->user, $ctx->branchB->id, 'JV-B-1', [
        ['11110', 600000, 0],
        ['41000', 0, 600000],
    ]);
    seedBranchJournal($ctx->fiscalYear, $ctx->accountMap, $ctx->user, $ctx->branchB->id, 'JV-B-2', [
        ['52000', 200000, 0],
        ['11110', 0, 200000],
    ]);
}

test('per-branch trial balance self-balances (total debit == total credit)', function () {
    seedTwoBranchFixtures($this);

    foreach ([$this->branchA->id, $this->branchB->id] as $branchId) {
        $rows = $this->service->getTrialBalance($this->fiscalYear->id, $branchId);

        $totalDebit = collect($rows)->sum('debit');
        $totalCredit = collect($rows)->sum('credit');

        expect(round($totalDebit, 2))->toBe(round($totalCredit, 2));
    }
});

test('per-branch balance sheet balances (assets == liabilities + equity) with per-branch CYE', function () {
    seedTwoBranchFixtures($this);

    $reportA = $this->service->getBalanceSheet($this->fiscalYear->id, null, $this->branchA->id);
    expect(round($reportA['totals']['assets'], 2))->toBe(1400000.0);
    expect(round($reportA['totals']['liabilities'], 2))->toBe(400000.0);
    expect(round($reportA['totals']['equity'], 2))->toBe(1000000.0);
    expect(round($reportA['totals']['assets'], 2))
        ->toBe(round($reportA['totals']['liabilities'] + $reportA['totals']['equity'], 2));

    $reportB = $this->service->getBalanceSheet($this->fiscalYear->id, null, $this->branchB->id);
    expect(round($reportB['totals']['assets'], 2))->toBe(400000.0);
    expect(round($reportB['totals']['liabilities'], 2))->toBe(0.0);
    expect(round($reportB['totals']['equity'], 2))->toBe(400000.0);
    expect(round($reportB['totals']['assets'], 2))
        ->toBe(round($reportB['totals']['liabilities'] + $reportB['totals']['equity'], 2));
});

test('balance sheet CYE row equals per-branch net income', function () {
    seedTwoBranchFixtures($this);

    $reportA = $this->service->getBalanceSheet($this->fiscalYear->id, null, $this->branchA->id);

    $cye = collect($reportA['equity'])->firstWhere('code', '9999-CYE');
    expect($cye)->not->toBeNull();
    expect(round($cye['balance'], 2))->toBe(1000000.0);
});

test('omitting branchId reproduces company-wide behavior (backward compatible)', function () {
    seedTwoBranchFixtures($this);

    $companyWide = $this->service->getBalanceSheet($this->fiscalYear->id);

    // Seeded NULL-branch journals: assets 8,000,000 / liabilities 3,000,000 / equity (CYE) 5,000,000.
    // Plus branch fixtures: assets +1,800,000, liabilities +400,000, net income +1,400,000.
    expect(round($companyWide['totals']['assets'], 2))->toBe(9800000.0);
    expect(round($companyWide['totals']['liabilities'], 2))->toBe(3400000.0);
    expect(round($companyWide['totals']['equity'], 2))->toBe(6400000.0);
});

test('null-branch journals do not break per-branch balancing', function () {
    seedTwoBranchFixtures($this);

    $reportA = $this->service->getBalanceSheet($this->fiscalYear->id, null, $this->branchA->id);

    expect(round($reportA['totals']['assets'], 2))
        ->toBe(round($reportA['totals']['liabilities'] + $reportA['totals']['equity'], 2));

    expect(round($reportA['totals']['assets'], 2))->toBe(1400000.0);
});

test('balance sheet endpoint accepts branch_id and scopes the report', function () {
    seedTwoBranchFixtures($this);
    $user = createTestUserWithPermissions(['balance_sheet_report']);

    Sanctum::actingAs($user, ['*']);
    $this->getJson('/api/reports/balance-sheet?fiscal_year_id=' . $this->fiscalYear->id . '&branch_id=' . $this->branchA->id)
        ->assertOk()
        ->assertJsonPath('report.totals.assets', 1400000)
        ->assertJsonPath('report.totals.liabilities', 400000)
        ->assertJsonPath('report.totals.equity', 1000000);
});

test('balance sheet export validates branch_id', function () {
    $user = createTestUserWithPermissions(['balance_sheet_report']);

    Sanctum::actingAs($user, ['*']);
    $this->postJson('/api/reports/balance-sheet/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'branch_id' => 999999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['branch_id']);
});
