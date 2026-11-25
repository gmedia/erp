<?php

use App\Exports\CashFlowReportExport;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ReportSection;
use App\Models\Setting;
use Database\Seeders\ReportConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    $this->fiscalYear = FiscalYear::create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);

    $this->user = createTestUserWithPermissions(['cash_flow_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access cash flow report', function () {
    Sanctum::actingAs($this->otherUser, ['*']);
    $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertForbidden();
});

test('it can fetch cash flow data via json', function () {
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk()
        ->assertJsonStructure([
            'selectedYearId',
            'fiscalYears',
            'report',
            'configuration',
            'computed_sections',
        ])
        ->assertJsonPath('selectedYearId', $this->fiscalYear->id);
});

test('it reports cash debit as inflow and credit as outflow', function () {
    $this->seed(ReportConfigurationSeeder::class);
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
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
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'posted',
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journal->id,
        'account_id' => $cashAccount->id,
        'debit' => 500000,
        'credit' => 0,
    ]);

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    $row = collect($response->json('report'))->firstWhere('code', '11110');
    expect((float) $row['inflow'])->toBe(500000.0)
        ->and((float) $row['outflow'])->toBe(0.0);

    $sections = collect($response->json('computed_sections'))->keyBy('code');
    expect((float) $sections['cash_inflow']['value'])->toBe(500000.0)
        ->and((float) $sections['net_change']['value'])->toBe(500000.0);
});

/**
 * Helper: buat akun kas, lalu posting satu jurnal dengan baris kas dan baris lawan.
 *
 * @param  array<int, array{account: Account, debit: float, credit: float}>  $counterLines
 */
function postCashFlowJournal(
    FiscalYear $fiscalYear,
    CoaVersion $coaVersion,
    Account $cashAccount,
    float $cashDebit,
    float $cashCredit,
    array $counterLines,
    string $status = 'posted',
    ?string $entryDate = null,
): JournalEntry {
    $journal = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => $status,
        'entry_date' => $entryDate ?? $fiscalYear->start_date->toDateString(),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journal->id,
        'account_id' => $cashAccount->id,
        'debit' => $cashDebit,
        'credit' => $cashCredit,
    ]);

    foreach ($counterLines as $line) {
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journal->id,
            'account_id' => $line['account']->id,
            'debit' => $line['debit'],
            'credit' => $line['credit'],
        ]);
    }

    return $journal;
}

test('it splits cash movements into operating, investing and financing activities', function () {
    $this->seed(ReportConfigurationSeeder::class);
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $receivable = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11200',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
    ]);
    $equipment = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '12100',
        'type' => 'asset',
        'sub_type' => 'non_current_asset',
        'normal_balance' => 'debit',
    ]);
    $longTermLoan = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '22100',
        'type' => 'liability',
        'sub_type' => 'non_current_liability',
        'normal_balance' => 'credit',
    ]);

    // Operasi: penerimaan dari piutang (kas masuk 300.000)
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 300000, 0, [
        ['account' => $receivable, 'debit' => 0, 'credit' => 300000],
    ]);
    // Investasi: beli peralatan tunai (kas keluar 200.000)
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 0, 200000, [
        ['account' => $equipment, 'debit' => 200000, 'credit' => 0],
    ]);
    // Pendanaan: terima pinjaman jangka panjang (kas masuk 150.000)
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 150000, 0, [
        ['account' => $longTermLoan, 'debit' => 0, 'credit' => 150000],
    ]);

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    expect((float) $response->json('activities.operating'))->toBe(300000.0)
        ->and((float) $response->json('activities.investing'))->toBe(-200000.0)
        ->and((float) $response->json('activities.financing'))->toBe(150000.0);
});

test('it excludes transfers between cash accounts from cash flow activities', function () {
    $this->seed(ReportConfigurationSeeder::class);
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $bank = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $petty = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11120',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);

    // Transfer kas bank ke kas kecil: tidak mengubah kas gabungan.
    postCashFlowJournal($this->fiscalYear, $coaVersion, $petty, 100000, 0, [
        ['account' => $bank, 'debit' => 0, 'credit' => 100000],
    ]);

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    expect((float) $response->json('activities.operating'))->toBe(0.0)
        ->and((float) $response->json('activities.investing'))->toBe(0.0)
        ->and((float) $response->json('activities.financing'))->toBe(0.0)
        ->and((float) $response->json('net_change'))->toBe(0.0);
});

test('it attributes counter lines net of cash in a mixed journal and totals equal cash change', function () {
    $this->seed(ReportConfigurationSeeder::class);
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $equipment = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '12100',
        'type' => 'asset',
        'sub_type' => 'non_current_asset',
        'normal_balance' => 'debit',
    ]);
    $ownerCapital = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '31000',
        'type' => 'equity',
        'sub_type' => null,
        'normal_balance' => 'credit',
    ]);
    $sales = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '41000',
        'type' => 'revenue',
        'sub_type' => null,
        'normal_balance' => 'credit',
    ]);

    // Jurnal campuran: kas masuk 100.000 dengan lawan ekuitas 60.000 (pendanaan)
    // dan pendapatan 40.000 (operasi). Kas keluar 30.000 untuk peralatan (investasi).
    // Kontribusi tiap baris lawan = kredit - debit: pendanaan 60.000, operasi 40.000,
    // investasi -30.000 (tanpa pembagian proporsional).
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 100000, 30000, [
        ['account' => $ownerCapital, 'debit' => 0, 'credit' => 60000],
        ['account' => $sales, 'debit' => 0, 'credit' => 40000],
        ['account' => $equipment, 'debit' => 30000, 'credit' => 0],
    ]);

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    $operating = (float) $response->json('activities.operating');
    $investing = (float) $response->json('activities.investing');
    $financing = (float) $response->json('activities.financing');
    $netChange = (float) $response->json('net_change');

    expect($operating)->toBe(40000.0)
        ->and($investing)->toBe(-30000.0)
        ->and($financing)->toBe(60000.0)
        ->and(round($operating + $investing + $financing, 2))->toBe($netChange)
        ->and($netChange)->toBe(70000.0);
});

test('it reports opening and closing cash balance around the period net change', function () {
    $this->seed(ReportConfigurationSeeder::class);
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $equity = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '31100',
        'type' => 'equity',
        'sub_type' => null,
        'normal_balance' => 'credit',
    ]);
    $receivable = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11200',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
    ]);

    // Kas masuk sebelum tanggal mulai tahun fiskal 2025: saldo awal 400.000.
    // Dicatat di tahun fiskal 2024 agar tidak ikut mutasi periode 2025.
    $previousYear = FiscalYear::create([
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'closed',
    ]);
    $opening = JournalEntry::factory()->create([
        'fiscal_year_id' => $previousYear->id,
        'status' => 'posted',
        'entry_date' => '2024-12-31',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $opening->id,
        'account_id' => $cash->id,
        'debit' => 400000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $opening->id,
        'account_id' => $equity->id,
        'debit' => 0,
        'credit' => 400000,
    ]);

    // Periode: kenaikan bersih +100.000 (300.000 masuk - 200.000 keluar).
    // entry_date dipaku di dalam tahun fiskal agar tidak jatuh ke saldo awal.
    $inPeriod = [
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2025-03-15',
    ];
    // Jurnal seimbang: kas masuk 300.000 (lawan piutang kredit), kas keluar 200.000 (lawan piutang debit).
    foreach ([[300000, 0, 0, 300000], [0, 200000, 200000, 0]] as [$cashDebit, $cashCredit, $recvDebit, $recvCredit]) {
        $journal = JournalEntry::factory()->create($inPeriod);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journal->id,
            'account_id' => $cash->id,
            'debit' => $cashDebit,
            'credit' => $cashCredit,
        ]);
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journal->id,
            'account_id' => $receivable->id,
            'debit' => $recvDebit,
            'credit' => $recvCredit,
        ]);
    }

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    expect((float) $response->json('opening_cash_balance'))->toBe(400000.0)
        ->and((float) $response->json('net_change'))->toBe(100000.0)
        ->and((float) $response->json('closing_cash_balance'))->toBe(500000.0);
});

test('it reconciles activity totals to the opening to closing cash change', function () {
    $this->seed(ReportConfigurationSeeder::class);
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $receivable = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11200',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
    ]);
    $equipment = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '12100',
        'type' => 'asset',
        'sub_type' => 'non_current_asset',
        'normal_balance' => 'debit',
    ]);

    // Saldo awal kas 0 -> akhir: +500.000 - 125.000 = 375.000.
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 500000, 0, [
        ['account' => $receivable, 'debit' => 0, 'credit' => 500000],
    ]);
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 0, 125000, [
        ['account' => $equipment, 'debit' => 125000, 'credit' => 0],
    ]);

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    $total = (float) $response->json('activities.operating')
        + (float) $response->json('activities.investing')
        + (float) $response->json('activities.financing');

    expect($total)->toBe(375000.0)
        ->and((float) $response->json('net_change'))->toBe(375000.0);
});

test('it carries opening cash across fiscal years when the coa version changes', function () {
    Sanctum::actingAs($this->user, ['*']);

    $previousYear = FiscalYear::create([
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'closed',
    ]);
    $oldCoa = CoaVersion::factory()->create([
        'fiscal_year_id' => $previousYear->id,
        'status' => 'archived',
    ]);
    $oldCash = Account::factory()->create([
        'coa_version_id' => $oldCoa->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $oldEquity = Account::factory()->create([
        'coa_version_id' => $oldCoa->id,
        'code' => '31100',
        'type' => 'equity',
        'sub_type' => null,
        'normal_balance' => 'credit',
    ]);
    postCashFlowJournal($previousYear, $oldCoa, $oldCash, 400000, 0, [
        ['account' => $oldEquity, 'debit' => 0, 'credit' => 400000],
    ], entryDate: '2024-06-01');

    // Tahun berjalan memakai versi COA baru dengan id akun berbeda.
    $newCoa = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $newCash = Account::factory()->create([
        'coa_version_id' => $newCoa->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $newReceivable = Account::factory()->create([
        'coa_version_id' => $newCoa->id,
        'code' => '11200',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
    ]);
    postCashFlowJournal($this->fiscalYear, $newCoa, $newCash, 100000, 0, [
        ['account' => $newReceivable, 'debit' => 0, 'credit' => 100000],
    ], entryDate: '2025-03-15');

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    expect((float) $response->json('opening_cash_balance'))->toBe(400000.0)
        ->and((float) $response->json('closing_cash_balance'))->toBe(500000.0);

    // Saldo akhir tahun 2024 harus sama dengan saldo awal tahun 2025.
    $previous = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $previousYear->id)
        ->assertOk();
    expect((float) $previous->json('closing_cash_balance'))->toBe(400000.0);
});

test('it keeps activity totals equal to net change when filtered by branch', function () {
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $equipment = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '12100',
        'type' => 'asset',
        'sub_type' => 'non_current_asset',
        'normal_balance' => 'debit',
    ]);
    $sales = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '41000',
        'type' => 'revenue',
        'sub_type' => null,
        'normal_balance' => 'credit',
    ]);
    $branch = Branch::factory()->create();

    // Jurnal cabang yang sama: kas +100.000, lawan pendapatan 60.000 dan aset 40.000 (investasi).
    $journal = postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 100000, 0, [
        ['account' => $sales, 'debit' => 0, 'credit' => 60000],
        ['account' => $equipment, 'debit' => 0, 'credit' => 40000],
    ]);
    JournalEntryLine::query()->where('journal_entry_id', $journal->id)->update(['branch_id' => $branch->id]);

    $query = http_build_query([
        'fiscal_year_id' => $this->fiscalYear->id,
        'branch_id' => $branch->id,
    ]);
    $response = $this->getJson('/api/reports/cash-flow?' . $query)
        ->assertOk();

    $total = (float) $response->json('activities.operating')
        + (float) $response->json('activities.investing')
        + (float) $response->json('activities.financing');

    expect($total)->toBe((float) $response->json('net_change'))
        ->and((float) $response->json('net_change'))->toBe(100000.0)
        ->and((float) $response->json('activities.operating'))->toBe(60000.0)
        ->and((float) $response->json('activities.investing'))->toBe(40000.0);
});

test('it returns activities and net change without seeded cash flow configuration', function () {
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $receivable = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11200',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
    ]);
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 250000, 0, [
        ['account' => $receivable, 'debit' => 0, 'credit' => 250000],
    ]);

    // Tanpa $this->seed(ReportConfigurationSeeder::class): konfigurasi aktivitas tidak ada.
    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    expect((float) $response->json('net_change'))->toBe(250000.0)
        ->and((float) $response->json('activities.operating'))->toBe(250000.0)
        ->and((float) $response->json('activities.investing'))->toBe(0.0)
        ->and((float) $response->json('activities.financing'))->toBe(0.0);
});

test('it derives net change from cash lines independently of activity totals', function () {
    Sanctum::actingAs($this->user, ['*']);

    $previousYear = FiscalYear::create([
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'closed',
    ]);
    $oldCoa = CoaVersion::factory()->create(['fiscal_year_id' => $previousYear->id, 'status' => 'active']);
    $oldCash = Account::factory()->create([
        'coa_version_id' => $oldCoa->id, 'code' => '11110', 'type' => 'asset',
        'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'is_cash_flow' => true,
    ]);
    $oldSales = Account::factory()->create([
        'coa_version_id' => $oldCoa->id, 'code' => '41000', 'type' => 'revenue',
        'sub_type' => null, 'normal_balance' => 'credit',
    ]);
    // Saldo kas awal tahun berjalan: 80.000.
    postCashFlowJournal($previousYear, $oldCoa, $oldCash, 80000, 0, [
        ['account' => $oldSales, 'debit' => 0, 'credit' => 80000],
    ], 'posted', '2024-06-01');

    $coaVersion = CoaVersion::factory()->create(['fiscal_year_id' => $this->fiscalYear->id, 'status' => 'active']);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id, 'code' => '11110', 'type' => 'asset',
        'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'is_cash_flow' => true,
    ]);
    $sales = Account::factory()->create([
        'coa_version_id' => $coaVersion->id, 'code' => '41000', 'type' => 'revenue',
        'sub_type' => null, 'normal_balance' => 'credit',
    ]);
    $equipment = Account::factory()->create([
        'coa_version_id' => $coaVersion->id, 'code' => '12100', 'type' => 'asset',
        'sub_type' => 'non_current_asset', 'normal_balance' => 'debit',
    ]);
    $equity = Account::factory()->create([
        'coa_version_id' => $coaVersion->id, 'code' => '31000', 'type' => 'equity',
        'sub_type' => null, 'normal_balance' => 'credit',
    ]);

    // Operasi +50.000, investasi -30.000, pendanaan +20.000 => net_change +40.000.
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 50000, 0, [
        ['account' => $sales, 'debit' => 0, 'credit' => 50000],
    ]);
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 0, 30000, [
        ['account' => $equipment, 'debit' => 30000, 'credit' => 0],
    ]);
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 20000, 0, [
        ['account' => $equity, 'debit' => 0, 'credit' => 20000],
    ]);

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    $netChange = (float) $response->json('net_change');
    $activitySum = (float) $response->json('activities.operating')
        + (float) $response->json('activities.investing')
        + (float) $response->json('activities.financing');

    expect($netChange)->toBe(40000.0)
        ->and($activitySum)->toBe($netChange)
        ->and((float) $response->json('opening_cash_balance'))->toBe(80000.0)
        ->and((float) $response->json('closing_cash_balance'))->toBe(120000.0)
        ->and((float) $response->json('closing_cash_balance') - (float) $response->json('opening_cash_balance'))
        ->toBe($netChange);
});

test('cash flow configuration seeder does not contain activity rows', function () {
    $this->seed(ReportConfigurationSeeder::class);

    $codes = ReportSection::query()
        ->whereHas('reportConfiguration', fn ($q) => $q->where('code', 'cash_flow'))
        ->pluck('code')
        ->all();

    expect($codes)->not->toContain('operating_cash_flow')
        ->and($codes)->not->toContain('investing_cash_flow')
        ->and($codes)->not->toContain('financing_cash_flow')
        ->and($codes)->toContain('net_change');
});

test('it can export cash flow report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/cash-flow/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('cash_flow_report_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('cash flow export lists activities, opening cash, net change and closing cash', function () {
    $this->seed(ReportConfigurationSeeder::class);
    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'name' => 'Cash in Banks',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $revenue = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '41100',
        'name' => 'Sales',
        'type' => 'revenue',
        'sub_type' => 'operating_revenue',
        'normal_balance' => 'credit',
        'is_cash_flow' => false,
    ]);

    $journal = JournalEntry::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2025-06-01',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journal->id,
        'account_id' => $cash->id,
        'debit' => 300000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journal->id,
        'account_id' => $revenue->id,
        'debit' => 0,
        'credit' => 300000,
    ]);

    $filters = ['fiscal_year_id' => $this->fiscalYear->id];
    $export = new CashFlowReportExport($filters);
    $rows = collect($export->collection()->all());
    $labels = collect($export->footerRows())->pluck(0)->all();

    expect($labels)->toContain('Operating activities')
        ->and($labels)->toContain('Investing activities')
        ->and($labels)->toContain('Financing activities')
        ->and($labels)->toContain('Opening cash')
        ->and($labels)->toContain('Net change')
        ->and($labels)->toContain('Closing cash')
        ->and($rows->firstWhere(0, '11110'))->not->toBeNull();
});

test('it requires permission to export cash flow report', function () {
    Sanctum::actingAs($this->otherUser, ['*']);
    $this->postJson('/api/reports/cash-flow/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
    ])
        ->assertForbidden();
});

test('it validates fiscal_year_id when exporting cash flow', function () {
    Sanctum::actingAs($this->user, ['*']);

    $this->postJson('/api/reports/cash-flow/export', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_year_id']);

    $this->postJson('/api/reports/cash-flow/export', [
        'fiscal_year_id' => 99999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_year_id']);
});

test('it classifies asset disposal gain and loss as investing activity', function () {
    $this->seed(ReportConfigurationSeeder::class);
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11100',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $equipment = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '12100',
        'type' => 'asset',
        'sub_type' => 'non_current_asset',
        'normal_balance' => 'debit',
    ]);
    $accumulated = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '12200',
        'type' => 'asset',
        'sub_type' => 'non_current_asset',
        'normal_balance' => 'credit',
    ]);
    $gain = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '42000',
        'type' => 'revenue',
        'sub_type' => null,
        'normal_balance' => 'credit',
    ]);
    $loss = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '52000',
        'type' => 'expense',
        'sub_type' => null,
        'normal_balance' => 'debit',
    ]);

    Setting::set('asset_disposal_gain_account_id', (string) $gain->id);
    Setting::set('asset_disposal_loss_account_id', (string) $loss->id);

    // Harga perolehan 100, akumulasi 30 -> nilai buku 70. Dijual 100 -> laba 30.
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 100, 0, [
        ['account' => $accumulated, 'debit' => 30, 'credit' => 0],
        ['account' => $equipment, 'debit' => 0, 'credit' => 100],
        ['account' => $gain, 'debit' => 0, 'credit' => 30],
    ]);

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    expect((float) $response->json('activities.operating'))->toBe(0.0)
        ->and((float) $response->json('activities.investing'))->toBe(100.0)
        ->and((float) $response->json('activities.financing'))->toBe(0.0)
        ->and((float) $response->json('net_change'))->toBe(100.0);
});

test('it classifies asset disposal loss from a sale below book value as investing activity', function () {
    $this->seed(ReportConfigurationSeeder::class);
    Sanctum::actingAs($this->user, ['*']);

    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
    $cash = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11100',
        'type' => 'asset',
        'sub_type' => 'current_asset',
        'normal_balance' => 'debit',
        'is_cash_flow' => true,
    ]);
    $equipment = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '12100',
        'type' => 'asset',
        'sub_type' => 'non_current_asset',
        'normal_balance' => 'debit',
    ]);
    $accumulated = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '12200',
        'type' => 'asset',
        'sub_type' => 'non_current_asset',
        'normal_balance' => 'credit',
    ]);
    $gain = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '42000',
        'type' => 'revenue',
        'sub_type' => null,
        'normal_balance' => 'credit',
    ]);
    $loss = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '52000',
        'type' => 'expense',
        'sub_type' => null,
        'normal_balance' => 'debit',
    ]);

    Setting::set('asset_disposal_gain_account_id', (string) $gain->id);
    Setting::set('asset_disposal_loss_account_id', (string) $loss->id);

    // Harga perolehan 100, akumulasi 30 -> nilai buku 70. Dijual 50 -> rugi 20.
    postCashFlowJournal($this->fiscalYear, $coaVersion, $cash, 50, 0, [
        ['account' => $loss, 'debit' => 20, 'credit' => 0],
        ['account' => $accumulated, 'debit' => 30, 'credit' => 0],
        ['account' => $equipment, 'debit' => 0, 'credit' => 100],
    ]);

    $response = $this->getJson('/api/reports/cash-flow?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertOk();

    expect((float) $response->json('activities.operating'))->toBe(0.0)
        ->and((float) $response->json('activities.investing'))->toBe(50.0)
        ->and((float) $response->json('net_change'))->toBe(50.0);
});
