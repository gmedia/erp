<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Database\Seeders\ReportConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('report-export-parity');

beforeEach(function () {
    Storage::fake('public');
    Carbon::setTestNow(Carbon::parse('2026-03-31 10:00:00'));

    $this->user = createTestUserWithPermissions([
        'ar_aging_report',
        'ar_outstanding_report',
        'customer_statement_report',
        'ap_aging_report',
        'ap_outstanding_report',
        'cash_flow_report',
        'comparative_report',
    ]);
    Sanctum::actingAs($this->user, ['*']);

    $fiscalYear = FiscalYear::factory()->create();
    $customerA = Customer::factory()->create(['name' => 'Pelanggan Alfa']);
    $customerB = Customer::factory()->create(['name' => 'Pelanggan Beta']);
    $branchA = Branch::factory()->create(['name' => 'Cabang Utara']);
    $branchB = Branch::factory()->create(['name' => 'Cabang Selatan']);

    $invoices = [
        ['INV-PAR-001', $customerA, $branchA, '2026-01-05', 100000],
        ['INV-PAR-002', $customerA, $branchB, '2026-02-10', 250000],
        ['INV-PAR-003', $customerB, $branchA, '2025-11-20', 175000],
        ['INV-PAR-004', $customerB, $branchB, '2026-03-01', 80000],
    ];

    foreach ($invoices as [$number, $customer, $branch, $date, $total]) {
        CustomerInvoice::factory()->create([
            'invoice_number' => $number,
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'fiscal_year_id' => $fiscalYear->id,
            'invoice_date' => $date,
            'due_date' => Carbon::parse($date)->addDays(30)->toDateString(),
            'status' => 'sent',
            'grand_total' => $total,
            'amount_received' => 0,
            'amount_due' => $total,
        ]);
    }

    $this->customerA = $customerA;
    $this->branchB = $branchB;
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * @param  array<string, mixed>  $filters
 * @return array{count: int, total: float}
 */
function arAgingJsonParity(array $filters): array
{
    $response = test()->getJson('/api/reports/ar-aging?' . http_build_query($filters))
        ->assertOk();

    return [
        'count' => (int) $response->json('meta.total'),
        'total' => round((float) $response->json('summary.totals.amount_due'), 2),
    ];
}

/**
 * @param  array<string, mixed>  $filters
 * @return array{count: int, total: float}
 */
function arAgingExportParity(array $filters): array
{
    $response = test()->postJson('/api/reports/ar-aging/export', $filters)
        ->assertOk();

    $rows = Excel::toArray(null, 'exports/' . $response->json('filename'), 'public')[0];
    $headings = array_shift($rows);
    $dueIndex = array_search('Amount Due', $headings, true);

    return [
        'count' => count($rows),
        'total' => round(array_sum(array_map(
            static fn (array $row): float => (float) $row[$dueIndex],
            $rows,
        )), 2),
    ];
}

test('ar aging export matches json for unfiltered scope', function () {
    expect(arAgingExportParity([]))->toEqual(arAgingJsonParity([]));
});

test('ar outstanding export matches json for unfiltered and branch filter', function () {
    $outstandingJson = function (array $filters): array {
        $response = test()->getJson('/api/reports/ar-outstanding?' . http_build_query($filters))
            ->assertOk();

        return [
            'count' => (int) $response->json('meta.total'),
            'total' => round((float) $response->json('summary.totals.amount_due'), 2),
        ];
    };

    $outstandingExport = function (array $filters): array {
        $response = test()->postJson('/api/reports/ar-outstanding/export', $filters)
            ->assertOk();

        $rows = Excel::toArray(null, 'exports/' . $response->json('filename'), 'public')[0];
        $headings = array_shift($rows);
        $dueIndex = array_search('Amount Due', $headings, true);

        return [
            'count' => count($rows),
            'total' => round(array_sum(array_map(
                static fn (array $row): float => (float) $row[$dueIndex],
                $rows,
            )), 2),
        ];
    };

    expect($outstandingExport([]))->toEqual($outstandingJson([]));
    expect($outstandingExport(['branch_id' => $this->branchB->id]))
        ->toEqual($outstandingJson(['branch_id' => $this->branchB->id]));
});

test('ap aging and outstanding exports match json for unfiltered and branch filter', function () {
    $supplier = Supplier::factory()->create(['name' => 'Pemasok Gamma']);
    $fiscalYear = FiscalYear::factory()->create();
    $branch = Branch::factory()->create(['name' => 'Cabang Barat']);

    foreach ([[10, 1000000], [-35, 2000000]] as [$dueOffset, $total]) {
        SupplierBill::factory()->confirmed()->create([
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'fiscal_year_id' => $fiscalYear->id,
            'due_date' => Carbon::now()->addDays($dueOffset)->format('Y-m-d'),
            'grand_total' => $total,
            'amount_due' => $total,
        ]);
    }

    $compare = function (string $path, array $filters): void {
        $json = test()->getJson('/api/reports/' . $path . '?' . http_build_query($filters))->assertOk();
        $export = test()->postJson('/api/reports/' . $path . '/export', $filters)->assertOk();

        $rows = Excel::toArray(null, 'exports/' . $export->json('filename'), 'public')[0];
        $headings = array_shift($rows);
        $dueIndex = array_search('Amount Due', $headings, true);
        $exportTotal = round(array_sum(array_map(
            static fn (array $row): float => (float) $row[$dueIndex],
            $rows,
        )), 2);

        expect(count($rows))->toBe((int) $json->json('meta.total'));
        expect($exportTotal)->toEqual(round((float) $json->json('summary.totals.amount_due'), 2));
    };

    $compare('ap-aging', []);
    $compare('ap-aging', ['branch_id' => $branch->id]);
    $compare('ap-outstanding', []);
    $compare('ap-outstanding', ['branch_id' => $branch->id]);
});

test('cash flow export matches json rows and totals', function () {
    (new ReportConfigurationSeeder)->run();
    $fiscalYear = FiscalYear::create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);
    $cashAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'name' => 'Cash in Banks',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);

    $journal = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journal->id,
        'account_id' => $cashAccount->id,
        'debit' => 500000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journal->id,
        'account_id' => $cashAccount->id,
        'debit' => 0,
        'credit' => 120000,
    ]);

    $filters = ['fiscal_year_id' => $fiscalYear->id];

    $json = test()->getJson('/api/reports/cash-flow?' . http_build_query($filters))->assertOk();
    $report = collect($json->json('report'));

    $export = test()->postJson('/api/reports/cash-flow/export', $filters)->assertOk();
    $rows = Excel::toArray(null, 'exports/' . $export->json('filename'), 'public')[0];
    array_shift($rows);
    $rows = collect($rows);

    expect($rows->count())->toBe($report->count());
    expect(round($rows->sum(fn (array $row): float => (float) $row[5]), 2))
        ->toEqual(round($report->sum(fn (array $row): float => (float) $row['inflow']), 2));
    expect(round($rows->sum(fn (array $row): float => (float) $row[6]), 2))
        ->toEqual(round($report->sum(fn (array $row): float => (float) $row['outflow']), 2));
});

test('comparative export section totals match json totals', function () {
    $fyPrev = FiscalYear::create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'closed',
    ]);
    $fyCurr = FiscalYear::create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coaPrev = CoaVersion::create([
        'name' => 'COA 2025',
        'fiscal_year_id' => $fyPrev->id,
        'status' => 'archived',
    ]);
    $coaCurr = CoaVersion::create([
        'name' => 'COA 2026',
        'fiscal_year_id' => $fyCurr->id,
        'status' => 'active',
    ]);

    $cashCurr = Account::factory()->create([
        'coa_version_id' => $coaCurr->id,
        'code' => '11110',
        'name' => 'Cash in Banks',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
    ]);
    $cashPrev = Account::factory()->create([
        'coa_version_id' => $coaPrev->id,
        'code' => '11110',
        'name' => 'Cash in Banks',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
    ]);

    $journalCurr = JournalEntry::factory()->create([
        'fiscal_year_id' => $fyCurr->id,
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalCurr->id,
        'account_id' => $cashCurr->id,
        'debit' => 450,
        'credit' => 0,
    ]);
    $journalPrev = JournalEntry::factory()->create([
        'fiscal_year_id' => $fyPrev->id,
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalPrev->id,
        'account_id' => $cashPrev->id,
        'debit' => 300,
        'credit' => 0,
    ]);

    $filters = [
        'fiscal_year_id' => $fyCurr->id,
        'comparison_year_id' => $fyPrev->id,
    ];

    $json = test()->getJson('/api/reports/comparative?' . http_build_query($filters))->assertOk();
    $totals = $json->json('report.totals');

    $export = test()->postJson('/api/reports/comparative/export', $filters)->assertOk();
    $rows = Excel::toArray(null, 'exports/' . $export->json('filename'), 'public')[0];
    array_shift($rows);
    $rows = collect($rows);

    $sections = [
        'assets' => 'Assets',
        'liabilities' => 'Liabilities',
        'equity' => 'Equity',
        'revenues' => 'Revenues',
        'expenses' => 'Expenses',
    ];

    foreach ($sections as $key => $label) {
        $row = $rows->first(fn (array $line): bool => ($line[2] ?? null) === 'Total ' . $label);
        expect($row)->not->toBeNull();
        expect(round((float) $row[4], 2))->toEqual(round((float) $totals[$key], 2));
        expect(round((float) $row[5], 2))->toEqual(round((float) $totals['comparison_' . $key], 2));
    }
});

test('customer statement export matches json for unfiltered and customer filter', function () {
    $statementJson = function (array $filters): array {
        $response = test()->getJson('/api/reports/customer-statement?' . http_build_query($filters))
            ->assertOk();

        return [
            'count' => (int) $response->json('meta.total'),
            'total' => round(array_sum(array_map(
                static fn (array $row): float => (float) $row['amounts']['amount_due'],
                $response->json('data'),
            )), 2),
        ];
    };

    $statementExport = function (array $filters): array {
        $response = test()->postJson('/api/reports/customer-statement/export', $filters)
            ->assertOk();

        $rows = Excel::toArray(null, 'exports/' . $response->json('filename'), 'public')[0];
        $headings = array_shift($rows);
        $dueIndex = array_search('Amount Due', $headings, true);

        return [
            'count' => count($rows),
            'total' => round(array_sum(array_map(
                static fn (array $row): float => (float) $row[$dueIndex],
                $rows,
            )), 2),
        ];
    };

    expect($statementExport([]))->toEqual($statementJson([]));
    expect($statementExport(['customer_id' => $this->customerA->id]))
        ->toEqual($statementJson(['customer_id' => $this->customerA->id]));
});

test('ar aging export matches json for customer and branch filter', function () {
    $filters = [
        'customer_id' => $this->customerA->id,
        'branch_id' => $this->branchB->id,
    ];

    expect(arAgingExportParity($filters))->toEqual(arAgingJsonParity($filters));
});
