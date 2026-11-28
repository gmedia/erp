<?php

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('reports');

/**
 * Kartu ringkasan (computed_sections) Neraca & Laba Rugi harus mengikuti pohon
 * akun: sub-baris terisi, TOTAL KEWAJIBAN = total kewajiban, LABA BERSIH =
 * pendapatan - beban, dan LABA KOTOR = pendapatan - HPP (Refs #623).
 */
beforeEach(function () {
    $this->user = createTestUserWithPermissions([
        'balance_sheet_report',
        'income_statement_report',
    ]);
    Sanctum::actingAs($this->user, ['*']);

    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    $coa = $this->fiscalYear->coaVersions()->where('status', 'active')->firstOrFail();
    $this->account = static fn (string $code): Account => Account::query()
        ->where('coa_version_id', $coa->id)
        ->where('code', $code)
        ->firstOrFail();

    // Kosongkan jurnal demo agar angka kartu ringkasan hanya berasal dari test ini.
    $demoEntryIds = JournalEntry::query()->pluck('id');
    JournalEntryLine::whereIn('journal_entry_id', $demoEntryIds)->delete();
    JournalEntry::query()->delete();
});

function fscJournal(FiscalYear $fiscalYear, Account $debit, Account $credit, float $amount): void
{
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => '2026-03-10',
        'status' => 'posted',
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $debit->id,
        'debit' => $amount,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $credit->id,
        'debit' => 0,
        'credit' => $amount,
    ]);
}

function fscSection(array $response, string $code): array
{
    $section = collect($response['computed_sections'])->firstWhere('code', $code);

    expect($section)->not->toBeNull("Section {$code} tidak ditemukan");

    return $section;
}

function fscValue(array $response, string $code): float
{
    return (float) fscSection($response, $code)['value'];
}

test('kartu neraca mengisi sub-baris dan total kewajiban dari pohon akun', function () {
    $cash = ($this->account)('11110');
    $payable = ($this->account)('21100');
    $revenue = ($this->account)('41000');
    $cogs = ($this->account)('51000');

    fscJournal($this->fiscalYear, $cash, $revenue, 1000);
    fscJournal($this->fiscalYear, $cogs, $cash, 400);
    fscJournal($this->fiscalYear, $cash, $payable, 500);

    $response = getJson("/api/reports/balance-sheet?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();
    $totals = $response['report']['totals'];

    // Semua aset pada skenario ini adalah aset lancar (kas), jadi sub-baris
    // Aset Lancar harus sama dengan total aset dari pohon akun.
    expect(fscValue($response, 'current_assets'))->toBe(round($totals['assets'], 2))
        ->and(fscValue($response, 'total_assets'))->toBe(round($totals['assets'], 2))
        ->and(fscValue($response, 'current_liabilities'))->toBe(500.0)
        ->and(fscValue($response, 'total_liabilities'))->toBe(round($totals['liabilities'], 2))
        ->and(fscValue($response, 'total_liabilities'))->toBe(500.0)
        // Laba tahun berjalan = pendapatan - beban (1000 - 400).
        ->and(fscValue($response, 'current_year_earnings'))->toBe(600.0)
        ->and(fscValue($response, 'total_equity'))->toBe(round($totals['equity'], 2))
        // Neraca seimbang: TOTAL KEWAJIBAN + EKUITAS = TOTAL ASET.
        ->and(fscValue($response, 'total_liab_equity'))->toBe(round($totals['assets'], 2));
});

test('kartu neraca mengisi modal disetor dan laba ditahan dari pohon akun', function () {
    $cash = ($this->account)('11110');
    $capital = ($this->account)('31000');
    $retained = ($this->account)('32000');

    fscJournal($this->fiscalYear, $cash, $capital, 700);
    fscJournal($this->fiscalYear, $cash, $retained, 300);

    $response = getJson("/api/reports/balance-sheet?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();

    expect(fscValue($response, 'paid_in_capital'))->toBe(700.0)
        ->and(fscValue($response, 'retained_earnings'))->toBe(300.0)
        ->and(fscValue($response, 'total_equity'))->toBe(1000.0)
        ->and(fscValue($response, 'total_liab_equity'))->toBe(1000.0);
});

test('kartu laba rugi menghitung laba kotor dan laba bersih dari pohon akun', function () {
    $cash = ($this->account)('11110');
    $revenue = ($this->account)('41000');
    $cogs = ($this->account)('51000');
    $operatingExpense = ($this->account)('52000');
    $depreciationExpense = ($this->account)('53000');
    $nonOperatingExpense = ($this->account)('53100');

    fscJournal($this->fiscalYear, $cash, $revenue, 1000);
    fscJournal($this->fiscalYear, $cogs, $cash, 400);
    fscJournal($this->fiscalYear, $operatingExpense, $cash, 100);
    fscJournal($this->fiscalYear, $depreciationExpense, $cash, 50);
    fscJournal($this->fiscalYear, $nonOperatingExpense, $cash, 25);

    $response = getJson("/api/reports/income-statement?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();
    $totals = $response['report']['totals'];

    expect(fscValue($response, 'total_revenue'))->toBe(1000.0)
        ->and(fscValue($response, 'cost_of_goods_sold'))->toBe(400.0)
        // LABA KOTOR = pendapatan - HPP.
        ->and(fscValue($response, 'gross_profit'))->toBe(600.0)
        ->and(fscValue($response, 'operating_expense'))->toBe(100.0)
        ->and(fscValue($response, 'depreciation_expense'))->toBe(50.0)
        ->and(fscValue($response, 'non_operating_expense'))->toBe(25.0)
        // LABA BERSIH = pendapatan - beban.
        ->and(fscValue($response, 'net_income'))->toBe(425.0)
        ->and(fscValue($response, 'net_income'))->toBe(round($totals['revenue'] - $totals['expense'], 2))
        ->and(fscValue($response, 'net_income'))->toBe(round($totals['net_income'], 2));
});

test('laba tahun berjalan di neraca sama dengan laba bersih laba rugi', function () {
    $cash = ($this->account)('11110');
    $revenue = ($this->account)('41000');
    $cogs = ($this->account)('51000');
    $operatingExpense = ($this->account)('52000');

    fscJournal($this->fiscalYear, $cash, $revenue, 800);
    fscJournal($this->fiscalYear, $cogs, $cash, 250);
    fscJournal($this->fiscalYear, $operatingExpense, $cash, 150);

    $balanceSheet = getJson("/api/reports/balance-sheet?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();
    $incomeStatement = getJson("/api/reports/income-statement?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();

    expect(fscValue($balanceSheet, 'current_year_earnings'))->toBe(400.0)
        ->and(fscValue($balanceSheet, 'current_year_earnings'))
        ->toBe(fscValue($incomeStatement, 'net_income'));
});
