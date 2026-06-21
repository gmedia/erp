<?php

namespace Tests\Feature\JournalEntries;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('inter-branch-clearing');

beforeEach(function () {
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    /** @var CoaVersion $coaVersion */
    $coaVersion = $this->fiscalYear->coaVersions()->where('status', 'active')->firstOrFail();
    $this->accountMap = Account::where('coa_version_id', $coaVersion->id)
        ->pluck('id', 'code')
        ->toArray();
    $this->clearingId = (int) $this->accountMap['1999-IBC'];

    $this->user = User::firstOrFail();
    $this->branchA = Branch::factory()->create(['name' => 'Branch A']);
    $this->branchB = Branch::factory()->create(['name' => 'Branch B']);

    $this->action = app(CreateJournalEntryAction::class);
    $this->reports = app(FinancialReportService::class);
});

test('multi-branch journal created via write-path flows through to per-branch reports', function () {
    $entry = $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Branch A pays Branch B expense',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 500000],
            ['account_id' => $this->accountMap['52000'], 'branch_id' => $this->branchB->id, 'debit' => 500000, 'credit' => 0],
        ],
    ]);

    $persistedLines = $entry->lines()->get();
    expect($persistedLines->where('account_id', $this->clearingId))->toHaveCount(2);

    foreach ([$this->branchA->id, $this->branchB->id] as $branchId) {
        $rows = $this->reports->getTrialBalance($this->fiscalYear->id, $branchId);
        expect(round((float) collect($rows)->sum('debit'), 2))
            ->toBe(round((float) collect($rows)->sum('credit'), 2));
    }

    $reportA = $this->reports->getBalanceSheet($this->fiscalYear->id, null, $this->branchA->id);
    $dueFromA = collect($reportA['assets'])->firstWhere('code', '1999-IBC');
    expect($dueFromA)->not->toBeNull();
    expect($dueFromA['name'])->toBe('Due From Branches');
    expect(round($dueFromA['balance'], 2))->toBe(500000.0);
    expect(collect($reportA['liabilities'])->firstWhere('code', '1999-IBC'))->toBeNull();
    expect(round($reportA['totals']['assets'], 2))
        ->toBe(round($reportA['totals']['liabilities'] + $reportA['totals']['equity'], 2));

    $reportB = $this->reports->getBalanceSheet($this->fiscalYear->id, null, $this->branchB->id);
    $dueToB = collect($reportB['liabilities'])->firstWhere('code', '1999-IBC');
    expect($dueToB)->not->toBeNull();
    expect($dueToB['name'])->toBe('Due To Branches');
    expect(round($dueToB['balance'], 2))->toBe(500000.0);
    expect(collect($reportB['assets'])->firstWhere('code', '1999-IBC'))->toBeNull();
    expect(round($reportB['totals']['assets'], 2))
        ->toBe(round($reportB['totals']['liabilities'] + $reportB['totals']['equity'], 2));
});

test('per-branch income statement reflects expense booked on the receiving branch', function () {
    $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Branch A pays Branch B expense',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 500000],
            ['account_id' => $this->accountMap['52000'], 'branch_id' => $this->branchB->id, 'debit' => 500000, 'credit' => 0],
        ],
    ]);

    $incomeB = $this->reports->getIncomeStatement($this->fiscalYear->id, null, $this->branchB->id);
    expect(round($incomeB['totals']['expense'], 2))->toBe(500000.0);

    $incomeA = $this->reports->getIncomeStatement($this->fiscalYear->id, null, $this->branchA->id);
    expect(round($incomeA['totals']['expense'], 2))->toBe(0.0);
});

test('clearing nets to zero company-wide after multi-branch posting', function () {
    $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Branch A pays Branch B expense',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 500000],
            ['account_id' => $this->accountMap['52000'], 'branch_id' => $this->branchB->id, 'debit' => 500000, 'credit' => 0],
        ],
    ]);

    $companyWide = $this->reports->getBalanceSheet($this->fiscalYear->id);
    expect(collect($companyWide['assets'])->firstWhere('code', '1999-IBC'))->toBeNull();
    expect(collect($companyWide['liabilities'])->firstWhere('code', '1999-IBC'))->toBeNull();
    expect(round($companyWide['totals']['assets'], 2))
        ->toBe(round($companyWide['totals']['liabilities'] + $companyWide['totals']['equity'], 2));
});
