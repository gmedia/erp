<?php

namespace Tests\Feature\Reports;

use App\Exports\IncomeStatementReportExport;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    $this->fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $this->coa = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);

    $this->revenue = Account::factory()->create([
        'coa_version_id' => $this->coa->id,
        'code' => '41000',
        'name' => 'Pendapatan Penjualan',
        'type' => 'revenue',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    $this->expense = Account::factory()->create([
        'coa_version_id' => $this->coa->id,
        'code' => '52000',
        'name' => 'Beban Operasional',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();
    $this->deptA = Department::factory()->create();
    $this->deptB = Department::factory()->create();

    $this->user = createTestUserWithPermissions(['income_statement_report']);
});

/**
 * Satu jurnal posted berisi baris pendapatan (kredit) dan baris beban (debit).
 */
function postIncomeLines(
    Account $revenue,
    Account $expense,
    int $fiscalYearId,
    float $amount,
    ?int $branchId,
    ?int $departmentId,
): void {
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYearId,
        'status' => 'posted',
        'branch_id' => $branchId,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $revenue->id,
        'debit' => 0,
        'credit' => $amount,
        'branch_id' => $branchId,
        'department_id' => $departmentId,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $expense->id,
        'debit' => $amount,
        'credit' => 0,
        'branch_id' => $branchId,
        'department_id' => $departmentId,
    ]);
}

function seedDepartmentJournal(object $t): void
{
    postIncomeLines($t->revenue, $t->expense, $t->fiscalYear->id, 1000, $t->branchA->id, $t->deptA->id);
    postIncomeLines($t->revenue, $t->expense, $t->fiscalYear->id, 400, $t->branchA->id, $t->deptB->id);
    postIncomeLines($t->revenue, $t->expense, $t->fiscalYear->id, 250, $t->branchB->id, $t->deptA->id);
    postIncomeLines($t->revenue, $t->expense, $t->fiscalYear->id, 100, $t->branchA->id, null);
    postIncomeLines($t->revenue, $t->expense, $t->fiscalYear->id, 50, $t->branchB->id, null);
}

/**
 * @return array{revenue: float, expense: float, net_income: float}
 */
function incomeTotals(int $fiscalYearId, ?int $branchId = null, ?int $departmentId = null, bool $none = false): array
{
    $report = app(FinancialReportService::class)->getIncomeStatement(
        $fiscalYearId,
        null,
        $branchId,
        $departmentId,
        withoutDepartment: $none,
    );

    return [
        'revenue' => (float) $report['totals']['revenue'],
        'expense' => (float) $report['totals']['expense'],
        'net_income' => (float) $report['totals']['net_income'],
    ];
}

test('tanpa filter departemen, angka laba rugi sama dengan total semua baris', function () {
    seedDepartmentJournal($this);

    $totals = incomeTotals($this->fiscalYear->id);

    expect($totals['revenue'])->toBe(1800.0)
        ->and($totals['expense'])->toBe(1800.0)
        ->and($totals['net_income'])->toBe(0.0);
});

test('filter departemen hanya menghitung baris departemen tersebut', function () {
    seedDepartmentJournal($this);

    $totals = incomeTotals($this->fiscalYear->id, null, $this->deptA->id);

    expect($totals['revenue'])->toBe(1250.0)
        ->and($totals['expense'])->toBe(1250.0);
});

test("filter 'none' hanya menghitung baris tanpa departemen", function () {
    seedDepartmentJournal($this);

    $totals = incomeTotals($this->fiscalYear->id, null, null, true);

    expect($totals['revenue'])->toBe(150.0)
        ->and($totals['expense'])->toBe(150.0);
});

test('jumlah dept A + dept B + none sama dengan total tanpa filter', function () {
    seedDepartmentJournal($this);

    $all = incomeTotals($this->fiscalYear->id);
    $deptA = incomeTotals($this->fiscalYear->id, null, $this->deptA->id);
    $deptB = incomeTotals($this->fiscalYear->id, null, $this->deptB->id);
    $none = incomeTotals($this->fiscalYear->id, null, null, true);

    expect($deptA['revenue'] + $deptB['revenue'] + $none['revenue'])->toBe($all['revenue'])
        ->and($deptA['expense'] + $deptB['expense'] + $none['expense'])->toBe($all['expense']);
});

test('kombinasi filter cabang dan departemen bekerja bersama', function () {
    seedDepartmentJournal($this);

    // Cabang A + dept A: hanya baris 1000 (pendapatan) dan 1000 (beban).
    $totals = incomeTotals($this->fiscalYear->id, $this->branchA->id, $this->deptA->id);

    expect($totals['revenue'])->toBe(1000.0)
        ->and($totals['expense'])->toBe(1000.0);

    // Cabang A + none: hanya baris 100.
    $none = incomeTotals($this->fiscalYear->id, $this->branchA->id, null, true);

    expect($none['revenue'])->toBe(100.0);
});

test('endpoint income statement menerima department_id dan none', function () {
    seedDepartmentJournal($this);
    Sanctum::actingAs($this->user, ['*']);

    $deptA = $this->getJson('/api/reports/income-statement?fiscal_year_id=' . $this->fiscalYear->id
        . '&department_id=' . $this->deptA->id)
        ->assertOk()
        ->json('report.totals');

    expect($deptA['revenue'])->toBe(1250);

    $none = $this->getJson('/api/reports/income-statement?fiscal_year_id=' . $this->fiscalYear->id
        . '&department_id=none')
        ->assertOk()
        ->json('report.totals');

    expect($none['revenue'])->toBe(150);
});

test('endpoint income statement menolak department_id yang tidak ada atau tidak valid', function () {
    Sanctum::actingAs($this->user, ['*']);

    $this->getJson('/api/reports/income-statement?fiscal_year_id=' . $this->fiscalYear->id . '&department_id=999999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['department_id']);

    $this->getJson('/api/reports/income-statement?fiscal_year_id=' . $this->fiscalYear->id . '&department_id=abc')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['department_id']);
});

test('export income statement menghormati filter departemen', function () {
    seedDepartmentJournal($this);

    $rows = (new IncomeStatementReportExport([
        'fiscal_year_id' => $this->fiscalYear->id,
        'department_id' => (string) $this->deptA->id,
    ]))->collection();

    $totalRevenue = $rows->firstWhere(fn ($row) => $row[2] === 'Total Revenues');

    expect((float) $totalRevenue[4])->toBe(1250.0);
});

test("export income statement dengan department_id 'none' hanya memuat baris tanpa departemen", function () {
    seedDepartmentJournal($this);

    $rows = (new IncomeStatementReportExport([
        'fiscal_year_id' => $this->fiscalYear->id,
        'department_id' => 'none',
    ]))->collection();

    $totalRevenue = $rows->firstWhere(fn ($row) => $row[2] === 'Total Revenues');

    expect((float) $totalRevenue[4])->toBe(150.0);
});
