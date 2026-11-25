<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('trial-balance-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['trial_balance_report']);
    Sanctum::actingAs($this->user, ['*']);

    // Data demo seeder: tahun fiskal terbuka beserta COA aktif yang terhubung.
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    $coa = $this->fiscalYear->coaVersions()->where('status', 'active')->firstOrFail();
    $this->cash = Account::where('coa_version_id', $coa->id)
        ->where('type', 'asset')
        ->where('normal_balance', 'debit')
        ->firstOrFail();
    $this->revenue = Account::where('coa_version_id', $coa->id)->where('type', 'revenue')->firstOrFail();

    // Kosongkan jurnal demo tahun ini agar angka exact hanya berasal dari test ini.
    $demoEntryIds = JournalEntry::where('fiscal_year_id', $this->fiscalYear->id)->pluck('id');
    JournalEntryLine::whereIn('journal_entry_id', $demoEntryIds)->delete();
    JournalEntry::whereIn('id', $demoEntryIds)->delete();
});

function tbsJournal(
    FiscalYear $fiscalYear,
    Account $debitAccount,
    Account $creditAccount,
    float $amount,
    string $status = 'posted',
    ?int $branchId = null,
): void {
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => '2026-03-10',
        'status' => $status,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $debitAccount->id,
        'debit' => $amount,
        'credit' => 0,
        'branch_id' => $branchId,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $creditAccount->id,
        'debit' => 0,
        'credit' => $amount,
        'branch_id' => $branchId,
    ]);
}

function tbsSummarySection(array $response, string $code): ?array
{
    return collect($response['computed_sections'])->firstWhere('code', $code);
}

test('kartu TOTAL trial balance sama dengan jumlah debit tabel rincian', function () {
    tbsJournal($this->fiscalYear, $this->cash, $this->revenue, 750);

    $response = getJson("/api/reports/trial-balance?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();

    $detail = getJson("/api/reports/trial-balance-detailed?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();

    expect((float) tbsSummarySection($response, 'total_debit_credit')['value'])
        ->toBe((float) $detail['summary']['total_debit'])
        ->and((float) tbsSummarySection($response, 'total_debit_credit')['value'])->toBe(750.0);
});

test('kartu Semua Akun trial balance terisi, bukan nol', function () {
    tbsJournal($this->fiscalYear, $this->cash, $this->revenue, 750);

    $response = getJson("/api/reports/trial-balance?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();

    expect((float) tbsSummarySection($response, 'all_accounts')['value'])->toBe(750.0);
});

test('kartu ringkasan trial balance mengikuti filter cabang', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    tbsJournal($this->fiscalYear, $this->cash, $this->revenue, 400, 'posted', $branchA->id);
    tbsJournal($this->fiscalYear, $this->cash, $this->revenue, 900, 'posted', $branchB->id);

    $response = getJson("/api/reports/trial-balance?fiscal_year_id={$this->fiscalYear->id}&branch_id={$branchA->id}")
        ->assertOk()
        ->json();

    expect((float) tbsSummarySection($response, 'total_debit_credit')['value'])->toBe(400.0);
});

test('jurnal draft tidak ikut kartu ringkasan trial balance', function () {
    tbsJournal($this->fiscalYear, $this->cash, $this->revenue, 750, 'draft');

    $response = getJson("/api/reports/trial-balance?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();

    expect((float) tbsSummarySection($response, 'total_debit_credit')['value'])->toBe(0.0)
        ->and((float) tbsSummarySection($response, 'all_accounts')['value'])->toBe(0.0);
});

test('tanpa jurnal kartu ringkasan trial balance bernilai nol', function () {
    $response = getJson("/api/reports/trial-balance?fiscal_year_id={$this->fiscalYear->id}")
        ->assertOk()
        ->json();

    expect((float) tbsSummarySection($response, 'total_debit_credit')['value'])->toBe(0.0);
});
