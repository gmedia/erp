<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
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
