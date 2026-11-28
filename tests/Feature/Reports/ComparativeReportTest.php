<?php

namespace Tests\Feature\Reports;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['comparative_report']);

    $this->fyPrev = FiscalYear::create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'closed',
    ]);

    $this->fyCurr = FiscalYear::create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $this->coaPrev = CoaVersion::create([
        'name' => 'COA 2025',
        'fiscal_year_id' => $this->fyPrev->id,
        'status' => 'archived',
    ]);

    $this->coaCurr = CoaVersion::create([
        'name' => 'COA 2026',
        'fiscal_year_id' => $this->fyCurr->id,
        'status' => 'active',
    ]);
});

function comparativeNodeByCode(array $nodes, string $code): ?array
{
    foreach ($nodes as $node) {
        if (($node['code'] ?? null) === $code) {
            return $node;
        }

        $found = comparativeNodeByCode($node['children'] ?? [], $code);
        if ($found !== null) {
            return $found;
        }
    }

    return null;
}

function comparativePostJournal(
    FiscalYear $fiscalYear,
    string $entryNumber,
    string $entryDate,
    Account $debitAccount,
    Account $creditAccount,
    float $amount,
    int $userId,
): void {
    $entry = JournalEntry::create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_number' => $entryNumber,
        'entry_date' => $entryDate,
        'description' => $entryNumber,
        'status' => 'posted',
        'created_by' => $userId,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $debitAccount->id,
        'debit' => $amount,
        'credit' => 0,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $creditAccount->id,
        'debit' => 0,
        'credit' => $amount,
    ]);
}

test('can view comparative report properties via json', function () {
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/comparative')
        ->assertStatus(200)
        ->assertJsonStructure(['fiscalYears', 'selectedYearId', 'comparisonYearId'])
        ->assertJsonPath('selectedYearId', $this->fyCurr->id)
        ->assertJsonPath('comparisonYearId', $this->fyPrev->id);
});

test('comparative uses matching account code for comparison balance without mappings', function () {
    $cashPrev = Account::create([
        'coa_version_id' => $this->coaPrev->id,
        'code' => '11110',
        'name' => 'Cash in Bank',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 2,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $assetHeader = Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '10000',
        'name' => 'Assets',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => false,
    ]);

    Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '11110',
        'name' => 'Cash in Bank',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 2,
        'parent_id' => $assetHeader->id,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $jePrev = JournalEntry::create([
        'fiscal_year_id' => $this->fyPrev->id,
        'entry_number' => 'JV-PREV-CODE-1',
        'entry_date' => '2025-01-15',
        'description' => 'Prev cash by code',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $jePrev->id,
        'account_id' => $cashPrev->id,
        'debit' => 450,
        'credit' => 0,
    ]);

    Sanctum::actingAs($this->user, ['*']);
    $this->getJson(
        '/api/reports/comparative?fiscal_year_id=' .
            $this->fyCurr->id .
            '&comparison_year_id=' .
            $this->fyPrev->id,
    )
        ->assertStatus(200)
        ->assertJsonPath('report.totals.assets', 0)
        ->assertJsonPath('report.totals.comparison_assets', 450)
        ->assertJsonPath('report.totals.change_assets', -450);
});

test('comparative split mapping keeps parent subtotal equal to sum of children', function () {
    $cashPrev = Account::create([
        'coa_version_id' => $this->coaPrev->id,
        'code' => '11100',
        'name' => 'Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 2,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $assetHeader = Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '10000',
        'name' => 'Assets',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => false,
    ]);

    $currentAssets = Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '11000',
        'name' => 'Current Assets',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 2,
        'parent_id' => $assetHeader->id,
        'is_active' => true,
        'is_cash_flow' => false,
    ]);

    $cashInBank = Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '11110',
        'name' => 'Cash in Bank',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 3,
        'parent_id' => $currentAssets->id,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $pettyCash = Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '11120',
        'name' => 'Petty Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 3,
        'parent_id' => $currentAssets->id,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    AccountMapping::create([
        'source_account_id' => $cashPrev->id,
        'target_account_id' => $cashInBank->id,
        'type' => 'split',
        'notes' => 'primary',
    ]);

    AccountMapping::create([
        'source_account_id' => $cashPrev->id,
        'target_account_id' => $pettyCash->id,
        'type' => 'split',
        'notes' => 'secondary',
    ]);

    $jePrev = JournalEntry::create([
        'fiscal_year_id' => $this->fyPrev->id,
        'entry_number' => 'JV-PREV-1',
        'entry_date' => '2025-02-01',
        'description' => 'Prev cash',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $jePrev->id,
        'account_id' => $cashPrev->id,
        'debit' => 300,
        'credit' => 0,
    ]);

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->getJson(
        '/api/reports/comparative?fiscal_year_id=' .
            $this->fyCurr->id .
            '&comparison_year_id=' .
            $this->fyPrev->id,
    )
        ->assertStatus(200)
        ->assertJsonPath('report.totals.assets', 0)
        ->assertJsonPath('report.totals.comparison_assets', 300)
        ->assertJsonPath('report.totals.change_assets', -300)
        ->json();

    $currentAssetsNode = comparativeNodeByCode($response['report']['assets'], '11000');
    $childrenComparisonSum = collect($currentAssetsNode['children'] ?? [])
        ->sum('comparison_balance');

    expect($currentAssetsNode)->not->toBeNull()
        ->and(round((float) $currentAssetsNode['comparison_balance'], 2))
        ->toBe(round((float) $childrenComparisonSum, 2))
        ->and(round((float) $currentAssetsNode['comparison_balance'], 2))->toBe(300.0);
});

test('comparative includes current year earnings in equity for both periods', function () {
    $definitions = [
        '11110' => ['name' => 'Cash in Bank', 'type' => 'asset', 'normal_balance' => 'debit'],
        '31000' => ['name' => 'Paid-in Capital', 'type' => 'equity', 'normal_balance' => 'credit'],
        '41000' => ['name' => 'Revenue', 'type' => 'revenue', 'normal_balance' => 'credit'],
        '51000' => ['name' => 'Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
    ];

    $previousAccounts = [];
    $currentAccounts = [];

    foreach ($definitions as $code => $definition) {
        $previousAccounts[$code] = Account::create([
            'coa_version_id' => $this->coaPrev->id,
            'code' => $code,
            'level' => 2,
            'is_active' => true,
            'is_cash_flow' => $code === '11110',
            ...$definition,
        ]);

        $currentAccounts[$code] = Account::create([
            'coa_version_id' => $this->coaCurr->id,
            'code' => $code,
            'level' => 2,
            'is_active' => true,
            'is_cash_flow' => $code === '11110',
            ...$definition,
        ]);
    }

    comparativePostJournal(
        $this->fyCurr,
        'JV-CUR-REV',
        '2026-03-01',
        $currentAccounts['11110'],
        $currentAccounts['41000'],
        1000,
        $this->user->id,
    );
    comparativePostJournal(
        $this->fyCurr,
        'JV-CUR-EXP',
        '2026-03-02',
        $currentAccounts['51000'],
        $currentAccounts['11110'],
        400,
        $this->user->id,
    );
    comparativePostJournal(
        $this->fyPrev,
        'JV-PRE-REV',
        '2025-03-01',
        $previousAccounts['11110'],
        $previousAccounts['41000'],
        500,
        $this->user->id,
    );
    comparativePostJournal(
        $this->fyPrev,
        'JV-PRE-EXP',
        '2025-03-02',
        $previousAccounts['51000'],
        $previousAccounts['11110'],
        100,
        $this->user->id,
    );

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->getJson(
        '/api/reports/comparative?fiscal_year_id=' .
            $this->fyCurr->id .
            '&comparison_year_id=' .
            $this->fyPrev->id,
    )
        ->assertOk()
        ->json();

    $totals = $response['report']['totals'];
    $currentYearEarnings = collect($response['report']['equity'])->firstWhere('code', '9999-CYE');

    expect($currentYearEarnings)->not->toBeNull()
        ->and((float) $currentYearEarnings['balance'])->toBe(600.0)
        ->and((float) $currentYearEarnings['comparison_balance'])->toBe(400.0)
        ->and(round((float) $totals['assets'], 2))
        ->toBe(round((float) $totals['liabilities'] + (float) $totals['equity'], 2))
        ->and(round((float) $totals['comparison_assets'], 2))
        ->toBe(round((float) $totals['comparison_liabilities'] + (float) $totals['comparison_equity'], 2))
        ->and((float) $totals['equity'])->toBe(600.0)
        ->and((float) $totals['comparison_equity'])->toBe(400.0);
});

test('comparative keeps archived accumulated depreciation negative like the current year', function () {
    $createAccount = static function (
        int $coaVersionId,
        string $code,
        string $name,
        string $normalBalance,
    ): Account {
        return Account::create([
            'coa_version_id' => $coaVersionId,
            'code' => $code,
            'name' => $name,
            'type' => 'asset',
            'normal_balance' => $normalBalance,
            'level' => 1,
            'is_active' => true,
        ]);
    };

    $previousCash = $createAccount($this->coaPrev->id, '11110', 'Cash in Bank', 'debit');
    $previousAccumulated = $createAccount($this->coaPrev->id, '12200', 'Accumulated Depreciation', 'credit');
    $currentCash = $createAccount($this->coaCurr->id, '11110', 'Cash in Bank', 'debit');
    $currentAccumulated = $createAccount($this->coaCurr->id, '12200', 'Accumulated Depreciation', 'credit');

    comparativePostJournal(
        $this->fyPrev,
        'JV-PRE-DEP',
        '2025-12-31',
        $previousCash,
        $previousAccumulated,
        200,
        $this->user->id,
    );
    comparativePostJournal(
        $this->fyCurr,
        'JV-CUR-DEP',
        '2026-12-31',
        $currentCash,
        $currentAccumulated,
        300,
        $this->user->id,
    );

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->getJson(
        '/api/reports/comparative?fiscal_year_id=' .
            $this->fyCurr->id .
            '&comparison_year_id=' .
            $this->fyPrev->id,
    )
        ->assertOk()
        ->json();

    $accumulatedDepreciation = comparativeNodeByCode($response['report']['assets'], '12200');

    expect($accumulatedDepreciation)->not->toBeNull()
        ->and((float) $accumulatedDepreciation['balance'])->toBe(-300.0)
        ->and((float) $accumulatedDepreciation['comparison_balance'])->toBe(-200.0);
});

test('it can export comparative report', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-10 09:30:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/comparative/export', [
        'fiscal_year_id' => $this->fyCurr->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('comparative_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it can export comparative report with comparison year', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-10 09:30:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/comparative/export', [
        'fiscal_year_id' => $this->fyCurr->id,
        'comparison_year_id' => $this->fyPrev->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('comparative_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it requires permission to export comparative report', function () {
    $userNoPerms = createTestUserWithPermissions([]);

    Sanctum::actingAs($userNoPerms, ['*']);
    $this->postJson('/api/reports/comparative/export', [
        'fiscal_year_id' => $this->fyCurr->id,
    ])->assertForbidden();
});

test('it validates fiscal_year_id when exporting comparative', function () {
    Sanctum::actingAs($this->user, ['*']);

    $this->postJson('/api/reports/comparative/export', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_year_id']);

    $this->postJson('/api/reports/comparative/export', [
        'fiscal_year_id' => 99999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_year_id']);
});
